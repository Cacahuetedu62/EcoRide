<?php
session_start();
require_once('lib/pdo.php');
require_once('lib/config.php');

// Vérification de la connexion
if (!isset($_SESSION['utilisateur']) || !isset($_SESSION['utilisateur']['id'])) {
    $_SESSION['message'] = "Vous devez être connecté pour effectuer cette action.";
    $_SESSION['message_type'] = "danger";
    header('Location: mesTrajets.php');
    exit;
}

// Vérification des données POST
if (!isset($_POST['trajet_id']) || !isset($_POST['role'])) {
    $_SESSION['message'] = "Données manquantes pour l'annulation.";
    $_SESSION['message_type'] = "danger";
    header('Location: mesTrajets.php');
    exit;
}

$utilisateur_id = $_SESSION['utilisateur']['id'];
$trajet_id = intval($_POST['trajet_id']);
$role = $_POST['role'];

try {
    $pdo->beginTransaction();

    // Cas du conducteur
    if ($role === 'conducteur') {
        // Vérification que l'utilisateur est bien le conducteur
        $stmt = $pdo->prepare("SELECT utilisateur_id FROM trajet_utilisateur WHERE trajet_id = :trajet_id");
        $stmt->execute(['trajet_id' => $trajet_id]);
        $trajet = $stmt->fetch();

        if ($trajet && $trajet['utilisateur_id'] == $utilisateur_id) {
            // Suppression des passagers
            $stmt = $pdo->prepare("DELETE FROM passagers WHERE reservation_id IN (SELECT id FROM reservations WHERE trajet_id = :trajet_id)");
            $stmt->execute(['trajet_id' => $trajet_id]);

            // Suppression des réservations
            $stmt = $pdo->prepare("DELETE FROM reservations WHERE trajet_id = :trajet_id");
            $stmt->execute(['trajet_id' => $trajet_id]);

            // Suppression du lien trajet-utilisateur
            $stmt = $pdo->prepare("DELETE FROM trajet_utilisateur WHERE trajet_id = :trajet_id");
            $stmt->execute(['trajet_id' => $trajet_id]);

            // Suppression du trajet
            $stmt = $pdo->prepare("DELETE FROM trajets WHERE id = :trajet_id");
            $stmt->execute(['trajet_id' => $trajet_id]);

            $_SESSION['message'] = "Le trajet a été annulé avec succès.";
            $_SESSION['message_type'] = "success";
        } else {
            throw new Exception('Vous n\'êtes pas le conducteur de ce trajet');
        }
    }
    // Cas du passager
    elseif ($role === 'passager') {
        // Récupération des informations de la réservation
        $stmt = $pdo->prepare("
            SELECT r.id as reservation_id, r.nb_personnes, t.nb_places
            FROM reservations r
            JOIN trajets t ON r.trajet_id = t.id
            WHERE r.trajet_id = :trajet_id
            AND r.utilisateur_id = :utilisateur_id
        ");
        $stmt->execute([
            'trajet_id' => $trajet_id,
            'utilisateur_id' => $utilisateur_id
        ]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($reservation) {
            // Mise à jour du nombre de places
            $stmt = $pdo->prepare("
                UPDATE trajets 
                SET nb_places = nb_places + :nb_personnes
                WHERE id = :trajet_id
            ");
            $stmt->execute([
                'nb_personnes' => $reservation['nb_personnes'],
                'trajet_id' => $trajet_id
            ]);

            // Suppression des passagers
            $stmt = $pdo->prepare("DELETE FROM passagers WHERE reservation_id = :reservation_id");
            $stmt->execute(['reservation_id' => $reservation['reservation_id']]);

            // Suppression de la réservation
            $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = :reservation_id");
            $stmt->execute(['reservation_id' => $reservation['reservation_id']]);

            $_SESSION['message'] = "Votre réservation a été annulée avec succès.";
            $_SESSION['message_type'] = "success";
        } else {
            throw new Exception('Réservation non trouvée');
        }
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['message'] = "Erreur lors de l'annulation : " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

header('Location: mesTrajets.php');
exit;
?>