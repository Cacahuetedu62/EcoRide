<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');
?>

<main>
<?php
// Vérifier si un ID de trajet est passé via l'URL
if (isset($_GET['id'])) {
    $trajet_id = (int) $_GET['id'];

    $sql = "SELECT t.*, u.pseudo, u.photo, u.note_moyenne, v.energie, v.nb_places AS voiture_places, t.prix_personnes,
    t.preferences, u.id AS utilisateur_id, v.marque, v.modele, 
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
        // Afficher les détails du trajet
?>

    <div class="container">
        <div class="row justify-content-center align-items-center mb-3">
            <div class="col-12">
                <h4 class="text-center mt-4">Détails du trajet n° <?= htmlspecialchars($trajet['id']) ?></h4>
            </div>
        </div>
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
        </div></div>

        <div class="row mb-3">
            <div class="col-12 departArrive">
                <h4>Durée du trajet</h4>
                <?= floor($trajet['duree_minutes'] / 60) . ' h ' . ($trajet['duree_minutes'] % 60) . ' min' ?>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6 departArrive">
                <h4>Détail du prix</h4>
                <p>Votre solde est de : </p>
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
                <p>Préférences : <?= htmlspecialchars($trajet['preferences']) ?></p>
        <p>Le trajet se fera en <?= htmlspecialchars($trajet['modele']) ?> de la marque <?= htmlspecialchars($trajet['marque'])?></p>
            </div>
        </div>

        <div class="row mb-3">
    <div class="col-12 departArrive">
        <h4>Acceptation des conditions</h4>
        
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="conditionsVente">
            <label class="form-check-label" for="conditionsVente">J'accepte les conditions de vente</label>
        </div>
        
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="politiqueConfidentialite">
            <label class="form-check-label" for="politiqueConfidentialite">J'accepte la politique de confidentialité</label>
        </div>

        <!-- Message d'erreur -->
        <div id="errorMessage">Si vous n'acceptez pas les conditions vous ne pourrez pass réservé votre trajet</div>

        <!-- Bouton de validation -->
        <?php if (!$isUserLoggedIn): ?>
    <p>Veuillez vous <a href="connexion.php">connecter</a> ou vous <a href="inscription.php">inscrire</a> pour réserver un trajet.</p>
    <button type="submit" id="submitButton" class="buttonVert m-2" disabled>Je réserve mon trajet</button>
<?php else: ?>
    <button type="submit" id="submitButton" class="buttonVert m-2">Je réserve mon trajet</button>
<?php endif; ?>
    </div>
</div>
    </div>


<script>

document.addEventListener('DOMContentLoaded', function () {
    const conditionsVente = document.getElementById('conditionsVente');
    const politiqueConfidentialite = document.getElementById('politiqueConfidentialite');
    const submitButton = document.getElementById('submitButton');
    const errorMessage = document.getElementById('errorMessage');

    // Fonction pour vérifier les deux conditions
    function checkConditions() {
        if (conditionsVente.checked && politiqueConfidentialite.checked) {
            submitButton.disabled = false; // Activer le bouton "Je valide"
            errorMessage.style.display = 'none'; // Cacher le message d'erreur
        } else {
            submitButton.disabled = true; // Désactiver le bouton "Je valide"
            errorMessage.style.display = 'block'; // Afficher le message d'erreur
        }
    }

    // Ajouter des écouteurs d'événements pour les cases à cocher
    conditionsVente.addEventListener('change', checkConditions);
    politiqueConfidentialite.addEventListener('change', checkConditions);

    // Initial check au cas où les cases sont déjà cochées lors du chargement de la page
    checkConditions();
});


</script>



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
