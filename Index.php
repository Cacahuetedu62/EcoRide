<?php
require_once('templates/header.php');
require_once('lib/config.php');
require_once('lib/pdo.php');


//Récupération de tous les utilisateurs
// $query = $pdo->prepare("SELECT * FROM trajets");
// $query->execute();
// $allUsers = $query->fetchAll(PDO::FETCH_ASSOC);
// var_dump($allUsers);
// ?>


<main>
    
 <div class="row p-3 justify-content-center align-items-md-stretch containHistoire">
        <div class="col-md-6 d-flex justify-content-center">
            <div class="h-100 p-1 rounded-3 image-backgroundHistoire">
                <p class="descriptionHistoire">EcoRide est une entreprise de covoiturage innovante qui met l'accent sur l'utilisation de véhicules électriques, alliant ainsi mobilité durable et respect de l'environnement. En choisissant EcoRide, vous contribuez à réduire les émissions de CO2 tout en profitant d'un transport économique et pratique. Nous offrons une alternative écologique aux trajets quotidiens, en permettant à nos utilisateurs de partager des trajets en toute sécurité et à moindre coût. Grâce à notre plateforme facile d'accès, chacun peut trouver un trajet éco-responsable adapté à ses besoins. EcoRide, c'est la solution idéale pour ceux qui veulent voyager intelligemment, économiquement et en respectant la planète. Rejoignez notre communauté et participez à la révolution verte du transport !</p>
            </div>
        </div>


      
        <div class="col-md-6 p-3 d-flex justify-content-center"> 
             
            <div class="col-md-7 col-lg-8 contenair">
            <h4 class="mb-3 text-center">Chercher un trajet</h4>
<form class="needs-validation" novalidate="" method="POST" action="">
    <div class="row g-3 cardTrajet">
        <!-- Ville de départ -->
        <div class="col-12">
            <label for="ville_depart" class="form-label">Ville de départ</label>
            <input type="text" class="form-control" id="ville_depart" name="ville_depart" placeholder="Ville de départ" required>
            <div class="invalid-feedback">
                Veuillez saisir une ville de départ
            </div>
        </div>
        
        <!-- Ville d'arrivé -->
        <div class="col-12">
            <label for="ville_arrive" class="form-label">Ville d'arrivé</label>
            <input type="text" class="form-control" id="ville_arrive" name="ville_arrive" placeholder="Ville d'arrivé" required>
            <div class="invalid-feedback">
                Veuillez saisir une ville d'arrivé
            </div>
        </div>

        <!-- Date de départ -->
        <div class="col-12">
            <label for="date_depart" class="form-label">Date de départ</label>
            <input type="date" class="form-control" id="date_depart" name="date_depart" required>
            <div class="invalid-feedback">
                Veuillez saisir une date de départ
            </div>
        </div>

        <!-- Nombre de passagers -->
        <div class="col-12">
            <label for="nb_passagers" class="form-label">Nombre de passagers</label>
            <input type="number" class="form-control" id="nb_passagers" name="nb_passagers" placeholder="Nombre de passagers" required>
            <div class="invalid-feedback">
                Veuillez saisir le nombre de passagers
            </div>
        </div>

        <!-- Bouton de recherche -->
        <button class="buttonVert" type="submit" name="chercher">Chercher</button>
    </div>
</form>

                </div>
            </div>

            <?php
