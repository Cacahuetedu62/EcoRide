<?php
session_start();
require_once('lib/pdo.php');
require_once('lib/config.php');

// Vérification de l'utilisateur connecté
if (!isset($_SESSION['utilisateur']) || !isset($_SESSION['utilisateur']['id'])) {
    echo "Utilisateur non connecté.";
    exit;
}

// Régénération de l'ID de session pour prévenir les attaques de fixation de session
session_regenerate_id(true);

$utilisateur_id = $_SESSION['utilisateur']['id'];

// Vérification de la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Méthode de requête non valide.";
    exit;
}

// Debug des valeurs POST
echo "<pre>";
var_dump($_POST);
echo "</pre>";

// Validation des champs requis
if (!isset($_POST['trajet_id']) || !isset($_POST['utilisateur_id']) || !isset($_POST['csrf_token'])) {
    echo "Aucun trajet valide spécifié.";
    exit;
}

// Vérification du token CSRF
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo "Token CSRF invalide.";
    exit;
}

// Nettoyage et validation des entrées
$trajet_id = filter_var($_POST['trajet_id'], FILTER_VALIDATE_INT);
$utilisateur_id = filter_var($_POST['utilisateur_id'], FILTER_VALIDATE_INT);

if (!$trajet_id || !$utilisateur_id) {
    echo "ID de trajet ou d'utilisateur invalide.";
    exit;
}

// Debug des valeurs nettoyées
echo "<pre>";
var_dump($trajet_id);
var_dump($utilisateur_id);
echo "</pre>";

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

    // Debug du résultat de la mise à jour du trajet
    echo "Lignes mises à jour (trajets) : " . $stmtUpdateTrajet->rowCount() . "<br>";

    // Mettre à jour le statut des réservations associées à ce trajet
    $sqlUpdateReservations = "UPDATE reservations
                              SET statut = 'en_cours'
                              WHERE trajet_id = :trajet_id";
    $stmtUpdateReservations = $pdo->prepare($sqlUpdateReservations);
    $stmtUpdateReservations->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
    $stmtUpdateReservations->execute();

    // Debug du résultat de la mise à jour des réservations
    echo "Lignes mises à jour (réservations) : " . $stmtUpdateReservations->rowCount() . "<br>";

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
