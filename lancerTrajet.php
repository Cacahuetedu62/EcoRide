<?php
session_start();
require_once('lib/pdo.php');
require_once('lib/config.php');

// Vérification de l'utilisateur connecté
if (!isset($_SESSION['utilisateur']) || !isset($_SESSION['utilisateur']['id'])) {
    echo "Utilisateur non connecté.";
    exit;
}

$utilisateur_id = $_SESSION['utilisateur']['id'];

// Vérification de la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Méthode de requête non valide.";
    exit;
}

// Validation des champs requis
if (!isset($_POST['trajet_id']) || !isset($_POST['utilisateur_id'])) {
    echo "Aucun trajet valide spécifié.";
    exit;
}

echo "Trajet ID reçu : " . htmlspecialchars($_POST['trajet_id'], ENT_QUOTES, 'UTF-8') . "<br>";
echo "Utilisateur ID reçu : " . htmlspecialchars($_POST['utilisateur_id'], ENT_QUOTES, 'UTF-8') . "<br>";


$trajet_id = (int)$_POST['trajet_id'];
$utilisateur_id = (int)$_POST['utilisateur_id'];

// Vérifiez si l'utilisateur est le chauffeur du trajet
$sqlCheckChauffeur = "SELECT 1 
                      FROM trajet_utilisateur 
                      WHERE trajet_id = :trajet_id 
                      AND utilisateur_id = :utilisateur_id";
$stmtCheckChauffeur = $pdo->prepare($sqlCheckChauffeur);
$stmtCheckChauffeur->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
$stmtCheckChauffeur->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
$stmtCheckChauffeur->execute();

if (!$stmtCheckChauffeur->fetchColumn()) {
    echo "Vous n'êtes pas le chauffeur de ce trajet.";
    exit;
}

try {
    $pdo->beginTransaction();

    // Mettre à jour le statut du trajet dans la table trajets
    $sqlUpdateTrajet = "UPDATE trajets 
                        SET statut = 'termine' 
                        WHERE id = :trajet_id";
    $stmtUpdateTrajet = $pdo->prepare($sqlUpdateTrajet);
    $stmtUpdateTrajet->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
    $stmtUpdateTrajet->execute();

    // Mettre à jour le statut des réservations associées à ce trajet
    $sqlUpdateReservations = "UPDATE reservations 
                              SET statut = 'en_cours' 
                              WHERE trajet_id = :trajet_id";
    $stmtUpdateReservations = $pdo->prepare($sqlUpdateReservations);
    $stmtUpdateReservations->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
    $stmtUpdateReservations->execute();

    // Insérer dans l'historique
    $sqlInsert = "INSERT INTO historique (trajet_id, utilisateur_id, date_debut_reel, date_fin_reel, date_enregistrement) 
                  VALUES (:trajet_id, :utilisateur_id, NOW(), NULL, NOW())";
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
    $stmtInsert->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
    $stmtInsert->execute();

    // Valider la transaction
    $pdo->commit();

    // Redirection vers la page des trajets
    header("Location: mesTrajets.php");
    exit;
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Erreur lors du lancement du trajet : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}
?>