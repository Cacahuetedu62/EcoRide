<?php
require_once('lib/pdo.php');
require_once('lib/config.php');
require_once('templates/header.php');

if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    $utilisateur_id = $_SESSION['utilisateur']['id'];
    echo "<div class='alert alert-info'>L'utilisateur connecté a l'ID : " . htmlspecialchars($utilisateur_id, ENT_QUOTES, 'UTF-8') . "</div>";
} else {
    // L'utilisateur n'est pas connecté
    echo "Utilisateur non connecté.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trajet_id'])) {
    $trajet_id = (int)$_POST['trajet_id'];

    // Mettre à jour le statut du trajet à "termine"
    $sqlUpdate = "UPDATE reservations SET statut = 'termine' WHERE trajet_id = :trajet_id";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);

    // Mettre à jour la table historique avec la date de fin réelle
    $sqlHistorique = "UPDATE historique SET date_fin_reel = NOW() WHERE trajet_id = :trajet_id";
    $stmtHistorique = $pdo->prepare($sqlHistorique);
    $stmtHistorique->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);

    // Supprimer le trajet terminé de la table reservations
    $sqlDelete = "DELETE FROM reservations WHERE trajet_id = :trajet_id";
    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);

    try {
        $pdo->beginTransaction();
        $stmtUpdate->execute();
        $stmtHistorique->execute();
        $stmtDelete->execute();
        $pdo->commit();
        header("Location: commentaires.php?id=" . $_SESSION['utilisateur']['id']);
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "Erreur lors de la fin du trajet : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        exit;
    }
} else {
    echo "Aucun trajet valide spécifié.";
    exit;
}

require_once('templates/footer.php');
?>
