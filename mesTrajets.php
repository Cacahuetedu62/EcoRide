<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

// Vérification session
if (!isset($_SESSION['utilisateur']) || !isset($_SESSION['utilisateur']['id'])) {
    header('Location: login.php');
    exit();
}

$utilisateur_id = (int)$_SESSION['utilisateur']['id'];
if (!$utilisateur_id) {
    die("ID utilisateur invalide");
}

// Messages de session
if (isset($_SESSION['message'])) {
    $alertClass = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
    echo "<div class='alert alert-{$alertClass} message-container'>" . htmlspecialchars($_SESSION['message']) . "</div>";
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Requête trajets
$sql = "SELECT DISTINCT
    t.id,
    t.date_depart,
    t.heure_depart,
    t.lieu_arrive,
    t.lieu_depart,
    COALESCE(r.statut, 'en_attente') as statut,
    GROUP_CONCAT(DISTINCT CONCAT(u_passager.prenom, ' ', u_passager.nom) SEPARATOR ' | ') AS participants,
    GROUP_CONCAT(DISTINCT CONCAT(p.prenom_passager, ' ', p.nom_passager) SEPARATOR ' | ') AS invites,
    r.id AS id_reservation,
    tu.utilisateur_id = :utilisateur_id AS is_conducteur
FROM trajets t
JOIN trajet_utilisateur tu ON t.id = tu.trajet_id
LEFT JOIN reservations r ON t.id = r.trajet_id
LEFT JOIN passagers p ON r.id = p.reservation_id
LEFT JOIN utilisateurs u_passager ON p.utilisateur_id = u_passager.id
WHERE tu.utilisateur_id = :utilisateur_id 
  OR r.utilisateur_id = :utilisateur_id
GROUP BY t.id, r.id, tu.utilisateur_id
HAVING statut != 'termine' OR statut IS NULL
ORDER BY t.date_depart ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
    $stmt->execute();
    $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur SQL: " . $e->getMessage());
    die("Une erreur est survenue lors de la récupération des trajets.");
}
?>

<section>
    <div>
        <h4 class="section-title">Mes trajets à venir</h4>

        <div class="row p-3">
            <!-- Trajets conducteur -->
            <div class="col-md-6">
                <h5 class="trajet-title">Trajets en tant que conducteur</h5>
                <?php
                $trajets_conducteur = array_filter($trajets, fn($t) => $t['is_conducteur']);
                if (!empty($trajets_conducteur)): ?>
                    <?php foreach ($trajets_conducteur as $trajet): ?>
                        <div class="trajet-card" id="trajet-<?= htmlspecialchars($trajet['id']) ?>">
                            <div class="trajet-header">
                                <span class="badge <?= $trajet['statut'] ? 'badge-'.htmlspecialchars($trajet['statut']) : 'badge-en_attente' ?>">
                                    <?= ucfirst(str_replace('_', ' ', $trajet['statut'] ?? 'en attente')) ?>
                                </span>
                            </div>

                            <div class="trajet-body">
                                <p><strong>Trajet n° : <?= htmlspecialchars($trajet['id']) ?></strong></p>
                                <p>
                                    <div class="trajet-destinations">
                                        <span class="svg-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM5 5h14v2H5V5zm14 14H5V9h14v10z"/>
                                            </svg>
                                        </span>
                                        <?= htmlspecialchars($trajet['lieu_depart']) ?>
                                        <span>→</span>
                                        <?= htmlspecialchars($trajet['lieu_arrive']) ?>
                                    </div>
                                </p>
                                <p>Date : <?= htmlspecialchars($trajet['date_depart']) ?></p>
                                <p>Heure : <?= htmlspecialchars($trajet['heure_depart']) ?></p>

                                <p><strong>Participants :</strong><br>
                                <?= htmlspecialchars($trajet['participants'] ?: 'Aucun participant') ?></p>

                                <p><strong>Invités :</strong><br>
                                <?= htmlspecialchars($trajet['invites'] ?: 'Aucun invité') ?></p>

                                <div class="trajet-actions">
                                    <a href="detailsTrajet.php?trajet_id=<?= htmlspecialchars($trajet['id']) ?>"
                                       class="btn btn-primary">Voir détails</a>

                                    <?php if ($trajet['statut'] != 'en_cours'): ?>
                                        <form method="POST" action="annulerTrajet.php"
                                              onsubmit="return confirm('Confirmer l\'annulation ? Les passagers seront notifiés.');">
                                            <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id']) ?>">
                                            <input type="hidden" name="role" value="conducteur">
                                            <button type="submit" class="btn btn-danger">Annuler trajet</button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled>Trajet en cours</button>
                                    <?php endif; ?>

                                    <?php if ($trajet['statut'] === 'en_attente'): ?>
    <form method="POST" action="lancerTrajet.php">
        <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id']) ?>">
        <button type="submit" class="btn btn-success">Lancer trajet</button>
    </form>
