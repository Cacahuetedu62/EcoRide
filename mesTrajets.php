<?php
require_once('templates/header.php');

$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

// Vérifie si l'utilisateur est connecté
if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    $utilisateur_id = $_SESSION['utilisateur']['id'];
} else {
    echo "Utilisateur non connecté.";
    exit;
}

// Affiche un message de session s'il existe
if (isset($_SESSION['message'])) {
    $alertClass = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
    echo "<div class='alert alert-{$alertClass} message-container'>" . htmlspecialchars($_SESSION['message']) . "</div>";
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 2; // Limite de 2 trajets par page
$offset = ($page - 1) * $limit;

// Requête pour les trajets en tant que chauffeur
$sql_chauffeur = "SELECT DISTINCT t.id, COUNT(*) OVER() as total_count
FROM trajets t
JOIN trajet_utilisateur tu ON t.id = tu.trajet_id
LEFT JOIN reservations r ON t.id = r.trajet_id
WHERE tu.utilisateur_id = :utilisateur_id
AND (r.statut != 'termine' OR r.statut IS NULL)";

// Requête pour les trajets en tant que passager
$sql_passager = "SELECT DISTINCT t.id, COUNT(*) OVER() as total_count
FROM trajets t
JOIN trajet_utilisateur tu ON t.id = tu.trajet_id
LEFT JOIN reservations r ON t.id = r.trajet_id
WHERE r.utilisateur_id = :utilisateur_id
AND (r.statut != 'termine' OR r.statut IS NULL)";

$stmt_chauffeur = $pdo->prepare($sql_chauffeur . " LIMIT :limit OFFSET :offset");
$stmt_chauffeur->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
$stmt_chauffeur->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt_chauffeur->bindParam(':offset', $offset, PDO::PARAM_INT);

$stmt_passager = $pdo->prepare($sql_passager . " LIMIT :limit OFFSET :offset");
$stmt_passager->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
$stmt_passager->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt_passager->bindParam(':offset', $offset, PDO::PARAM_INT);

$stmt_chauffeur->execute();
$stmt_passager->execute();

$total_chauffeur = $stmt_chauffeur->fetch(PDO::FETCH_ASSOC)['total_count'] ?? 0;
$total_passager = $stmt_passager->fetch(PDO::FETCH_ASSOC)['total_count'] ?? 0;

$total_pages = ceil(max($total_chauffeur, $total_passager) / $limit);

// Requête SQL pour récupérer les trajets avec pagination
$sql = "SELECT DISTINCT
    t.id,
    t.date_depart,
    t.heure_depart,
    t.lieu_arrive,
    t.lieu_depart,
    COALESCE(r.statut, 'en_attente') AS statut,
    r.date_reservation,
    (
        SELECT GROUP_CONCAT(DISTINCT CONCAT(u.prenom, ' ', u.nom) SEPARATOR ' | ')
        FROM reservations r2
        JOIN utilisateurs u ON r2.utilisateur_id = u.id
        WHERE r2.trajet_id = t.id
    ) AS participants,
    (
        SELECT GROUP_CONCAT(DISTINCT CONCAT(p.prenom_passager, ' ', p.nom_passager) SEPARATOR ' | ')
        FROM passagers p
        JOIN reservations r3 ON p.reservation_id = r3.id
        WHERE r3.trajet_id = t.id
    ) AS invites,
    r.id AS id_reservation,
    CASE
        WHEN tu.utilisateur_id = :utilisateur_id THEN 'chauffeur'
        ELSE 'passager'
    END AS role
FROM trajets t
JOIN trajet_utilisateur tu ON t.id = tu.trajet_id
LEFT JOIN reservations r ON t.id = r.trajet_id
WHERE (tu.utilisateur_id = :utilisateur_id OR r.utilisateur_id = :utilisateur_id)
  AND (r.statut != 'termine' OR r.statut IS NULL)
GROUP BY t.id, r.id, tu.utilisateur_id
ORDER BY t.date_depart DESC, r.date_reservation DESC
LIMIT :limit OFFSET :offset";

$statement = $pdo->prepare($sql);
$statement->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
$statement->bindParam(':limit', $limit, PDO::PARAM_INT);
$statement->bindParam(':offset', $offset, PDO::PARAM_INT);

try {
    $statement->execute();
    $trajets = $statement->fetchAll(PDO::FETCH_ASSOC);
    error_log("Trajets récupérés : " . print_r($trajets, true));
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des trajets : " . $e->getMessage());
    echo "Erreur lors de la récupération des trajets : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}
?>

<div class="container-fluid py-4">
    <h2 class="text-center mb-4 border-bottom pb-2">Mes trajets à venir</h2>
    
    <div class="row g-4">
        <!-- Section Chauffeur -->
        <div class="col-md-6">
            <div class="border rounded-3 p-3 h-100 bg-white">
                <h3 class="text-center py-2 mb-4 border-bottom">Trajets en tant que chauffeur</h3>
                <div class="card-body">
                    <?php
                    $trajets_chauffeur = array_slice(
    array_filter($trajets, fn($trajet) => $trajet['role'] === 'chauffeur'),
    0,
    2
);
                    if (!empty($trajets_chauffeur)): ?>
                        <?php foreach ($trajets_chauffeur as $trajet): ?>
                            <div class="card mb-3" id="trajet-<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Trajet n° <?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?></h5>
                                    <span class="badge bg-<?= $trajet['statut'] === 'en_attente' ? 'warning' : ($trajet['statut'] === 'en_cours' ? 'info' : 'success') ?>">
                                        <?= ucfirst($trajet['statut']) ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold"><?= htmlspecialchars($trajet['lieu_depart'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <i class="fas fa-arrow-right mx-2"></i>
                                            <span class="fw-bold"><?= htmlspecialchars($trajet['lieu_arrive'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                        <div class="small text-muted">
                                            <p class="mb-1"><i class="fas fa-calendar me-2"></i><?= htmlspecialchars($trajet['date_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                                            <p class="mb-1"><i class="fas fa-clock me-2"></i><?= htmlspecialchars($trajet['heure_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                                            <?php if (!empty($trajet['participants'])): ?>
                                                <p class="mb-1"><i class="fas fa-users me-2"></i>Réservé par : <?= htmlspecialchars($trajet['participants'], ENT_QUOTES, 'UTF-8') ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($trajet['invites'])): ?>
                                                <p class="mb-1"><i class="fas fa-user-friends me-2"></i>Ses invités : <?= htmlspecialchars($trajet['invites'], ENT_QUOTES, 'UTF-8') ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php if ($trajet['statut'] === 'en_attente'): ?>
                                            <?php if (!empty($trajet['participants'])): ?>
                                                <form method="POST" action="lancerTrajet.php" class="me-2">
                                                    <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="utilisateur_id" value="<?= htmlspecialchars($_SESSION['utilisateur']['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <button type="submit" class="btn btn-success btn-sm">Lancer le trajet</button>
                                                </form>
                                                <form method="POST" action="annulerTrajet.php" onsubmit="return confirmAnnulation(event)" class="me-2">
                                                    <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="role" value="conducteur">
                                                    <button type="submit" class="btn btn-danger btn-sm">Annuler la réservation</button>
                                                </form>
                                            <?php else: ?>
                                                <div class="alert alert-warning">Impossible de lancer le trajet sans réservation</div>
                                            <?php endif; ?>
                                        <?php elseif ($trajet['statut'] === 'en_cours'): ?>
                                            <form method="POST" action="terminerTrajet.php" class="me-2">
                                                <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Terminer le trajet</button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="detailsTrajet.php?trajet_id=<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm">Détails</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">Aucun trajet prévu en tant que chauffeur.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Section Passager -->
        <div class="col-md-6">
            <div class="border rounded-3 p-3 h-100 bg-white">
                <h3 class="text-center py-2 mb-4 border-bottom">Trajets en tant que passager</h3>
                <div class="card-body">
                    <?php
                    $trajets_passager = array_slice(
    array_filter($trajets, fn($trajet) => $trajet['role'] === 'passager'),
    0,
    2
);
                    if (!empty($trajets_passager)): ?>
                        <?php foreach ($trajets_passager as $trajet): ?>
                            <div class="card mb-3" id="trajet-<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Trajet n° <?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?></h5>
                                    <span class="badge bg-<?= $trajet['statut'] === 'en_attente' ? 'warning' : ($trajet['statut'] === 'en_cours' ? 'info' : 'success') ?>">
                                        <?= ucfirst($trajet['statut']) ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold"><?= htmlspecialchars($trajet['lieu_depart'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <i class="fas fa-arrow-right mx-2"></i>
                                            <span class="fw-bold"><?= htmlspecialchars($trajet['lieu_arrive'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                        <div class="small text-muted">
                                            <p class="mb-1"><i class="fas fa-calendar me-2"></i><?= htmlspecialchars($trajet['date_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                                            <p class="mb-1"><i class="fas fa-clock me-2"></i><?= htmlspecialchars($trajet['heure_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                                            <?php if (!empty($trajet['invites'])): ?>
                                                <p class="mb-1"><i class="fas fa-user-friends me-2"></i>Vos invités : <?= htmlspecialchars($trajet['invites'], ENT_QUOTES, 'UTF-8') ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php if ($trajet['statut'] === 'en_attente'): ?>
                                            <form method="POST" action="annulerTrajet.php" onsubmit="return confirmAnnulation(event)" class="me-2">
                                                <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="role" value="passager">
                                                <button type="submit" class="btn btn-danger btn-sm">Annuler la réservation</button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="detailsTrajet.php?trajet_id=<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm">Détails</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">Aucune réservation en tant que passager.</div>
                    <?php endif; ?>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>

<!-- Pagination -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <nav aria-label="Navigation des trajets" class="mt-4">
                <ul class="pagination justify-content-center gap-2">
                    <?php if($total_pages > 1): ?>
                        <!-- Bouton précédent -->
                        <li class="page-item">
                            <a class="btn <?= ($page <= 1) ? 'btn-secondary disabled' : 'btn-primary' ?>" 
                               href="?page=<?= $page - 1 ?>" 
                               <?= ($page <= 1) ? 'tabindex="-1" aria-disabled="true"' : '' ?>>Précédent</a>
                        </li>
                        
                        <!-- Numéros de page -->
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item">
                                <a class="btn <?= ($page == $i) ? 'btn-primary' : 'btn-outline-primary' ?>" 
                                   href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- Bouton suivant -->
                        <li class="page-item">
                            <a class="btn <?= ($page >= $total_pages) ? 'btn-secondary disabled' : 'btn-primary' ?>" 
                               href="?page=<?= $page + 1 ?>" 
                               <?= ($page >= $total_pages) ? 'tabindex="-1" aria-disabled="true"' : '' ?>>Suivant</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const messageContainer = document.querySelector('.message-container');
    if (messageContainer) {
        setTimeout(() => {
            messageContainer.remove();
        }, 5000);
    }
});

function confirmAnnulation(event) {
    event.preventDefault();
    if (confirm("Êtes-vous sûr de vouloir annuler ce trajet ?")) {
        event.target.submit();
    }
}
</script>

<?php
require_once('templates/footer.php');
?>