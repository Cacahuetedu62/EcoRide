<?php
session_start();
require_once('lib/pdo.php');
require_once('lib/config.php');

if (!isset($_SESSION['utilisateur']['id'])) {
}

$utilisateur_id = $_SESSION['utilisateur']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trajet_id'])) {
    $trajet_id = (int)$_POST['trajet_id'];
    
    // Vérifier que l'utilisateur est le conducteur
    $sqlCheck = "SELECT conducteur_id FROM trajets WHERE id = :trajet_id AND conducteur_id = :utilisateur_id AND statut = 'disponible'";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([
        ':trajet_id' => $trajet_id,
        ':utilisateur_id' => $utilisateur_id
    ]);
    
    if ($stmtCheck->rowCount() === 0) {
        $_SESSION['error'] = "Vous n'êtes pas autorisé à lancer ce trajet.";
        header("Location: mesTrajets.php");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Mettre à jour le statut du trajet
        $sqlUpdateTrajet = "UPDATE trajets SET statut = 'en_cours' WHERE id = :trajet_id";
        $stmtUpdateTrajet = $pdo->prepare($sqlUpdateTrajet);
        $stmtUpdateTrajet->execute([':trajet_id' => $trajet_id]);

        // Mettre à jour les réservations
        $sqlUpdateReservations = "UPDATE reservations SET statut = 'en_cours' WHERE trajet_id = :trajet_id";
        $stmtUpdateReservations = $pdo->prepare($sqlUpdateReservations);
        $stmtUpdateReservations->execute([':trajet_id' => $trajet_id]);

        // Insérer dans l'historique
        $sqlInsert = "INSERT INTO historique (trajet_id, utilisateur_id, date_debut_reel, date_enregistrement) 
                     VALUES (:trajet_id, :utilisateur_id, NOW(), NOW())";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            ':trajet_id' => $trajet_id,
            ':utilisateur_id' => $utilisateur_id
        ]);

        $pdo->commit();
        header("Location: mesTrajets.php");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Erreur lors du lancement du trajet.";
        header("Location: mesTrajets.php");
        exit;
    }
}

header("Location: mesTrajets.php");
?>