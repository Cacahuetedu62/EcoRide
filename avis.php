<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

// Vérifier si le trajet_id est présent
if (!isset($_GET['trajet_id'])) {
    echo "<div class='avis-error'>Aucun trajet spécifié</div>";
    exit;
}

// Si l'utilisateur n'est pas connecté, le rediriger vers la connexion
if (!isset($_SESSION['utilisateur'])) {
    // Encoder l'URL complète pour la redirection après connexion
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: connexion.php?redirect=" . $redirect_url);
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$utilisateur_id = $_SESSION['utilisateur']['id'];
$trajet_id = (int)$_GET['trajet_id'];

// Vérifier si le trajet existe et appartient à l'utilisateur
$sqlCheck = "
    SELECT t.*, tu.utilisateur_id as conducteur_id
    FROM trajets t
    JOIN trajet_utilisateur tu ON t.id = tu.trajet_id
    JOIN reservations r ON t.id = r.trajet_id
    WHERE t.id = :trajet_id 
    AND r.utilisateur_id = :utilisateur_id
";
$stmtCheck = $pdo->prepare($sqlCheck);
$stmtCheck->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
$stmtCheck->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
$stmtCheck->execute();
$trajet = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$trajet) {
    echo "<div class='avis-error'>Le trajet spécifié n'existe pas ou ne vous appartient pas.</div>";
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['avis']) && isset($_POST['note'])) {
        $commentaires = htmlspecialchars($_POST['avis'], ENT_QUOTES, 'UTF-8');
        $note = (int)$_POST['note'];
        $statut = 'en attente';

        // Insérer l'avis dans la base de données
        $sqlInsert = "INSERT INTO avis (commentaires, note, statut, utilisateur_id, trajet_id) 
                      VALUES (:commentaires, :note, :statut, :utilisateur_id, :trajet_id)";
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