<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

// Vérifiez si l'utilisateur est connecté
$isUserLoggedIn = isset($_SESSION['user_id']);

if ($isUserLoggedIn && isset($_POST['trajet_id']) && isset($_POST['nb_personnes'])) {
    $user_id = $_SESSION['user_id'];
    $trajet_id = $_POST['trajet_id'];
    $nb_personnes = $_POST['nb_personnes'];

    // Préparer la requête d'insertion
    $sql = "INSERT INTO reservations (user_id, trajet_id, nb_personnes) VALUES (:user_id, :trajet_id, :nb_personnes)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
    $stmt->bindParam(':nb_personnes', $nb_personnes, PDO::PARAM_INT);

    // Exécuter la requête
    if ($stmt->execute()) {
        // Rediriger vers la page "mesTrajets.php"
        header('Location: mesTrajets.php');
        exit;
    } else {
        echo "Erreur lors de la réservation.";
    }
} else {
    echo "Vous devez être connecté pour réserver un trajet.";
}
?>
