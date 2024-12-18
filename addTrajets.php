<?php
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
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <meta name="description" content="EcoRide, quand économie rime avec écologie ! le covoiturage éléctrique, découvrez le covoiturage électrique pour des trajets plus verts et économiques.">
    <title>EcoRide | Covoiturage écologique et éléctrique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
</head>

<main class="form-page">    
    <section class="custom-section">
        <div class="custom-form-container">
            <h4>Créer un Nouveau Trajet</h4>
            <form action="addTrajets.php" method="post">
                <label for="date_depart">Date de Départ :</label>
                <input type="date" id="date_depart" name="date_depart" required>

                <label for="heure_depart">Heure de Départ :</label>
                <input type="time" id="heure_depart" name="heure_depart" required>

                <label for="lieu_depart">Lieu de Départ :</label>
                <input type="text" id="lieu_depart" name="lieu_depart" required>

                <label for="date_arrive">Date d'Arrivée :</label>
                <input type="date" id="date_arrive" name="date_arrive" required>

                <label for="heure_arrive">Heure d'Arrivée :</label>
                <input type="time" id="heure_arrive" name="heure_arrive" required>

                <label for="lieu_arrive">Lieu d'Arrivée :</label>
                <input type="text" id="lieu_arrive" name="lieu_arrive" required>

                <label for="nb_places">Nombre de Places :</label>
                <input type="number" id="nb_places" name="nb_places" required>

                <label for="prix_personnes">Prix par Personne* :</label>
                <input type="number" step="0.01" id="prix_personnes" name="prix_personnes" required>

                <label for="preferences">Préférences :</label>
                <textarea id="preferences" name="preferences"></textarea>

                <label for="voiture_id">Voiture :</label>
                <select id="voiture_id" name="voiture_id" required>
                    <?php foreach ($voitures as $voiture) : ?>
                        <option value="<?php echo $voiture['id']; ?>"><?php echo $voiture['marque'] . ' ' . $voiture['modele']; ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="hidden" name="utilisateur_id" value="<?php echo $utilisateur_id; ?>">

                <button type="submit" id="submitButton" class="buttonVert m-2" disabled>Je créer le trajet</button>
                <p>*Veuillez prendre note que 2 crédits sont prélevés sur le prix pour assurer le bon fonctionement de votre plateforme</p>

            </form>
        </div>
    </section>
</main>
