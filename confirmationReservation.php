<?php
session_start();
require_once('lib/pdo.php');
require_once('lib/config.php');


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] == 1) {
    // Vérifie si l'utilisateur est connecté
    if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
        $utilisateur_id = $_SESSION['utilisateur']['id'];
        $trajet_id = $_POST['trajet_id'];
        $nb_personnes = $_POST['nb_personnes'];
        $total_cost = $_POST['total_cost'];

        // Vérifier le solde de crédits de l'utilisateur
        $sql_check_credits = "SELECT credits FROM utilisateurs WHERE id = :utilisateur_id";
        $stmt_check_credits = $pdo->prepare($sql_check_credits);
        $stmt_check_credits->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
        $stmt_check_credits->execute();
        $user = $stmt_check_credits->fetch();

        if ($user && $user['credits'] >= $total_cost) {
            // Insérer la réservation
            $sql_reservation = "INSERT INTO reservations (utilisateur_id, trajet_id, nb_personnes) VALUES (:utilisateur_id, :trajet_id, :nb_personnes)";
            $stmt_reservation = $pdo->prepare($sql_reservation);
            $stmt_reservation->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
            $stmt_reservation->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
            $stmt_reservation->bindParam(':nb_personnes', $nb_personnes, PDO::PARAM_INT);

            if ($stmt_reservation->execute()) {
                // Mettre à jour le statut du trajet à 'réservé_en_attente'
                $sql_update_statut = "UPDATE trajets SET statut = 'réservé_en_attente' WHERE id = :trajet_id";
                $stmt_update_statut = $pdo->prepare($sql_update_statut);
                $stmt_update_statut->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
                $stmt_update_statut->execute();

                // Réduire les crédits de l'utilisateur
                $sql_update_credits = "UPDATE utilisateurs SET credits = credits - :total_cost WHERE id = :utilisateur_id";
                $stmt_update_credits = $pdo->prepare($sql_update_credits);
                $stmt_update_credits->bindParam(':total_cost', $total_cost, PDO::PARAM_INT);
                $stmt_update_credits->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
                $stmt_update_credits->execute();

                // Rediriger vers une page de confirmation de succès
                header('Location: mesTrajets.php');
                exit;
            } else {
                echo "Erreur lors de la réservation.";
            }
        } else {
            echo "Vous n'avez pas assez de crédits pour effectuer cette réservation.";
        }
    } else {
        echo "Vous devez être connecté pour confirmer la réservation.";
    }
}

require_once('templates/footer.php');
?>