<?php elseif ($trajet['statut'] === 'en_cours'): ?>
    <form method="POST" action="terminerTrajet.php">
        <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id']) ?>">
        <button type="submit" class="btn btn-warning">Terminer trajet</button>
    </form>
<?php endif; ?>                                 

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-trajet">Vous n'êtes conducteur d'aucun trajet.</p>
                <?php endif; ?>
            </div>

            <!-- Trajets passager -->
            <div class="col-md-6">
                <h5 class="trajet-title">Trajets en tant que passager</h5>
                <?php
                $trajets_passager = array_filter($trajets, fn($t) => !$t['is_conducteur']);
                if (!empty($trajets_passager)): ?>
                    <?php foreach ($trajets_passager as $trajet): ?>
                        <div class="trajet-card" id="trajet-<?= htmlspecialchars($trajet['id']) ?>">
                            <div class="trajet-header">
                                <span class="badge <?= $trajet['statut'] ? 'badge-'.htmlspecialchars($trajet['statut']) : 'badge-default' ?>">
                                    <?= ucfirst(str_replace('_', ' ', $trajet['statut'] ?? 'en attente')) ?>
                                </span>
                            </div>

                            <div class="trajet-body">
                                <p>
                                    <strong>Trajet n° <?= htmlspecialchars($trajet['id']) ?></strong><br>
                                </p>
                                <div class="trajet-body">
                                    <p>
                                        <div class="trajet-destinations">
                                            <span class="svg-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-6h2v6zm0-8h-2V7h2v4z"/>
                                                </svg>
                                            </span>
                                            <?= htmlspecialchars($trajet['lieu_depart']) ?>
                                            <span>→</span>
                                            <?= htmlspecialchars($trajet['lieu_arrive']) ?>
                                        </div>
                                    </p>
                                    <p>
                                        <span class="svg-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM5 5h14v2H5V5zm14 14H5V9h14v10z"/>
                                            </svg>
                                        </span>
                                        Date : <?= htmlspecialchars($trajet['date_depart']) ?>
                                    </p>
                                    <p>
                                        <span class="svg-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-6h2v6zm0-8h-2V7h2v4z"/>
                                            </svg>
                                        </span>
                                        Heure : <?= htmlspecialchars($trajet['heure_depart']) ?>
                                    </p>
                                </div>

                                <p><strong>Participants :</strong><br>
                                <?= htmlspecialchars($trajet['participants'] ?: 'Aucun participant') ?></p>

                                <p><strong>Invités :</strong><br>
                                <?= htmlspecialchars($trajet['invites'] ?: 'Aucun invité') ?></p>

                                <div class="trajet-actions">
                                    <a href="detailsTrajet.php?trajet_id=<?= htmlspecialchars($trajet['id']) ?>"
                                       class="btn btn-primary">Voir détails</a>

                                    <?php if ($trajet['statut'] != 'en_cours'): ?>
                                        <form method="POST" action="annulerTrajet.php"
                                              onsubmit="return confirm('Confirmer l\'annulation de votre réservation ?');">
                                            <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id']) ?>">
                                            <input type="hidden" name="role" value="passager">
                                            <button type="submit" class="btn btn-danger">Annuler réservation</button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled>Trajet en cours</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-trajet">Vous n'êtes passager d'aucun trajet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const messageContainer = document.querySelector('.message-container');
    if (messageContainer) {
        setTimeout(() => messageContainer.remove(), 5000);
    }
});
</script>

<?php require_once('templates/footer.php'); ?>
