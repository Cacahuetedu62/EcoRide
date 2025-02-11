<?php
require_once('templates/header.php');

// Générer un jeton CSRF si non existant
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    $utilisateur_id = $_SESSION['utilisateur']['id'];
} else {
    exit; // Arrêter l'exécution si l'utilisateur n'est pas connecté
}

// Paramètres de pagination
$trajets_par_page = 2; // Nombre de trajets par page
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($page - 1) * $trajets_par_page;

// Requête SQL pour récupérer l'historique des trajets avec pagination
$sql = "
    SELECT DISTINCT
        t.id,
        t.date_depart,
        t.heure_depart,
        t.lieu_depart,
        t.lieu_arrive,
        COALESCE(r.statut, 'termine') AS statut,
        GROUP_CONCAT(DISTINCT CONCAT(u_passager.prenom, ' ', u_passager.nom) SEPARATOR ' | ') AS participants,
        CASE
            WHEN tu.utilisateur_id = :utilisateur_id THEN 'chauffeur'
            ELSE 'passager'
        END AS role
    FROM trajets t
    JOIN trajet_utilisateur tu ON t.id = tu.trajet_id
    LEFT JOIN reservations r ON t.id = r.trajet_id
    LEFT JOIN utilisateurs u_passager ON r.utilisateur_id = u_passager.id
    WHERE (tu.utilisateur_id = :utilisateur_id OR r.utilisateur_id = :utilisateur_id)
    AND r.statut = 'terminé'
    GROUP BY t.id, tu.utilisateur_id, r.statut
    ORDER BY t.date_depart DESC
    LIMIT :offset, :trajets_par_page
";

$statement = $pdo->prepare($sql);
$statement->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
$statement->bindParam(':offset', $offset, PDO::PARAM_INT);
$statement->bindParam(':trajets_par_page', $trajets_par_page, PDO::PARAM_INT);

try {
    $statement->execute();
    $trajets = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    exit;
}

// Requête pour obtenir le nombre total de trajets pour la pagination
$sql_count = "
    SELECT COUNT(DISTINCT t.id) AS total
    FROM trajets t
    JOIN trajet_utilisateur tu ON t.id = tu.trajet_id
    LEFT JOIN reservations r ON t.id = r.trajet_id
    WHERE (tu.utilisateur_id = :utilisateur_id OR r.utilisateur_id = :utilisateur_id)
    AND r.statut = 'terminé'
";

$statement_count = $pdo->prepare($sql_count);
$statement_count->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);

try {
    $statement_count->execute();
    $total_trajets = $statement_count->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_trajets / $trajets_par_page);
} catch (PDOException $e) {
    exit;
}
?>

<div class="container-fluid py-4">
    <h2 class="text-center mb-4 border-bottom pb-2">Historique des trajets</h2>
    
    <div class="row g-4">
        <!-- Section Chauffeur -->
        <div class="col-md-6">
            <div class="border rounded-3 p-3 h-100 bg-white">
                <h3 class="text-center py-2 mb-4 border-bottom">Trajets en tant que chauffeur</h3>
                <?php
                $trajets_chauffeur = array_filter($trajets, fn($trajet) => $trajet['role'] === 'chauffeur');
                if (!empty($trajets_chauffeur)) {
                    foreach ($trajets_chauffeur as $trajet) {
                        ?>
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Trajet n° <?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?></h5>
                                <span class="badge bg-warning"><?= ucfirst($trajet['statut']) ?></span>
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
                                            <p class="mb-1"><i class="fas fa-users me-2"></i>Participants : <?= htmlspecialchars($trajet['participants'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="alert alert-info">Aucun trajet enregistré en tant que chauffeur.</div>';
                }
                ?>
            </div>
        </div>

        <!-- Section Passager -->
        <div class="col-md-6">
            <div class="border rounded-3 p-3 h-100 bg-white">
                <h3 class="text-center py-2 mb-4 border-bottom">Trajets en tant que passager</h3>
                <?php
                $trajets_passager = array_filter($trajets, fn($trajet) => $trajet['role'] === 'passager');
                if (!empty($trajets_passager)) {
                    foreach ($trajets_passager as $trajet) {
                        ?>
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Trajet n° <?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?></h5>
                                <span class="badge bg-warning"><?= ucfirst($trajet['statut']) ?></span>
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
                                            <p class="mb-1"><i class="fas fa-users me-2"></i>Participants : <?= htmlspecialchars($trajet['participants'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="alert alert-info">Aucun trajet enregistré en tant que passager.</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <nav aria-label="Navigation des trajets" class="mt-4">
        <ul class="pagination justify-content-center gap-2">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="btn btn-primary" href="?page=<?= $page - 1 ?>">Précédent</a>
                </li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item">
                    <a class="btn <?= $i == $page ? 'btn-primary' : 'btn-outline-primary' ?>" href="?page=<?= $i ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="btn btn-primary" href="?page=<?= $page + 1 ?>">Suivant</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<?php require_once('templates/footer.php'); ?>