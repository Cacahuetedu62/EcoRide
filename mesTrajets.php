<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

// Vérifie si l'utilisateur est connecté
if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    $utilisateur_id = $_SESSION['utilisateur']['id'];
    echo "<div class='alert alert-info'>L'utilisateur connecté a l'ID : " . htmlspecialchars($utilisateur_id, ENT_QUOTES, 'UTF-8') . "</div>";
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

// Requête SQL pour récupérer les trajets
$sql = "SELECT DISTINCT
           t.id,
           t.date_depart,
           t.heure_depart,
           t.lieu_arrive,
           t.lieu_depart,
           COALESCE(r.statut, 'en_attente') AS statut,
           GROUP_CONCAT(DISTINCT CONCAT(u_passager.prenom, ' ', u_passager.nom) SEPARATOR ' | ') AS participants,
           GROUP_CONCAT(DISTINCT CONCAT(p.nom_passager, ' ', p.prenom_passager) SEPARATOR ' | ') AS invites,
           r.id AS id_reservation,
           CASE
               WHEN tu.utilisateur_id = :utilisateur_id THEN 'chauffeur'
               ELSE 'passager'
           END AS role
       FROM trajets t
       JOIN trajet_utilisateur tu ON t.id = tu.trajet_id
       LEFT JOIN reservations r ON t.id = r.trajet_id
       LEFT JOIN utilisateurs u_passager ON r.utilisateur_id = u_passager.id
       LEFT JOIN passagers p ON r.id = p.reservation_id
       WHERE (tu.utilisateur_id = :utilisateur_id OR r.utilisateur_id = :utilisateur_id)
         AND (r.statut != 'termine' OR r.statut IS NULL)
       GROUP BY t.id, r.id, tu.utilisateur_id
       ORDER BY t.date_depart";

$statement = $pdo->prepare($sql);
$statement->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);

try {
    $statement->execute();
    $trajets = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erreur lors de la récupération des trajets : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}
?>

<h2 class="section-title">Mes trajets à venir</h2>
<div class="trajets-container">
    <!-- Section Chauffeur -->
    <div class="trajets-column">
        <h3 class="trajet-title">Trajets en tant que chauffeur</h3>
        <?php
        $trajets_chauffeur = array_filter($trajets, fn($trajet) => $trajet['role'] === 'chauffeur');
        if (!empty($trajets_chauffeur)): ?>
            <?php foreach ($trajets_chauffeur as $trajet): ?>
                <div class="trajet-card" id="trajet-<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="trajet-header">
                        <h5>Trajet n° <?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?></h5>
                        <span class="badge badge-<?= $trajet['statut'] ?>">
                            <?= ucfirst($trajet['statut']) ?>
                        </span>
                    </div>
                    <div class="trajet-body">
                        <div class="trajet-destinations">
                            <span class="destination-depart"><?= htmlspecialchars($trajet['lieu_depart'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="destination-arrow">→</span>
                            <span class="destination-arrivee"><?= htmlspecialchars($trajet['lieu_arrive'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="trajet-details">
                            <p><i class="fas fa-calendar"></i> <?= htmlspecialchars($trajet['date_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p><i class="fas fa-clock"></i> <?= htmlspecialchars($trajet['heure_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php if (!empty($trajet['participants'])): ?>
                                <p><i class="fas fa-users"></i>Reservé par : <?= htmlspecialchars($trajet['participants'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <?php if (!empty($trajet['invites'])): ?>
                                <p><i class="fas fa-user-friends"></i> Ses invités : <?= htmlspecialchars($trajet['invites'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="trajet-actions">
                        <?php if ($trajet['statut'] === 'en_attente'): ?>
    <?php if (!empty($trajet['participants'])): ?>
        <form method="POST" action="lancerTrajet.php">
            <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="utilisateur_id" value="<?= htmlspecialchars($_SESSION['utilisateur']['id'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-success">Lancer le trajet</button>
        </form>
    <?php else: ?>
        <div class="alert alert-warning">Impossible de lancer le trajet sans réservation</div>
    <?php endif; ?>

                            <?php elseif ($trajet['statut'] === 'en_cours'): ?>
                                <form method="POST" action="terminerTrajet.php">
                                    <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn btn-danger">Terminer le trajet</button>
                                </form>
                            <?php endif; ?>
                            <a href="detailsTrajet.php?trajet_id=<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">Détails</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-trajet">
                <p>Aucun trajet prévu en tant que chauffeur.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Section Passager -->
    <div class="trajets-column">
        <h3 class="trajet-title">Trajets en tant que passager</h3>
        <?php
        $trajets_passager = array_filter($trajets, fn($trajet) => $trajet['role'] === 'passager');
        if (!empty($trajets_passager)): ?>
            <?php foreach ($trajets_passager as $trajet): ?>
                <div class="trajet-card" id="trajet-<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="trajet-header">
                        <h5>Trajet n° <?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?></h5>
                        <span class="badge badge-<?= $trajet['statut'] ?>">
                            <?= ucfirst($trajet['statut']) ?>
                        </span>
                    </div>
                    <div class="trajet-body">
                        <div class="trajet-destinations">
                            <span class="destination-depart"><?= htmlspecialchars($trajet['lieu_depart'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="destination-arrow">→</span>
                            <span class="destination-arrivee"><?= htmlspecialchars($trajet['lieu_arrive'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="trajet-details">
                            <p><i class="fas fa-calendar"></i> <?= htmlspecialchars($trajet['date_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p><i class="fas fa-clock"></i> <?= htmlspecialchars($trajet['heure_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php if (!empty($trajet['invites'])): ?>
                                <p><i class="fas fa-user-friends"></i> Vos invités : <?= htmlspecialchars($trajet['invites'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="trajet-actions">
                            <?php if ($trajet['statut'] === 'en_attente'): ?>
                                <form method="POST" action="annulerReservation.php">
                                    <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn btn-danger">Annuler la réservation</button>
                                </form>
                            <?php endif; ?>
                            <a href="detailsTrajet.php?trajet_id=<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">Détails</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-trajet">
                <p>Aucune réservation en tant que passager.</p>
            </div>
        <?php endif; ?>
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
</script>

<?php
require_once('templates/footer.php');
?>
