<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');


if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    $utilisateur_id = $_SESSION['utilisateur']['id'];
    echo "<div class='alert alert-info'>L'utilisateur connecté a l'ID : " . $utilisateur_id . "</div>";
} else {
    // L'utilisateur n'est pas connecté
    echo "Utilisateur non connecté.";
}
?>

<main>
<?php
// Vérifier si un ID de trajet est passé via l'URL
if (isset($_GET['id'])) {
    $trajet_id = (int) $_GET['id'];

    $sql = "SELECT t.*, u.pseudo, u.photo, u.note_moyenne, u.credits, v.energie, v.nb_places AS voiture_places, t.prix_personnes,
    t.preferences, t.fumeur, t.animaux, u.id AS utilisateur_id, v.marque, v.modele,
    TIMESTAMPDIFF(MINUTE, CONCAT(t.date_depart, ' ', t.heure_depart), CONCAT(t.date_arrive, ' ', t.heure_arrive)) AS duree_minutes,
    a.commentaires
    FROM trajets t
    JOIN trajet_utilisateur tu ON t.id = tu.trajet_id
    JOIN utilisateurs u ON tu.utilisateur_id = u.id
    LEFT JOIN voitures v ON v.utilisateur_id = u.id
    LEFT JOIN avis a ON a.utilisateur_id = u.id
    WHERE t.id = :id";


    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $trajet_id, PDO::PARAM_INT);
    $stmt->execute();
    $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

    $credits = $trajet['credits'];
    $nb_places_dispo = $trajet['nb_places'];
    $prix_personne = $trajet['prix_personnes'];
    $nb_personnes = isset($_POST['nb_personnes']) ? intval($_POST['nb_personnes']) : 1;
    $isUserLoggedIn = isset($_SESSION['utilisateur']['id']);

    // Vérifiez si le nombre de places disponibles est suffisant
    $places_suffisantes = $nb_places_dispo >= $nb_personnes;
    // Vérifiez si l'utilisateur a suffisamment de crédits
    $credits_suffisants = $credits >= ($prix_personne * $nb_personnes);

    if ($trajet) {
        // Afficher les détails du trajet
?>
    <div class="container">
        <div class="row justify-content-center align-items-center mb-3">
            <div class="col-12">
                <h4 class="text-center mt-4">Détails du trajet n° <?= htmlspecialchars($trajet['id']) ?></h4>
            </div>
        </div>

        <!-- Détails du trajet -->
        <div class="row mb-3">
            <div class="col-md-6 departArrive">
                <h4>Départ</h4>
                <label class="form-label">Ville de départ</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($trajet['lieu_depart']) ?>" disabled>
                <label class="form-label">Date de départ</label>
                <input type="date" class="form-control" value="<?= $trajet['date_depart'] ?>" disabled>
                <label class="form-label">Heure de départ</label>
                <input type="text" class="form-control" value="<?= $trajet['heure_depart'] ?>" disabled>
            </div>

            <div class="col-md-6 departArrive">
                <h4>Arrivée</h4>
                <label class="form-label">Ville d'arrivée</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($trajet['lieu_arrive']) ?>" disabled>
                <label class="form-label">Date d'arrivée</label>
                <input type="date" class="form-control" value="<?= $trajet['date_arrive'] ?>" disabled>
                <label class="form-label">Heure d'arrivée</label>
                <input type="text" class="form-control" value="<?= $trajet['heure_arrive'] ?>" disabled>
            </div>
        </div>

        <!-- Durée et réservation -->
        <div class="row mb-3">
            <div class="col-md-6 departArrive">
                <h4>Durée du trajet</h4>
                <?= floor($trajet['duree_minutes'] / 60) . ' h ' . ($trajet['duree_minutes'] % 60) . ' min' ?>
            </div>

        <!-- Détails financiers et chauffeur -->
        <div class="row mb-3">
            <div class="col-md-6 departArrive">
                <h4>Détail du prix</h4>
                <p>Votre solde est de : <?= htmlspecialchars($credits, ENT_QUOTES, 'UTF-8') ?> crédits</p>
                <p>Prix du trajet : <?= $trajet['prix_personnes'] ?> € par personne</p>
                <p>Votre solde après ce trajet : *le solde sera mis à jour lors de la validation du trajet</p>
            </div>

            <div class="col-md-6 departArrive">
                <h4>Informations sur le chauffeur</h4>
                <p class="profilPhotoPseudo">
                    <img src="images/pilote.jpg" alt="Photo du chauffeur" class="bd-placeholder-img rounded-circle" width="75" height="75">
                    <?= htmlspecialchars($trajet['pseudo']) ?>
                </p>
                <p>Note :
                    <?php
                    $note_moyenne = $trajet['note_moyenne'];
                    for ($i = 0; $i < $note_moyenne; $i++) {
                        echo "🚗";
                    }
                    ?>
                </p>
                <p><a href="commentaires.php?id=<?= htmlspecialchars($trajet['utilisateur_id']) ?>">Voir tous les commentaires de <?= htmlspecialchars($trajet['pseudo']) ?></a></p>
                <p>Nombre de places restantes : <?= $trajet['nb_places'] ?></p>
                <p>
                    <?php
                    $ecologique = ($trajet['energie'] === 'électrique' || $trajet['energie'] === 'hybride') ? 'Oui' : 'Non';
                    if ($ecologique) {
                        echo "🌱 C'est un trajet écologique*";
                    } else {
                        echo "⛽ Ce trajet n'est pas écologique*";
                    }
                    ?>
                </p>
                <p>Fumeur accepté : <?= $trajet['fumeur'] ? 'Oui' : 'Non' ?></p>
                <p>Animaux acceptés : <?= $trajet['animaux'] ? 'Oui' : 'Non' ?></p>
                <p>Préférences : <?= htmlspecialchars($trajet['preferences']) ?></p>
                <p>Le trajet se fera en <?= htmlspecialchars($trajet['modele']) ?> de la marque <?= htmlspecialchars($trajet['marque'])?></p>
            </div>
        </div>

        <!-- Nombre de passagers -->
        <div class="row mb-3">
            <div class="col-12">
                <h4>Nombre de passagers</h4>
                
                <form action="reserverTrajet.php" method="POST">
                    <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet_id, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label for="nb_personnes" class="form-label">Nombre de personnes :</label>
                        <input type="number" id="nb_personnes" name="nb_personnes" class="form-control" min="1" required>
                    </div>
                    <div id="additionalPassengers" style="display: none;">
                        <h5>Informations sur les passagers supplémentaires :</h5>
                        <div id="passengerFields"></div>
                    </div>
            </div>
        </div>

        <!-- Acceptation des conditions et validation -->
        <div class="row mb-3">
            <div class="col-12">
                <h4>Acceptation des conditions</h4>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="conditionsVente">
                    <label class="form-check-label" for="conditionsVente">J'accepte les conditions de vente</label>
                </div>

                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="politiqueConfidentialite">
                    <label class="form-check-label" for="politiqueConfidentialite">J'accepte la politique de confidentialité</label>
                </div>

                <div id="errorMessage">
                    <?php if (!$places_suffisantes): ?>
                        <p>Il n'y a pas assez de places disponibles pour ce trajet.</p>
                    <?php endif; ?>
                    <?php if (!$credits_suffisants): ?>
                        <p>Vous n'avez pas assez de crédits pour réserver ce trajet.</p>
                    <?php endif; ?>
                    <?php if ($places_suffisantes && $credits_suffisants): ?>
                        <p>Si vous n'acceptez pas les conditions, vous ne pourrez pas réserver votre trajet.</p>
                    <?php endif; ?>
                </div>

                <?php if (!$isUserLoggedIn): ?>
                    <p>Veuillez vous <a href="connexion.php">connecter</a> ou vous <a href="inscription.php">inscrire</a> pour réserver un trajet.</p>
                <?php endif; ?>

                <div id="totalPrice" style="display: none;">
                    <h5>Prix total : <span id="priceValue">0</span> €</h5>
                </div>

                <button type="submit" id="submitButton" class="buttonVert m-2" <?= (!$isUserLoggedIn || !$places_suffisantes || !$credits_suffisants) ? 'disabled' : '' ?>>Réserver</button>
                </form>
            </div>
        </div>

        <script>
document.addEventListener('DOMContentLoaded', function () {
    const nbPersonnesInput = document.getElementById('nb_personnes');
    const additionalPassengers = document.getElementById('additionalPassengers');
    const passengerFields = document.getElementById('passengerFields');
    const totalPriceDiv = document.getElementById('totalPrice');
    const priceValue = document.getElementById('priceValue');
    const pricePerPerson = <?= json_encode($trajet['prix_personnes']) ?>; // Utilise le prix par personne du trajet
    const submitButton = document.getElementById('submitButton');
    const conditionsVenteCheckbox = document.getElementById('conditionsVente');
    const politiqueConfidentialiteCheckbox = document.getElementById('politiqueConfidentialite');
    const errorMessage = document.getElementById('errorMessage');

    if (!nbPersonnesInput || !additionalPassengers || !passengerFields || !totalPriceDiv || !priceValue || !submitButton || !conditionsVenteCheckbox || !politiqueConfidentialiteCheckbox || !errorMessage) {
        console.error('One or more required elements are missing.');
        return;
    }

    nbPersonnesInput.addEventListener('input', function () {
    const nbPersonnes = parseInt(nbPersonnesInput.value, 10);

    // Si le nombre de passagers est invalide ou inférieur à 1
    if (isNaN(nbPersonnes) || nbPersonnes < 1) {
        additionalPassengers.style.display = 'none';
        totalPriceDiv.style.display = 'none';
        passengerFields.innerHTML = '';
        return;
    }

    // Réinitialiser les champs des passagers supplémentaires
    passengerFields.innerHTML = '';

    if (nbPersonnes > 1) {
        additionalPassengers.style.display = 'block';

        // Ajouter des champs pour chaque passager supplémentaire
        for (let i = 2; i <= nbPersonnes; i++) {
            const passengerField = document.createElement('div');
            passengerField.className = 'mb-3 departArrive';
            passengerField.innerHTML = `
                <label for="nom_${i}_passager" class="form-label">Nom du passager ${i} :</label>
                <input type="text" id="nom_${i}_passager" name="noms[${i}][nom]" class="form-control" required>
                <label for="prenom_${i}_passager" class="form-label">Prénom du passager ${i} :</label>
                <input type="text" id="prenom_${i}_passager" name="noms[${i}][prenom]" class="form-control" required>
            `;
            passengerFields.appendChild(passengerField);
        }
    } else {
        additionalPassengers.style.display = 'none';
    }

    // Calculer le prix total et l'afficher
    const totalPrice = pricePerPerson * nbPersonnes;
    priceValue.textContent = totalPrice.toFixed(2);
    totalPriceDiv.style.display = 'block';
});


    // Fonction pour vérifier l'état des cases à cocher et mettre à jour le message d'erreur
    function checkConditions() {
        if (conditionsVenteCheckbox.checked && politiqueConfidentialiteCheckbox.checked) {
            errorMessage.innerHTML = ""; // Efface le message d'erreur si les conditions sont acceptées
        } else {
            errorMessage.innerHTML = "<p>Vous devez accepter les conditions de vente et la politique de confidentialité pour réserver ce trajet.</p>";
        }
    }

    // Validation avant la soumission du formulaire
    submitButton.addEventListener('click', function(event) {
        if (!conditionsVenteCheckbox.checked || !politiqueConfidentialiteCheckbox.checked) {
            event.preventDefault();  // Empêche la soumission du formulaire si les conditions ne sont pas acceptées
        }
    });

    // Ajouter des écouteurs d'événements pour les cases à cocher
    conditionsVenteCheckbox.addEventListener('change', checkConditions);
    politiqueConfidentialiteCheckbox.addEventListener('change', checkConditions);

    // Vérifier l'état initial des cases à cocher
    checkConditions();
});

    </script>
</body>
</html>

</main>

<?php
    } else {
        echo "Trajet non trouvé.";
    }
} else {
    echo "Aucun trajet sélectionné.";
}

require_once('templates/footer.php');
?>
