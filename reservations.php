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

    // Récupérer les informations du trajet avec l'ID
    $sql = "SELECT t.*, u.pseudo, u.photo, u.note_moyenne, v.energie, v.nb_places AS voiture_places, t.prix_personnes,
            t.preferences, 
            TIMESTAMPDIFF(MINUTE, CONCAT(t.date_depart, ' ', t.heure_depart), CONCAT(t.date_arrive, ' ', t.heure_arrive)) AS duree_minutes
            FROM trajets t
            JOIN trajet_utilisateur tu ON t.id = tu.trajet_id
            JOIN utilisateurs u ON tu.utilisateur_id = u.id
            LEFT JOIN voitures v ON v.utilisateur_id = u.id
            WHERE t.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $trajet_id, PDO::PARAM_INT);
    $stmt->execute();
    $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($trajet) {
        // Afficher les détails du trajet
?>

    <div class="reservation">
        <div class="row justify-content-center align-items-center">
            <div class="col-md-7 col-lg-8 contenair">
                <h4 class="mb-3 text-center">Détails du trajet n° <?= htmlspecialchars($trajet['id']) ?></h4>
                <div class="row g-3 cardTrajet">
                    <div class="departArrive">
                        <h3>Départ</h3>
                        <label class="form-label">Ville de départ</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($trajet['lieu_depart']) ?>" disabled>
                        <label class="form-label">Date de départ</label>
                        <input type="date" class="form-control" value="<?= $trajet['date_depart'] ?>" disabled>
                        <label class="form-label">Heure de départ</label>
                        <input type="text" class="form-control" value="<?= $trajet['heure_depart'] ?>" disabled>
                    </div>

                    <div class="departArrive">
                        <h3>Arrivée</h3>
                        <label class="form-label">Ville d'arrivée</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($trajet['lieu_arrive']) ?>" disabled>
                        <label class="form-label">Date d'arrivée</label>
                        <input type="date" class="form-control" value="<?= $trajet['date_arrive'] ?>" disabled>
                        <label class="form-label">Heure d'arrivée</label>
                        <input type="text" class="form-control" value="<?= $trajet['heure_arrive'] ?>" disabled>
                        <label class="form-label">Durée du trajet</label>
                        <input type="text" class="form-control" value="<?= floor($trajet['duree_minutes'] / 60) . 'h ' . ($trajet['duree_minutes'] % 60) . 'min' ?>" disabled>
                    </div>



                    <div class="infosChauffeurs">
                        <h4>Informations sur le chauffeur</h4>
                        
                        <p class="profilPhotoPseudo"><img src="images/pilote.jpg" alt="Photo du chauffeur" class="bd-placeholder-img rounded-circle" width="75" height="75"> <?= htmlspecialchars($trajet['pseudo']) ?></p>
                        <p>Note : 
                            <?php
                                $note_moyenne = $trajet['note_moyenne'];
                                for ($i = 0; $i < $note_moyenne; $i++) {
                                    echo "🚗";
                                }
                            ?>
                        </p>
                        <p>Nombre de places restantes : <?= $trajet['nb_places'] ?></p>
                        <p>Prix : <?= $trajet['prix_personnes'] ?> € par personne</p>
                        <p>    <?php
                         $ecologique = ($trajet['energie'] === 'électrique' || $trajet['energie'] === 'hybride') ? 'Oui' : 'Non';
        if ($ecologique) {
            echo "🌱 C'est un trajet écologique*";
        } else {
            echo "⛽ Ce trajet n'est pas écologique*";
        }
        ?></p>
<p>Préférences : <?= htmlspecialchars($trajet['preferences']) ?></p>
                    </div>
                </div>
            </div>

    </div>


        <div class="row justify-content-center align-items-center">
            <div class="col-md-7 col-lg-8 contenair">
                <h4 class="mb-3 text-center">Détails du prix</h4>
                <div class="row g-3 cardTrajet">

                        
                        <p>Votre solde est de : </p>
                        <p>Prix du trajet : <?= $trajet['prix_personnes'] ?> € par personne</p>
                        <p>Votre solde après ce trajet : *le solde sera mis à jour lors de la validation du trajet</p>
                    </div>
                </div>
            </div>

    </div>


        <div class="row justify-content-center align-items-center">
            <div class="col-md-7 col-lg-8 contenair">
                <h4 class="mb-3 text-center">Acceptation des conditions et validation</h4>
                <div class="row g-3 cardTrajet">
 <p>J'accepte les conditions de ventes</p>
 <p>J'accepte la politque de confidentialité</p>
 <button type="submit">Je valide</button>
                    </div>
                </div>
            </div>

    </div>

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