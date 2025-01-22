<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

// Vérification de la session utilisateur
if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    $utilisateur_id = htmlspecialchars($_SESSION['utilisateur']['id'], ENT_QUOTES, 'UTF-8');
    echo "<div class='container'><div class='alert alert-info'>L'utilisateur connecté a l'ID : " . $utilisateur_id . "</div></div>";
} else {
    // L'utilisateur n'est pas connecté
    echo "<div class='container'><div class='alert alert-warning'>Utilisateur non connecté.</div></div>";
}
?>

<main class="container py-5">
<?php
// Vérifier si un ID de trajet est passé via l'URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $trajet_id = (int) $_GET['id'];

    // Utilisation de déclarations préparées pour éviter les injections SQL
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

    if ($trajet) {
        $credits = htmlspecialchars($trajet['credits'], ENT_QUOTES, 'UTF-8');
        $nb_places_dispo = htmlspecialchars($trajet['nb_places'], ENT_QUOTES, 'UTF-8');
        $prix_personne = htmlspecialchars($trajet['prix_personnes'], ENT_QUOTES, 'UTF-8');
        $nb_personnes = isset($_POST['nb_personnes']) ? intval($_POST['nb_personnes']) : 1;
        $isUserLoggedIn = isset($_SESSION['utilisateur']['id']);

        // Vérifiez si le nombre de places disponibles est suffisant
        $places_suffisantes = $nb_places_dispo >= $nb_personnes;
        // Vérifiez si l'utilisateur a suffisamment de crédits
        $credits_suffisants = $credits >= ($prix_personne * $nb_personnes);
?>
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h2 class="text-center mb-0">
                        Détails du trajet n° <?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>
                    </h2>
                </div>
                
                <div class="card-body">
                    <!-- Départ et Arrivée -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-info text-white">
                                    <h4 class="mb-0">Départ</h4>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Ville de départ</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($trajet['lieu_depart'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Date de départ</label>
                                        <input type="date" class="form-control" value="<?= htmlspecialchars($trajet['date_depart'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                    </div>
                                    <div>
                                        <label class="form-label">Heure de départ</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($trajet['heure_depart'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-info text-white">
                                    <h4 class="mb-0">Arrivée</h4>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Ville d'arrivée</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($trajet['lieu_arrive'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Date d'arrivée</label>
                                        <input type="date" class="form-control" value="<?= htmlspecialchars($trajet['date_arrive'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                    </div>
                                    <div>
                                        <label class="form-label">Heure d'arrivée</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($trajet['heure_arrive'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Détails du trajet -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-secondary text-white">
                                    <h4 class="mb-0">Durée et Prix</h4>
                                </div>
                                <div class="card-body">
                                    <p class="mb-2">
                                        <strong>Durée du trajet :</strong> 
                                        <?= floor($trajet['duree_minutes'] / 60) . ' h ' . ($trajet['duree_minutes'] % 60) . ' min' ?>
                                    </p>
                                    <p class="mb-2">
                                        <strong>Votre solde :</strong> 
                                        <?= htmlspecialchars($credits, ENT_QUOTES, 'UTF-8') ?> crédits
                                    </p>
                                    <p class="mb-2">
                                        <strong>Prix du trajet :</strong> 
                                        <?= htmlspecialchars($trajet['prix_personnes'], ENT_QUOTES, 'UTF-8') ?> € par personne
                                    </p>
                                    <p class="text-muted fst-italic">
                                        * Le solde sera mis à jour lors de la validation du trajet
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-secondary text-white">
                                    <h4 class="mb-0">Informations du Chauffeur</h4>
                                </div>
                                <div class="card-body text-center">
                                    <img src="<?= htmlspecialchars($trajet['photo'], ENT_QUOTES, 'UTF-8') ?>" 
                                         alt="Photo du chauffeur" 
                                         class="rounded-circle mb-3" 
                                         style="width: 100px; height: 100px; object-fit: cover;">
                                    
                                    <h5 class="card-title"><?= htmlspecialchars($trajet['pseudo'], ENT_QUOTES, 'UTF-8') ?></h5>
                                    
                                    <div class="mb-2">
                                        <strong>Note :</strong>
                                        <?php
                                        $note_moyenne = htmlspecialchars($trajet['note_moyenne'], ENT_QUOTES, 'UTF-8');
                                        for ($i = 0; $i < $note_moyenne; $i++) {
                                            echo "🚗";
                                        }
                                        ?>
                                    </div>

                                    <div class="mb-2">
                                        <a href="commentaires.php?id=<?= htmlspecialchars($trajet['utilisateur_id'], ENT_QUOTES, 'UTF-8') ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            Voir tous les commentaires
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Détails supplémentaires -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0">Détails du Voyage</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2">
                                        <strong>Nombre de places restantes :</strong> 
                                        <?= htmlspecialchars($trajet['nb_places'], ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                    <p class="mb-2">
                                        <?php
                                        $ecologique = ($trajet['energie'] === 'électrique' || $trajet['energie'] === 'hybride') ? 'Oui' : 'Non';
                                        ?>
                                        <strong>Trajet écologique :</strong> 
                                        <?= $ecologique ? '🌱 Oui' : '⛽ Non' ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2">
                                        <strong>Fumeur accepté :</strong> 
                                        <?= htmlspecialchars($trajet['fumeur'], ENT_QUOTES, 'UTF-8') ? 'Oui' : 'Non' ?>
                                    </p>
                                    <p class="mb-2">
                                        <strong>Animaux acceptés :</strong> 
                                        <?= htmlspecialchars($trajet['animaux'], ENT_QUOTES, 'UTF-8') ? 'Oui' : 'Non' ?>
                                    </p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <strong>Préférences :</strong> 
                                <?php 
                                $preferences = htmlspecialchars($trajet['preferences'], ENT_QUOTES, 'UTF-8');
                                echo !empty($preferences) ? $preferences : '<em class="text-muted">Aucune préférence spécifiée</em>'; 
                                ?>
                            </div>
                            <div class="mt-2">
                                <strong>Véhicule :</strong> 
                                <?= htmlspecialchars($trajet['modele'], ENT_QUOTES, 'UTF-8') ?> 
                                de la marque 
                                <?= htmlspecialchars($trajet['marque'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire de réservation -->
                    <form action="reserverTrajet.php" method="POST" id="reservationForm">
                        <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet_id, ENT_QUOTES, 'UTF-8') ?>">
                        
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-white">
                                <h4 class="mb-0">Réservation</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nb_personnes" class="form-label">Nombre de personnes :</label>
                                        <input type="number" id="nb_personnes" name="nb_personnes" 
                                               class="form-control" min="1" max="<?= $nb_places_dispo ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">Prix total :</h5>
                                            <span id="totalPrice" class="h4 text-primary">0 €</span>
                                        </div>
                                    </div>
                                </div>

                                <div id="additionalPassengers" style="display: none;">
                                    <h5 class="mt-3">Informations sur les passagers supplémentaires :</h5>
                                    <div id="passengerFields"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Conditions -->
                        <div class="card mb-4">
                            <div class="card-header bg-danger text-white">
                                <h4 class="mb-0">Acceptation des conditions</h4>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-2">
                                    <input type="checkbox" class="form-check-input" id="conditionsVente">
                                    <label class="form-check-label" for="conditionsVente">
                                        J'accepte les conditions de vente
                                    </label>
                                </div>

                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="politiqueConfidentialite">
                                    <label class="form-check-label" for="politiqueConfidentialite">
                                        J'accepte la politique de confidentialité
                                    </label>
                                </div>

                                <div id="errorMessage" class="alert alert-danger" style="display: none;">
                                    <?php if (!$places_suffisantes): ?>
                                        <p>Il n'y a pas assez de places disponibles pour ce trajet.</p>
                                    <?php endif; ?>
                                    <?php if (!$credits_suffisants): ?>
                                        <p>Vous n'avez pas assez de crédits pour réserver ce trajet.</p>
                                    <?php endif; ?>
                                </div>

                                <?php if (!$isUserLoggedIn): ?>
                                    <div class="alert alert-warning">
                                        <p>Veuillez vous <a href="connexion.php">connecter</a> ou vous <a href="inscription.php">inscrire</a> pour réserver un trajet.</p>
                                    </div>
                                <?php endif; ?>

                                <div class="text-center">
                                    <button type="submit" id="submitButton" 
                                            class="btn btn-success" 
                                            <?= (!$isUserLoggedIn || !$places_suffisantes || !$credits_suffisants) ? 'disabled' : '' ?>>
                                        Réserver
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const nbPersonnesInput = document.getElementById('nb_personnes');
        const additionalPassengers = document.getElementById('additionalPassengers');
        const passengerFields = document.getElementById('passengerFields');
        const totalPriceSpan = document.getElementById('totalPrice');
        const pricePerPerson = <?= json_encode($trajet['prix_personnes']) ?>;
        const submitButton = document.getElementById('submitButton');
        const conditionsVenteCheckbox = document.getElementById('conditionsVente');
        const politiqueConfidentialiteCheckbox = document.getElementById('politiqueConfidentialite');
        const errorMessage = document.getElementById('errorMessage');

        // Validation des passagers et du prix
        nbPersonnesInput.addEventListener('input', function () {
            const nbPersonnes = parseInt(nbPersonnesInput.value, 10) || 1;
            const totalPrice = pricePerPerson * nbPersonnes;
            totalPriceSpan.textContent = totalPrice.toFixed(2) + ' €';

            // Gestion des passagers supplémentaires
            if (nbPersonnes > 1) {
                additionalPassengers.style.display = 'block';
                passengerFields.innerHTML = '';
                
                for (let i = 2; i <= nbPersonnes; i++) {
                    const passengerDiv = document.createElement('div');
                    passengerDiv.className = 'row mb-3';
                    passengerDiv.innerHTML = `
                        <div class="col-md-6">
                            <label for="nom_${i}" class="form-label">Nom du passager ${i}</label>
                            <input type="text" id="nom_${i}" name="noms[${i}][nom]" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="prenom_${i}" class="form-label">Prénom du passager ${i}</label>
                            <input type="text" id="prenom_${i}" name="noms[${i}][prenom]" class="form-control" required>
                        </div>
                    `;
                    passengerFields.appendChild(passengerDiv);
                }
            } else {
                additionalPassengers.style.display = 'none';
            }
        });

        // Validation des conditions
        function validateConditions() {
            const conditionsAccepted = conditionsVenteCheckbox.checked && politiqueConfidentialiteCheckbox.checked;
            submitButton.disabled = !conditionsAccepted;
            
            if (conditionsAccepted) {
                errorMessage.style.display = 'none';
            } else {
                errorMessage.style.display = 'block';
                errorMessage.innerHTML = '<p>Vous devez accepter les conditions de vente et la politique de confidentialité pour réserver ce trajet.</p>';
            }
        }

        // Écouteurs d'événements pour les conditions
        conditionsVenteCheckbox.addEventListener('change', validateConditions);
        politiqueConfidentialiteCheckbox.addEventListener('change', validateConditions);

        // Validation initiale
        validateConditions();
    });
    </script>
</main>

<?php
    } else {
        echo "<div class='container'><div class='alert alert-danger'>Trajet non trouvé.</div></div>";
    }
} else {
    echo "<div class='container'><div class='alert alert-warning'>Aucun trajet sélectionné.</div></div>";
}

require_once('templates/footer.php');
?>