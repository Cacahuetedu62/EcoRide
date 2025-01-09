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
                // Récupérer l'ID de la réservation insérée
                $reservation_id = $pdo->lastInsertId();

                // Insérer les passagers dans la table passagers
                for ($i = 2; $i <= $nb_personnes; $i++) {
                    $nom_passager = isset($_POST['nom_passager' . $i]) ? $_POST['nom_passager' . $i] : '';
                    $prenom_passager = isset($_POST['prenom_passager' . $i]) ? $_POST['prenom_passager' . $i] : '';
                    $sql_passager = "INSERT INTO passagers (reservation_id, utilisateur_id, nom_passager, prenom_passager) VALUES (:reservation_id, :utilisateur_id, :nom_passager, :prenom_passager)";
                    $stmt_passager = $pdo->prepare($sql_passager);
                    $stmt_passager->bindParam(':reservation_id', $reservation_id, PDO::PARAM_INT);
                    $stmt_passager->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
                    $stmt_passager->bindValue(':nom_passager', $nom_passager, PDO::PARAM_STR);
                    $stmt_passager->bindValue(':prenom_passager', $prenom_passager, PDO::PARAM_STR);
                    $stmt_passager->execute();
                }

                // Mettre à jour le statut du trajet à 'réservé_en_attente'
                $sql_update_statut = "UPDATE trajets SET statut = 'réservé_en_attente' WHERE id = :trajet_id";
                $stmt_update_statut = $pdo->prepare($sql_update_statut);
                $stmt_update_statut->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
                $stmt_update_statut->execute();

                // Mettre à jour le nombre de places disponibles
                $sql_update_places = "UPDATE trajets SET nb_places = nb_places - :nb_personnes WHERE id = :trajet_id";
                $stmt_update_places = $pdo->prepare($sql_update_places);
                $stmt_update_places->bindParam(':nb_personnes', $nb_personnes, PDO::PARAM_INT);
                $stmt_update_places->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
                $stmt_update_places->execute();

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
