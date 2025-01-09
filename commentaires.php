<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

// Vérifier si un ID d'utilisateur est passé via l'URL
if (isset($_GET['id'])) {
    $utilisateur_id = (int) $_GET['id'];

    // Récupérer les commentaires validés
    $sql = "SELECT a.commentaires, a.note, u.pseudo
            FROM avis a
            JOIN utilisateurs u ON a.utilisateur_id = u.id
            WHERE a.utilisateur_id = :utilisateur_id AND a.statut = 'validé'";

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
