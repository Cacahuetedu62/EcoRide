<?php
require_once('templates/header.php');

// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    $utilisateur_id = $_SESSION['utilisateur']['id'];
} else {
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
                $commentaires = htmlspecialchars($_POST['avis'], ENT_QUOTES, 'UTF-8');
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
                    echo "<div class='avis-success'>Votre avis a été soumis avec succès et est en attente de validation.</div>";
                } catch (PDOException $e) {
                    echo "<div class='avis-error'>Erreur lors de la soumission de l'avis : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</div>";
                }
            } else {
                echo "<div class='avis-error'>Données de formulaire invalides.</div>";
            }
        } else {
            // Afficher le formulaire pour soumettre un avis
            ?>
            <div class="avis-form-container">
                <form method="POST" action="avis.php?trajet_id=<?php echo htmlspecialchars($trajet_id, ENT_QUOTES, 'UTF-8'); ?>" id="form-avis">
                    <label for="avis">Votre avis :</label>
                    <textarea name="avis" id="avis" required></textarea>
                    <div id="error-avis" class="avis-error-message"></div>

                    <label for="note">Votre note :</label>
                    <select name="note" id="note" required>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                    <div id="error-note" class="avis-error-message"></div>

                    <button type="submit">Soumettre</button>
                </form>
            </div>
            <?php
        }
    } else {
        echo "<div class='avis-error'>Le trajet spécifié n'existe pas ou ne vous appartient pas.</div>";
    }
} else {
    echo "<div class='avis-error'>Aucun trajet valide spécifié.</div>";
}

require_once('templates/footer.php');
?>

<script>
    // Fonction de validation côté client
    document.getElementById('form-avis').addEventListener('submit', function(event) {
        let valid = true;
        let avis = document.getElementById('avis').value;
        let note = document.getElementById('note').value;
        let errorAvis = document.getElementById('error-avis');
        let errorNote = document.getElementById('error-note');

        // Réinitialiser les messages d'erreur
        errorAvis.textContent = '';
        errorNote.textContent = '';

        // Validation de l'avis
        if (avis.trim().length < 10) {
            errorAvis.textContent = 'L\'avis doit contenir au moins 10 caractères.';
            valid = false;
        }

        // Validation de la note
        if (!note) {
            errorNote.textContent = 'Veuillez sélectionner une note.';
            valid = false;
        }

        // Si une erreur a été détectée, empêcher la soumission du formulaire
        if (!valid) {
            event.preventDefault();
        }
    });
</script>
