<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');


// Récupération de l'ID de l'utilisateur connecté (à remplacer par l'ID réel de l'utilisateur connecté)
$utilisateur_id = 4;
echo ";ADUDPONT pour le test ici : utilisateur >";
var_dump($utilisateur_id);

// Récupération de la liste des voitures de l'utilisateur
$sql = "SELECT id, marque, modele FROM voitures WHERE utilisateur_id = :utilisateur_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
$stmt->execute();
$voitures = $stmt->fetchAll(PDO::FETCH_ASSOC);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération des données du formulaire
    $utilisateur_id = $_POST['utilisateur_id'];
    $date_depart = $_POST['date_depart'];
    $heure_depart = $_POST['heure_depart'];
    $lieu_depart = $_POST['lieu_depart'];
    $date_arrive = $_POST['date_arrive'];
    $heure_arrive = $_POST['heure_arrive'];
    $lieu_arrive = $_POST['lieu_arrive'];
    $nb_places = $_POST['nb_places'];
    $prix_personnes = $_POST['prix_personnes'];
    $preferences = $_POST['preferences'];
    $voiture_id = $_POST['voiture_id'];

    // Afficher les informations de débogage
    echo "Données soumises via le formulaire :<br>";
    var_dump($_POST);

    // Appel de la procédure stockée
    $sql = "CALL CreerTrajet(:utilisateur_id, :date_depart, :heure_depart, :lieu_depart, :date_arrive, :heure_arrive, :lieu_arrive, :nb_places, :prix_personnes, :preferences, :voiture_id)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
    $stmt->bindParam(':date_depart', $date_depart, PDO::PARAM_STR);
    $stmt->bindParam(':heure_depart', $heure_depart, PDO::PARAM_STR);
    $stmt->bindParam(':lieu_depart', $lieu_depart, PDO::PARAM_STR);
    $stmt->bindParam(':date_arrive', $date_arrive, PDO::PARAM_STR);
    $stmt->bindParam(':heure_arrive', $heure_arrive, PDO::PARAM_STR);
    $stmt->bindParam(':lieu_arrive', $lieu_arrive, PDO::PARAM_STR);
    $stmt->bindParam(':nb_places', $nb_places, PDO::PARAM_INT);
    $stmt->bindParam(':prix_personnes', $prix_personnes, PDO::PARAM_STR);
    $stmt->bindParam(':preferences', $preferences, PDO::PARAM_STR);
    $stmt->bindParam(':voiture_id', $voiture_id, PDO::PARAM_INT);

    try {
        $stmt->execute();
        echo "Trajet créé avec succès";
    } catch (PDOException $e) {
        echo "Erreur : " . $e->getMessage();
    }

    // Fermeture de la connexion
    $pdo = null;
}
?>

<main>
    <h1>Créer un Nouveau Trajet</h1>
    <form action="addTrajets.php" method="post">
        <label for="date_depart">Date de Départ :</label>
        <input type="date" id="date_depart" name="date_depart" required><br><br>

        <label for="heure_depart">Heure de Départ :</label>
        <input type="time" id="heure_depart" name="heure_depart" required><br><br>

        <label for="lieu_depart">Lieu de Départ :</label>
        <input type="text" id="lieu_depart" name="lieu_depart" required><br><br>

        <label for="date_arrive">Date d'Arrivée :</label>
        <input type="date" id="date_arrive" name="date_arrive" required><br><br>

        <label for="heure_arrive">Heure d'Arrivée :</label>
        <input type="time" id="heure_arrive" name="heure_arrive" required><br><br>

        <label for="lieu_arrive">Lieu d'Arrivée :</label>
        <input type="text" id="lieu_arrive" name="lieu_arrive" required><br><br>

        <label for="nb_places">Nombre de Places :</label>
        <input type="number" id="nb_places" name="nb_places" required><br><br>

        <label for="prix_personnes">Prix par Personne :</label>
        <input type="number" step="0.01" id="prix_personnes" name="prix_personnes" required><br><br>

        <label for="preferences">Préférences :</label>
        <textarea id="preferences" name="preferences"></textarea><br><br>

        <label for="voiture_id">Voiture :</label>
        <select id="voiture_id" name="voiture_id" required>
            <?php foreach ($voitures as $voiture) : ?>
                <option value="<?php echo $voiture['id']; ?>"><?php echo $voiture['marque'] . ' ' . $voiture['modele']; ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <input type="hidden" name="utilisateur_id" value="<?php echo $utilisateur_id; ?>">

        <input type="submit" value="Créer le Trajet">
    </form>
</main>

<?php
require_once('templates/footer.php');
?>
