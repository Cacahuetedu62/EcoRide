<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    $utilisateur_id = $_SESSION['utilisateur']['id'];
} else {
    // L'utilisateur n'est pas connecté
    echo "Utilisateur non connecté.";
    exit;
}

// Vérifier si le paramètre trajet_id est présent dans l'URL
if (isset($_GET['trajet_id'])) {
    $trajet_id = (int)$_GET['trajet_id'];

    // Vérifier si le trajet existe dans la base de données en utilisant la table reservations
    $sqlCheck = "
        SELECT t.*
        FROM trajets t
        JOIN reservations r ON t.id = r.trajet_id
        WHERE t.id = :trajet_id AND r.utilisateur_id = :utilisateur_id
    ";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
    $stmtCheck->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
    $stmtCheck->execute();

    if ($stmtCheck->rowCount() > 0) {
        // Le trajet existe et appartient à l'utilisateur connecté
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Traiter la soumission du formulaire
            if (isset($_POST['avis']) && isset($_POST['note'])) {
                $commentaires = $_POST['avis'];
                $note = (int)$_POST['note'];
                $statut = 'en attente'; // Définir le statut par défaut à "en attente"

                // Insérer l'avis dans la base de données
                $sqlInsert = "INSERT INTO avis (commentaires, note, statut, utilisateur_id, trajet_id) VALUES (:commentaires, :note, :statut, :utilisateur_id, :trajet_id)";
                $stmtInsert = $pdo->prepare($sqlInsert);
                $stmtInsert->bindParam(':commentaires', $commentaires, PDO::PARAM_STR);
                $stmtInsert->bindParam(':note', $note, PDO::PARAM_INT);
                $stmtInsert->bindParam(':statut', $statut, PDO::PARAM_STR);
                $stmtInsert->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
                $stmtInsert->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);

                try {
                    $stmtInsert->execute();
                    echo "Votre avis a été soumis avec succès et est en attente de validation.";
                } catch (PDOException $e) {
                    echo "Erreur lors de la soumission de l'avis : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
                }
            } else {
                echo "Données de formulaire invalides.";
            }
        } else {
            // Afficher le formulaire pour soumettre un avis
            ?>
            <form method="POST" action="avis.php?trajet_id=<?php echo $trajet_id; ?>">
                <label for="avis">Votre avis :</label>
                <textarea name="avis" id="avis" required></textarea>
                <label for="note">Votre note :</label>
                <select name="note" id="note" required>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
                <button type="submit">Soumettre</button>
            </form>
            <?php
        }
    } else {
        echo "Le trajet spécifié n'existe pas ou ne vous appartient pas.";
        // Ajoutez des messages de débogage
        echo "<br>Trajet ID : " . htmlspecialchars($trajet_id, ENT_QUOTES, 'UTF-8');
        echo "<br>Utilisateur ID : " . htmlspecialchars($utilisateur_id, ENT_QUOTES, 'UTF-8');

        // Vérifiez si le trajet existe dans la table trajets
        $sqlCheckTrajet = "SELECT * FROM trajets WHERE id = :trajet_id";
        $stmtCheckTrajet = $pdo->prepare($sqlCheckTrajet);
        $stmtCheckTrajet->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
        $stmtCheckTrajet->execute();

        if ($stmtCheckTrajet->rowCount() > 0) {
            echo "<br>Le trajet existe dans la table trajets.";
        } else {
            echo "<br>Le trajet n'existe pas dans la table trajets.";
        }

        // Vérifiez si l'utilisateur existe dans la table utilisateurs
        $sqlCheckUtilisateur = "SELECT * FROM utilisateurs WHERE id = :utilisateur_id";
        $stmtCheckUtilisateur = $pdo->prepare($sqlCheckUtilisateur);
        $stmtCheckUtilisateur->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
        $stmtCheckUtilisateur->execute();

        if ($stmtCheckUtilisateur->rowCount() > 0) {
            echo "<br>L'utilisateur existe dans la table utilisateurs.";
        } else {
            echo "<br>L'utilisateur n'existe pas dans la table utilisateurs.";
        }

        // Vérifiez si l'entrée existe dans la table reservations
        $sqlCheckReservation = "SELECT * FROM reservations WHERE trajet_id = :trajet_id AND utilisateur_id = :utilisateur_id";
        $stmtCheckReservation = $pdo->prepare($sqlCheckReservation);
        $stmtCheckReservation->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
        $stmtCheckReservation->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
        $stmtCheckReservation->execute();

        if ($stmtCheckReservation->rowCount() > 0) {
            echo "<br>L'entrée existe dans la table reservations.";
        } else {
            echo "<br>L'entrée n'existe pas dans la table reservations.";
        }
    }
} else {
    echo "Aucun trajet valide spécifié.";
}

require_once('templates/footer.php');
?>
