<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

// Vérifier si un ID d'utilisateur est passé via l'URL
if (isset($_GET['id'])) {
    $utilisateur_id = (int) $_GET['id'];

    // Traitement du formulaire de commentaire et de notation
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $commentaire = $_POST['commentaire'];
        $note = (int)$_POST['note'];

        // Insérer le commentaire et la note dans la base de données
        $sql = "INSERT INTO avis (utilisateur_id, commentaires, note) VALUES (:utilisateur_id, :commentaire, :note)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
        $stmt->bindParam(':commentaire', $commentaire, PDO::PARAM_STR);
        $stmt->bindParam(':note', $note, PDO::PARAM_INT);

        try {
            $stmt->execute();
            header("Location: commentaires.php?id=" . $utilisateur_id);
            exit;
        } catch (PDOException $e) {
            echo "Erreur lors de l'enregistrement du commentaire : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            exit;
        }
    }

    // Récupérer les commentaires existants
    $sql = "SELECT a.commentaires, a.note, u.pseudo
            FROM avis a
            JOIN utilisateurs u ON a.utilisateur_id = u.id
            WHERE a.utilisateur_id = :utilisateur_id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
    $stmt->execute();
    $commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($commentaires) {
        // Afficher les commentaires
?>


    <section class="commentaires">      
        <h4 class="mb-3 text-center">Commentaires de <?= htmlspecialchars($commentaires[0]['pseudo']) ?></h4>
          <div class="departArrive">
            <div class="row g-3 cardCommentaires">
            <?php foreach ($commentaires as $commentaire) : ?>
                <div class="commentaire">
                    <p>Note :
                        <?php
                            $note = $commentaire['note'];
                            for ($i = 0; $i < $note; $i++) {
                                echo "🚗";
                            }
                        ?>
                    </p>
                    <p><?= htmlspecialchars($commentaire['commentaires']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>


<div class="autresParticipants">
    <div class="form-container">
        <h4>Laisser un commentaire et une note</h4>
        <form action="commentaires.php?id=<?= htmlspecialchars($utilisateur_id, ENT_QUOTES, 'UTF-8') ?>" method="post">
            <div class="form-group">
                <label for="commentaire">Commentaire :</label>
                <textarea name="commentaire" id="commentaire" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <label for="note">Note (1-5) :</label>
                <input type="number" name="note" id="note" class="form-control" min="1" max="5" required>
            </div>
            <button type="submit" class="btn btn-primary">Soumettre</button>
        </form>
    </div>
    </div>
    </section>
<?php
    } else {
        echo "Aucun commentaire trouvé.";
    }
} else {
    echo "Aucun utilisateur sélectionné.";
}

require_once('templates/footer.php');
?>
