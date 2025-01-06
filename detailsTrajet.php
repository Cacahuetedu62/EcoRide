<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

// Vérification de l'ID du trajet dans l'URL
if (!isset($_GET['trajet_id']) || empty($_GET['trajet_id']) || !is_numeric($_GET['trajet_id'])) {
    echo "Aucun trajet valide spécifié.";
    exit;
}

$trajet_id = (int)$_GET['trajet_id'];

try {
    // Requête pour récupérer les détails du trajet
    $sql = "SELECT t.lieu_depart, t.date_arrive, t.heure_arrive,
                t.date_depart, t.heure_depart,
                t.lieu_arrive, t.nb_places,
                t.prix_personnes, t.preferences,
                t.fumeur, t.animaux,
                u.pseudo, u.photo, u.telephone, u.note_moyenne
            FROM trajets t
            JOIN reservations r ON t.id = r.trajet_id
            JOIN utilisateurs u ON u.id = r.utilisateur_id
            WHERE t.id = :trajet_id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
    $stmt->execute();
    $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vérifier si le trajet existe
    if (!$trajet) {
        echo "Le trajet demandé n'existe pas.";
        exit;
    }

    // Requête pour récupérer les autres participants
    $sql_participants = "SELECT u.pseudo, u.photo, u.telephone, u.note_moyenne
                         FROM reservations r
                         JOIN utilisateurs u ON u.id = r.utilisateur_id
                         WHERE r.trajet_id = :trajet_id";

    $stmt_participants = $pdo->prepare($sql_participants);
    $stmt_participants->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
    $stmt_participants->execute();
    $participants = $stmt_participants->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    echo "Erreur lors de la récupération des détails du trajet : " . $e->getMessage();
    exit;
}
?>

<main>
    <div class="container">
        <div class="row justify-content-center align-items-center mb-3">
            <div class="col-12">
                <h4 class="text-center mt-4">Détails du trajet n° <?= htmlspecialchars($trajet_id, ENT_QUOTES, 'UTF-8') ?></h4>
            </div>
        </div>

        <!-- Départ et arrivée -->
        <div class="row mb-3">
            <div class="col-md-6">
                <h4>Départ</h4>
                <p>Lieu : <?= htmlspecialchars($trajet['lieu_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                <p>Date : <?= htmlspecialchars($trajet['date_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                <p>Heure : <?= htmlspecialchars($trajet['heure_depart'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="col-md-6">
                <h4>Arrivée</h4>
                <p>Lieu : <?= htmlspecialchars($trajet['lieu_arrive'], ENT_QUOTES, 'UTF-8') ?></p>
                <p>Date : <?= htmlspecialchars($trajet['date_arrive'], ENT_QUOTES, 'UTF-8') ?></p>
                <p>Heure : <?= htmlspecialchars($trajet['heure_arrive'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>

        <!-- Détails supplémentaires -->
        <div class="row mb-3">
            <div class="col-md-6">
                <h4>Informations du trajet</h4>
                <p>Places disponibles : <?= htmlspecialchars($trajet['nb_places'], ENT_QUOTES, 'UTF-8') ?></p>
                <p>Prix par personne : <?= htmlspecialchars($trajet['prix_personnes'], ENT_QUOTES, 'UTF-8') ?> €</p>
                <p>Préférences : <?= htmlspecialchars($trajet['preferences'], ENT_QUOTES, 'UTF-8') ?></p>
                <p>Fumeur : <?= $trajet['fumeur'] ? 'Oui' : 'Non' ?></p>
                <p>Animaux acceptés : <?= $trajet['animaux'] ? 'Oui' : 'Non' ?></p>
            </div>

            <div class="col-md-6">
                <h4>Informations sur le créateur</h4>
                <p>Pseudo : <?= htmlspecialchars($trajet['pseudo'], ENT_QUOTES, 'UTF-8') ?></p>
                <p>Téléphone : <?= htmlspecialchars($trajet['telephone'], ENT_QUOTES, 'UTF-8') ?></p>
                <p>
                    <img src="images/<?= htmlspecialchars($trajet['photo'], ENT_QUOTES, 'UTF-8') ?>"
                         alt="Photo du créateur" class="rounded-circle" width="75" height="75">
                </p>
                <p>Note moyenne : <?= htmlspecialchars($trajet['note_moyenne'], ENT_QUOTES, 'UTF-8') ?>/5</p>
            </div>
        </div>

        <!-- Autres participants -->
        <div class="row mb-3">
            <div class="col-12">
                <h4>Autres participants</h4>
                <?php if (!empty($participants)): ?>
                    <?php foreach ($participants as $participant): ?>
                        <div class="participant">
                            <div class="autresParticipants">
                            <p>Pseudo : <?= htmlspecialchars($participant['pseudo'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p>Téléphone : <?= htmlspecialchars($participant['telephone'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p>
                                <img src="images/<?= htmlspecialchars($participant['photo'], ENT_QUOTES, 'UTF-8') ?>"
                                     alt="Photo du participant" class="rounded-circle" width="50" height="50">
                            </p>
                            <p>Note moyenne : <?= htmlspecialchars($participant['note_moyenne'], ENT_QUOTES, 'UTF-8') ?>/5</p>
                       </div> 
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Aucun autre participant pour ce trajet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php
require_once('templates/footer.php');
?>
