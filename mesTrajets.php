<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    $utilisateur_id = $_SESSION['utilisateur']['id'];
    echo "<div class='alert alert-info'>L'utilisateur connecté a l'ID : " . htmlspecialchars($utilisateur_id, ENT_QUOTES, 'UTF-8') . "</div>";
} else {
    echo "Utilisateur non connecté.";
    exit;
}

if (isset($_SESSION['message'])) {
    $alertClass = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
    echo "<div class='alert alert-{$alertClass} message-container'>" . htmlspecialchars($_SESSION['message']) . "</div>";
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

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


$stmt = $pdo->prepare($sql);
$stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);

try {
    $stmt->execute();
    $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erreur lors de la récupération des trajets : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}
?>

<main class="form-page">
    <section class="custom-section2">
        <div class="colonnesConducPassagers">
            <h4>Mes trajets à venir</h4>

            <div class="row">
                <!-- Section pour les trajets en tant que chauffeur -->
                <div class="col-md-6">
                    <h5>Trajets en tant que chauffeur</h5>
                    <?php
                    $trajets_chauffeur = array_filter($trajets, fn($trajet) => $trajet['role'] === 'chauffeur');
                    if (!empty($trajets_chauffeur)): ?>
                        <?php foreach ($trajets_chauffeur as $trajet): ?>
                            <div class="vehicule givre" id="trajet-<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <div class="titleTrajets">
                                    <p>Trajet n° : <?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <span class="trajet-info">Lieu de départ : <?= htmlspecialchars($trajet['lieu_depart'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="arrow"> &gt; </span>
                                    <span class="trajet-info">Arrivée : <?= htmlspecialchars($trajet['lieu_arrive'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <p>Date : <?= htmlspecialchars($trajet['date_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p>Heure : <?= htmlspecialchars($trajet['heure_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p><strong>Participants :</strong> <?= htmlspecialchars($trajet['participants'], ENT_QUOTES, 'UTF-8') ?></p>
<?php if (!empty($trajet['invites'])): ?>
    <p><strong>Invités :</strong> <?= htmlspecialchars($trajet['invites'], ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
                                <span class="badge <?= $trajet['statut'] === 'en_attente' ? 'bg-warning' : 'bg-success' ?>">
                                    <?= ucfirst($trajet['statut']) ?>
                                </span>

                                <?php if ($trajet['statut'] === 'en_attente'): ?>
                                    <form method="POST" action="lancerTrajet.php">
    <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>"> 
    <input type="hidden" name="utilisateur_id" value="<?= htmlspecialchars($_SESSION['utilisateur']['id'], ENT_QUOTES, 'UTF-8'); ?>">
    <button type="submit">Lancer le trajet</button>
</form>


                                <?php elseif ($trajet['statut'] === 'en_cours'): ?>
                                    <form method="POST" action="terminerTrajet.php">
                                        <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="btn btn-danger">Terminer le trajet</button>
                                    </form>
                                <?php endif; ?>
                                <a href="detailsTrajet.php?trajet_id=<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">Détails</a>

                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Aucun trajet prévu en tant que chauffeur.</p>
                    <?php endif; ?>
                </div>

                <!-- Section pour les trajets en tant que passager -->
                <div class="col-md-6">
                    <h5>Trajets en tant que passager</h5>
                    <?php
                    $trajets_passager = array_filter($trajets, fn($trajet) => $trajet['role'] === 'passager');
                    if (!empty($trajets_passager)): ?>
                        <?php foreach ($trajets_passager as $trajet): ?>
                            <div class="vehicule givre" id="trajet-<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <div class="titleTrajets">
                                    <p>Trajet n° : <?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <span class="trajet-info">Lieu de départ : <?= htmlspecialchars($trajet['lieu_depart'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="arrow"> &gt; </span>
                                    <span class="trajet-info">Arrivée : <?= htmlspecialchars($trajet['lieu_arrive'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <p>Date : <?= htmlspecialchars($trajet['date_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p>Heure : <?= htmlspecialchars($trajet['heure_depart'], ENT_QUOTES, 'UTF-8') ?></p>
<?php if (!empty($trajet['invites'])): ?>
    <p><strong>Invités :</strong> <?= htmlspecialchars($trajet['invites'], ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>                                <span class="badge <?= $trajet['statut'] === 'en_attente' ? 'bg-warning' : 'bg-success' ?>">
                                    <?= ucfirst($trajet['statut']) ?>
                                </span>

                                <?php if ($trajet['statut'] === 'en_attente'): ?>
                                    <form method="POST" action="annulerReservation.php">
                                        <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="btn btn-danger">Annuler la réservation</button>
                                    </form>
                                <?php endif; ?>
                                <a href="detailsTrajet.php?trajet_id=<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">Détails</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Aucune réservation en tant que passager.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

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
