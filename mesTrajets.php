<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    $utilisateur_id = $_SESSION['utilisateur']['id'];
    echo "<div class='alert alert-info'>L'utilisateur connecté a l'ID : " . htmlspecialchars($utilisateur_id, ENT_QUOTES, 'UTF-8') . "</div>";
} else {
    // L'utilisateur n'est pas connecté
    echo "Utilisateur non connecté.";
    exit;
}

if (isset($_SESSION['message'])) {
    $alertClass = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
    echo "<div class='alert alert-{$alertClass} message-container'>" . htmlspecialchars($_SESSION['message']) . "</div>";
    // On supprime le message après l'avoir affiché
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Préparer la requête pour récupérer les trajets de l'utilisateur connecté depuis la table reservations
$sql = "SELECT
    t.id,
    t.date_depart,
    t.heure_depart,
    t.lieu_arrive,
    t.lieu_depart,
    MAX(r.statut) AS statut,
    GROUP_CONCAT(DISTINCT CONCAT(p.prenom_passager, ' ', p.nom_passager) SEPARATOR ', ') AS participants,
    r.id AS id_reservation,  -- Récupérer l'ID de réservation
    u.role AS role
FROM trajets t
JOIN reservations r ON t.id = r.trajet_id
LEFT JOIN passagers p ON r.id = p.reservation_id
JOIN utilisateurs u ON r.utilisateur_id = u.id
WHERE r.utilisateur_id = :utilisateur_id
  AND r.statut != 'terminé'
GROUP BY t.id, r.id  -- Ajoutez ici r.id pour résoudre l'erreur
ORDER BY u.role, t.date_depart;
";



$stmt = $pdo->prepare($sql);
$stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);

try {
    $stmt->execute();
    $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En cas d'erreur SQL, afficher un message et arrêter le script
    echo "Erreur lors de la récupération des trajets : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}
?>




<main class="form-page">
    <section class="custom-section2">
        <div class="colonnesConducPassagers">
            <h4>Mes trajets à venir</h4>

            <div class="row">
                <!-- Section pour les trajets en tant que conducteur -->
                <div class="col-md-6">
                    <h5>Trajets en tant que conducteur</h5>
                    <?php
                    $trajets_conducteur = array_filter($trajets, fn($trajet) => $trajet['role'] === 'conducteur');
                    if (!empty($trajets_conducteur)): ?>
                        <?php foreach ($trajets_conducteur as $trajet): ?>
                            <div class="vehicule givre" id="trajet-<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <!-- Affichage de l'ID, Lieu de départ, Flèche et Lieu d'arrivée -->
                                <div class="titleTrajets">
                                    <p> Trajet n° : <?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <span class="trajet-info"><?= htmlspecialchars($trajet['lieu_depart'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="arrow"> &gt; </span>
                                    <span class="trajet-info"><?= htmlspecialchars($trajet['lieu_arrive'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <p>Date : <?= htmlspecialchars($trajet['date_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p>Heure de départ : <?= htmlspecialchars($trajet['heure_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p>Participants : <?= !empty($trajet['participants']) ? htmlspecialchars($trajet['participants'], ENT_QUOTES, 'UTF-8') : 'Aucun participant'; ?></p>
                                <a href="detailsTrajet.php?trajet_id=<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">Voir les détails</a>
                                <?php if ($trajet['statut'] == 'en_attente'): ?>
                                    <form id="lancer-form-<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>" action="lancerTrajet.php" method="post" style="display:inline;">
                                        <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="utilisateur_id" value="<?= htmlspecialchars($utilisateur_id, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="btn btn-success lancer-btn">Lancer le trajet</button>
                                    </form>
                                <?php elseif ($trajet['statut'] == 'en_cours'): ?>
                                    <form id="terminer-form-<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>" action="terminerTrajet.php" method="post" style="display:inline;">
                                        <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="btn btn-danger terminer-btn">Terminer le trajet</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="annulerTrajet.php" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler ce trajet ? Tous les passagers seront notifiés.');">
                <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="role" value="conducteur">
                <button type="submit" class="btn btn-danger">Annuler le trajet</button>
            </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: white; font-size: 20px;">Vous n'êtes conducteur d'aucun trajet.</p>
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
                                <!-- Affichage de l'ID, Lieu de départ, Flèche et Lieu d'arrivée -->
                                <p>
                                    <strong>ID du trajet : <?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span class="trajet-info"><?= htmlspecialchars($trajet['lieu_depart'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="arrow"> &gt; </span>
                                    <span class="trajet-info"><?= htmlspecialchars($trajet['lieu_arrive'], ENT_QUOTES, 'UTF-8') ?></span>
                                </p>
                                <p>Date : <?= htmlspecialchars($trajet['date_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p>Heure de départ : <?= htmlspecialchars($trajet['heure_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p>Participants : <?= !empty($trajet['participants']) ? htmlspecialchars($trajet['participants'], ENT_QUOTES, 'UTF-8') : 'Aucun participant'; ?></p>
                                <a href="detailsTrajet.php?trajet_id=<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">Voir les détails</a>

                                <!-- Permettre à un passager de terminer un trajet si le statut est 'en_cours' -->
                                <?php if ($trajet['statut'] == 'en_cours' && $trajet['role'] == 'passager'): ?>
                                    <form id="terminer-form-<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>" action="terminerTrajet.php" method="post" style="display:inline;">
                                        <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="utilisateur_id" value="<?= htmlspecialchars($utilisateur_id, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="btn btn-danger terminer-btn">Terminer le trajet</button>
                                    </form>
                                <?php endif; ?>







                                <!-- Ajouter un bouton "Lancer le trajet" pour les passagers (optionnel) -->
                                <?php if ($trajet['statut'] == 'en_attente' && $trajet['role'] == 'passager'): ?>
                                    <form id="lancer-form-<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>" action="lancerTrajet.php" method="post" style="display:inline;">
                                        <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="utilisateur_id" value="<?= htmlspecialchars($utilisateur_id, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="btn btn-success lancer-btn">Lancer le trajet</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="annulerTrajet.php" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler votre réservation ?');">
                <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="role" value="passager">
                <button type="submit" class="btn btn-danger">Annuler la réservation</button>
            </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: white; font-size: 20px;">Vous n'êtes passager d'aucun trajet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gérer le clic sur le bouton "Lancer le trajet"
        document.querySelectorAll('.lancer-btn').forEach(function(button) {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                const form = event.target.closest('form');
                const formData = new FormData(form);
                const trajetId = formData.get('trajet_id');

                // Vérification : assurez-vous que l'ID du trajet est bien récupéré
                console.log("Trajet ID :", trajetId); // Ajoutez cette ligne pour déboguer

                // Soumettre le formulaire si l'ID du trajet est valide
                if (trajetId) {
                    form.submit();
                } else {
                    alert("Trajet ID manquant !");
                }
            });
        });

        // Gérer le clic sur le bouton "Terminer le trajet"
        document.querySelectorAll('.terminer-btn').forEach(function(button) {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                const form = event.target.closest('form');
                const formData = new FormData(form);
                const trajetId = formData.get('trajet_id');

                // Vérification : assurez-vous que l'ID du trajet est bien récupéré
                console.log("Trajet ID :", trajetId); // Ajoutez cette ligne pour déboguer

                // Soumettre le formulaire si l'ID du trajet est valide
                if (trajetId) {
                    form.submit();
                } else {
                    alert("Trajet ID manquant !");
                }
            });
        });
    });



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