if (isset($_POST['chercher'])) {
    // Récupérer les données du formulaire
    $ville_depart = $_POST['ville_depart'];
    $ville_arrive = $_POST['ville_arrive'];
    $date_depart = $_POST['date_depart'];
    $nb_passagers = $_POST['nb_passagers'];

    // Validation des villes (uniquement lettres et espaces autorisés)
    if (!preg_match("/^[a-zA-Z\s]+$/", $ville_depart)) {
        die("Ville de départ invalide. Seules les lettres et les espaces sont autorisés.");
    }
    if (!preg_match("/^[a-zA-Z\s]+$/", $ville_arrive)) {
        die("Ville d'arrivé invalide. Seules les lettres et les espaces sont autorisés.");
    }

    // Filtrage pour assainir les villes en supprimant les caractères spéciaux HTML
    $ville_depart = htmlspecialchars($ville_depart, ENT_QUOTES, 'UTF-8');
    $ville_arrive = htmlspecialchars($ville_arrive, ENT_QUOTES, 'UTF-8');
    $date_depart = htmlspecialchars($date_depart, ENT_QUOTES, 'UTF-8');
    $nb_passagers = intval($nb_passagers); // Assurer que le nombre de passagers est un entier

    // Préparer la requête SQL
    $sql = "SELECT t.*, u.pseudo, u.photo, a.note, v.energie, v.nb_places AS voiture_places, t.prix_personnes
            FROM trajets t
            JOIN trajet_utilisateur tu ON t.id = tu.trajet_id
            JOIN utilisateurs u ON tu.utilisateur_id = u.id
            LEFT JOIN avis a ON a.trajet_id = t.id
            LEFT JOIN voitures v ON v.utilisateur_id = u.id
            WHERE t.lieu_depart LIKE :ville_depart
            AND t.lieu_arrive LIKE :ville_arrive
            AND t.date_depart = :date_depart
            AND t.nb_places >= :nb_passagers";

    $stmt = $pdo->prepare($sql);

    // Lier les paramètres
    $stmt->bindValue(':ville_depart', '%' . $ville_depart . '%');
    $stmt->bindValue(':ville_arrive', '%' . $ville_arrive . '%');
    $stmt->bindValue(':date_depart', $date_depart);
    $stmt->bindValue(':nb_passagers', $nb_passagers, PDO::PARAM_INT);

    // Exécuter la requête
    $stmt->execute();

    // Vérifier si des trajets correspondent
    $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($trajets) > 0) {
        // Afficher les résultats
        foreach ($trajets as $trajet) {
            // Vérifier si le trajet est écologique (si l'énergie est électrique)
            $ecologique = ($trajet['energie'] === 'électrique') ? 'Oui' : 'Non';
            ?>
            <div class="col-md-4 p-1 d-flex justify-content-center">
                <div class="col-md-7 col-lg-8 contenair">
                    <h4 class="mb-3 text-center">Trajet n° <?= htmlspecialchars($trajet['id']) ?></h4>
                    <div class="row g-3 cardTrajet">
                        <div class="departArrive">
                            <h3>Départ</h3>
                            <label for="#" class="form-label">Ville de départ</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($trajet['lieu_depart']) ?>" disabled>
    
                            <label for="#" class="form-label">Date de départ</label>
                            <input type="date" class="form-control" value="<?= $trajet['date_depart'] ?>" disabled>
    
                            <label for="#" class="form-label">Heure de départ</label>
                            <input type="text" class="form-control" value="<?= $trajet['heure_depart'] ?>" disabled>
                        </div>
    
                        <div class="departArrive">
                            <h3>Arrivée</h3>
                            <label for="#" class="form-label">Ville d'arrivé</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($trajet['lieu_arrive']) ?>" disabled>
    
                            <label for="#" class="form-label">Date d'arrivé</label>
                            <input type="date" class="form-control" value="<?= $trajet['date_arrive'] ?>" disabled>
    
                            <label for="#" class="form-label">Heure d'arrivé</label>
                            <input type="text" class="form-control" value="<?= $trajet['heure_arrive'] ?>" disabled>
                        </div>
    
                        <!-- Informations supplémentaires -->
                        <div class="chauffeur-info">
                            <h4>Informations sur le chauffeur</h4>
                            <p>Pseudo : <?= htmlspecialchars($trajet['pseudo']) ?></p>
                            <p>Note :<?= htmlspecialchars($trajet['note']) ?> / 5</p>
                            <p>Photo :<img src="<?= htmlspecialchars($trajet['photo']) ?>" alt="Photo du chauffeur" width="100"></p>
                            <p>Nombre de places restantes :<?= $trajet['nb_places'] ?></p>
                            <p>Prix :<?= $trajet['prix_personnes'] ?> € par personne</p>
                            <p>Voyage écologique : <?= $ecologique ?></p>
                        </div>
                    </div>
                </div>
                
            </div>
            <?php
        }
    } else {
        echo "<p>Aucun trajet trouvé pour les dates sélectionnées. Peut-être que des trajets sont disponibles à d'autres dates. Essayez de modifier vos dates de départ.</p>";
    }
}
?>
    </div>


    <div class="container marketing">
        <div class="row carousselImageDescription">
            <div class="col-lg-4 d-flex flex-column align-items-center text-center">
                <img src="images/poignee_de_main.jpg" class="bd-placeholder-img rounded-circle" width="200" height="150" alt="poignée de main">
                <p class="p-3">Le covoiturage favorise la solidarité et l'entraide entre les conducteurs et passagers, permettant de partager des trajets tout en économisant de l'argent. C'est aussi une manière de renforcer les liens sociaux et d'encourager une mobilité plus responsable.</p>
            </div>
            <div class="col-lg-4 d-flex flex-column align-items-center text-center">
                <img src="images/electric-car.jpg" class="bd-placeholder-img rounded-circle" width="200" height="140" alt="voiture éléctrique en charge">
                <p class="p-3">Le covoiturage électrique contribue à réduire les émissions de CO2 et à diminuer la pollution de l'air. En choisissant des véhicules électriques, nous participons activement à la préservation de l'environnement.</p>
            </div>
            <div class="col-lg-4 d-flex flex-column align-items-center text-center">
                <img src="images/pouce-levé.jpg" class="bd-placeholder-img rounded-circle" width="200" height="140" alt="pouce levé">
                <p class="p-3">L'application et le site web "EcoRide" sont conçus pour offrir une expérience utilisateur simple et rapide. Trouver un trajet éco-responsable n'a jamais été aussi facile grâce à une interface claire et intuitive.</p>
            </div>
        </div>
</main>


<?php
require_once('templates/footer.php');
?>