<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

// Vérifie si l'utilisateur est connecté
if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    $utilisateur_id = $_SESSION['utilisateur']['id'];
} else {
    exit;
}

// Paramètres de pagination
$trajets_par_page = 3; // Nombre de trajets par page
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($page - 1) * $trajets_par_page;

// Requête SQL pour récupérer l'historique des trajets avec pagination
$sql = "SELECT DISTINCT
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
       LIMIT :offset, :trajets_par_page";

$statement = $pdo->prepare($sql);
$statement->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
$statement->bindParam(':offset', $offset, PDO::PARAM_INT);
$statement->bindParam(':trajets_par_page', $trajets_par_page, PDO::PARAM_INT);

try {
    $statement->execute();
    $trajets = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Suppression du echo pour le débug, pas d'affichage des erreurs
    exit;
}

// Requête pour obtenir le nombre total de trajets pour la pagination
$sql_count = "SELECT COUNT(DISTINCT t.id) AS total
              FROM trajets t
              JOIN trajet_utilisateur tu ON t.id = tu.trajet_id
              LEFT JOIN reservations r ON t.id = r.trajet_id
              WHERE (tu.utilisateur_id = :utilisateur_id OR r.utilisateur_id = :utilisateur_id)
              AND r.statut = 'terminé'";

$statement_count = $pdo->prepare($sql_count);
$statement_count->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);

try {
    $statement_count->execute();
    $total_trajets = $statement_count->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_trajets / $trajets_par_page);
} catch (PDOException $e) {
    // Suppression du echo pour le débug, pas d'affichage des erreurs
    exit;
}

function renderTrajetCard($trajet) {
    ?>
    <div class="trajet-card">
        <div class="trajet-header">
            <h5>Trajet n° <?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?></h5>
            <span class="badge badge-<?= htmlspecialchars($trajet['statut'], ENT_QUOTES, 'UTF-8') ?>">
                <?= ucfirst($trajet['statut']) ?>
            </span>
        </div>
        <div class="trajet-body">
            <p><i class="fas fa-map-marker-alt" aria-hidden="true"></i> De : <?= htmlspecialchars($trajet['lieu_depart'], ENT_QUOTES, 'UTF-8') ?> → <?= htmlspecialchars($trajet['lieu_arrive'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><i class="fas fa-calendar" aria-hidden="true"></i> Le : <?= htmlspecialchars($trajet['date_depart'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><i class="fas fa-clock" aria-hidden="true"></i> Heure : <?= htmlspecialchars($trajet['heure_depart'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php if (!empty($trajet['participants'])): ?>
                <p><i class="fas fa-users" aria-hidden="true"></i> Participants : <?= htmlspecialchars($trajet['participants'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?>

<h2 class="section-title">Historique des trajets</h2>
<div class="trajets-container">
    <div class="trajets-column">
        <h3 class="trajet-title">Trajets en tant que chauffeur</h3>
        <?php
        $trajets_chauffeur = array_filter($trajets, fn($trajet) => $trajet['role'] === 'chauffeur');
        if (!empty($trajets_chauffeur)) {
            foreach ($trajets_chauffeur as $trajet) {
                renderTrajetCard($trajet);
            }
        } else {
            echo '<p>Aucun trajet enregistré en tant que chauffeur.</p>';
        }
        ?>
    </div>
    <div class="trajets-column">
        <h3 class="trajet-title">Trajets en tant que passager</h3>
        <?php
        $trajets_passager = array_filter($trajets, fn($trajet) => $trajet['role'] === 'passager');
        if (!empty($trajets_passager)) {
            foreach ($trajets_passager as $trajet) {
                renderTrajetCard($trajet);
            }
        } else {
            echo '<p>Aucun trajet enregistré en tant que passager.</p>';
        }
        ?>
    </div>
</div>

<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>" aria-label="Précédent">&laquo; Précédent</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>" aria-label="Page <?= $i ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>

    <?php if ($page < $total_pages): ?>
        <a href="?page=<?= $page + 1 ?>" aria-label="Suivant">Suivant &raquo;</a>
    <?php endif; ?>
</div>

<?php
require_once('templates/footer.php');
?>