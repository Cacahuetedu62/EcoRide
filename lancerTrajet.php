<?php
session_start();
require_once('lib/pdo.php');
require_once('lib/config.php');


// Débogage pour afficher le contenu de $_POST
var_dump($_POST);

if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    $utilisateur_id = $_SESSION['utilisateur']['id'];
    echo "<div class='alert alert-info'>L'utilisateur connecté a l'ID : " . htmlspecialchars($utilisateur_id, ENT_QUOTES, 'UTF-8') . "</div>";
} else {
    // L'utilisateur n'est pas connecté
    echo "Utilisateur non connecté.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Afficher les valeurs reçues pour le débogage
    echo "Méthode de requête : " . htmlspecialchars($_SERVER['REQUEST_METHOD'], ENT_QUOTES, 'UTF-8') . "<br>";
    echo "Trajet ID : " . htmlspecialchars($_POST['trajet_id'] ?? 'Non défini', ENT_QUOTES, 'UTF-8') . "<br>";
    echo "Utilisateur ID : " . htmlspecialchars($_POST['utilisateur_id'] ?? 'Non défini', ENT_QUOTES, 'UTF-8') . "<br>";

    if (isset($_POST['trajet_id']) && isset($_POST['utilisateur_id'])) {
        $trajet_id = (int)$_POST['trajet_id'];
        $utilisateur_id = (int)$_POST['utilisateur_id'];

        // Mettre à jour le statut du trajet à "en_cours"
        $sqlUpdate = "UPDATE reservations SET statut = 'en_cours' WHERE trajet_id = :trajet_id";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);

        // Insérer les informations dans la table historique
        $sqlInsert = "INSERT INTO historique (trajet_id, utilisateur_id, date_debut_reel, date_fin_reel, date_enregistrement) VALUES (:trajet_id, :utilisateur_id, NOW(), NULL, NOW())";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
        $stmtInsert->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);

        try {
            $pdo->beginTransaction();

            // Mettre à jour le statut dans la table des réservations
            $stmtUpdate->execute();
            if ($stmtUpdate->rowCount() === 0) {
                echo "Aucun trajet trouvé avec cet ID ou le statut était déjà 'en_cours'.";
                $pdo->rollBack();
                exit;
            } else {
                echo "Trajet mis à jour avec succès !<br>";
            }

            // Insérer l'historique
            $stmtInsert->execute();
            echo "Historique inséré avec succès !<br>";

            // Commit la transaction
            $pdo->commit();

            // Redirection vers la page des trajets
            header("Location: mesTrajets.php");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo "Erreur lors du lancement du trajet : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            exit;
        }
    } else {
        echo "Aucun trajet valide spécifié.";
        exit;
    }
} else {
    echo "Méthode de requête non valide.";
    exit;
}

require_once('templates/footer.php');
?>
