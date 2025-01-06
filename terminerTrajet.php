<?php
require_once('lib/pdo.php');
require_once('lib/config.php');
require_once('templates/header.php');


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trajet_id'])) {
    $trajet_id = (int)$_POST['trajet_id'];

    // Mettre à jour le statut du trajet à "termine" et enregistrer la date et l'heure réelles de fin
    $sql = "UPDATE reservations SET statut = 'termine', date_fin_reel = NOW() WHERE trajet_id = :trajet_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);

    try {
        $stmt->execute();

        // Déplacer le trajet terminé vers la table d'historique
        $sql_historique = "INSERT INTO trajets_finis (trajet_id, utilisateur_id, date_debut_reel, date_fin_reel)
                           SELECT trajet_id, utilisateur_id, date_debut_reel, date_fin_reel
                           FROM reservations
                           WHERE trajet_id = :trajet_id";
        $stmt_historique = $pdo->prepare($sql_historique);
        $stmt_historique->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
        $stmt_historique->execute();

        // Supprimer le trajet terminé de la table reservations
        $sql_delete = "DELETE FROM reservations WHERE trajet_id = :trajet_id";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
        $stmt_delete->execute();

        header("Location: commentaires.php?id=" . $_SESSION['utilisateur']['id']);
        exit;
    } catch (PDOException $e) {
        echo "Erreur lors de la fin du trajet : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        exit;
    }
} else {
    echo "Aucun trajet valide spécifié.";
    exit;
}

require_once('templates/footer.php');
?>
