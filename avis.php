<?php
session_start();
require_once('lib/pdo.php');
require_once('lib/config.php');

// Vérification de l'utilisateur connecté
if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    $utilisateur_id = $_SESSION['utilisateur']['id'];
} else {
    echo "Utilisateur non connecté.";
    exit;
}

// Vérification de l'ID du trajet
if (isset($_GET['trajet_id'])) {
    $trajet_id = (int)$_GET['trajet_id'];

    // Récupérer les détails du trajet
    $sql = "SELECT * FROM trajets WHERE id = :trajet_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
    $stmt->execute();
    $trajet = $stmt->fetch();

    if (!$trajet) {
        echo "Trajet non trouvé.";
        exit;
    }

    // Récupérer les détails du conducteur
    $sqlConducteur = "SELECT * FROM utilisateurs WHERE id = :conducteur_id";
    $stmtConducteur = $pdo->prepare($sqlConducteur);
    $stmtConducteur->bindParam(':conducteur_id', $trajet['conducteur_id'], PDO::PARAM_INT);
    $stmtConducteur->execute();
    $conducteur = $stmtConducteur->fetch();
} else {
    echo "Aucun trajet spécifié.";
    exit;
}

// Traitement du formulaire d'avis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note'])) {
    $note = (int)$_POST['note'];
    $commentaire = htmlspecialchars($_POST['commentaire'], ENT_QUOTES, 'UTF-8');

    // Insérer l'avis dans la base de données avec le statut 'en_attente'
    $sqlAvis = "INSERT INTO avis (utilisateur_id, trajet_id, note, commentaire, statut) 
                VALUES (:utilisateur_id, :trajet_id, :note, :commentaire, 'en_attente')";
    $stmtAvis = $pdo->prepare($sqlAvis);
    $stmtAvis->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
    $stmtAvis->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
    $stmtAvis->bindParam(':note', $note, PDO::PARAM_INT);
    $stmtAvis->bindParam(':commentaire', $commentaire, PDO::PARAM_STR);

    try {
        $stmtAvis->execute();
        echo "Merci ! Votre avis a été soumis et est en attente de validation.";
    } catch (PDOException $e) {
        echo "Erreur lors de la soumission de l'avis : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Récapitulatif de votre trajet</title>
</head>
<body>
    <h1>Récapitulatif du trajet</h1>
    <p><strong>Lieu de départ :</strong> <?php echo htmlspecialchars($trajet['depart'], ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Lieu d'arrivée :</strong> <?php echo htmlspecialchars($trajet['arrivee'], ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Date :</strong> <?php echo date("d/m/Y H:i", strtotime($trajet['date_depart'])); ?></p>
    <p><strong>Conducteur :</strong> <?php echo htmlspecialchars($conducteur['prenom'], ENT_QUOTES, 'UTF-8') . " " . htmlspecialchars($conducteur['nom'], ENT_QUOTES, 'UTF-8'); ?></p>

    <h2>Évaluez ce trajet</h2>
    <form method="POST">
        <label for="note">Note (obligatoire) :</label>
        <input type="number" id="note" name="note" min="1" max="5" required><br><br>

        <label for="commentaire">Commentaire (facultatif) :</label><br>
        <textarea id="commentaire" name="commentaire" rows="4" cols="50"></textarea><br><br>

        <button type="submit">Soumettre</button>
    </form>
</body>
</html>
