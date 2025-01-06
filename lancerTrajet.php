<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trajet_id'])) {
    $trajet_id = (int)$_POST['trajet_id'];

    // Mettre à jour le statut du trajet à "en_cours" et enregistrer la date et l'heure réelles de début
    $sql = "UPDATE reservations SET statut = 'en_cours', date_debut_reel = NOW() WHERE trajet_id = :trajet_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);

    try {
        $stmt->execute();
        header("Location: mesTrajets.php");
        exit;
    } catch (PDOException $e) {
        echo "Erreur lors du lancement du trajet : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        exit;
    }
} else {
    echo "Aucun trajet valide spécifié.";
    exit;
}


require_once('templates/footer.php');
?>
