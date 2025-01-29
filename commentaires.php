<?php
require_once('templates/header.php');

// Vérifier si un ID d'utilisateur est passé via l'URL
if (isset($_GET['id'])) {
    $utilisateur_id = (int) $_GET['id'];

    // Structure de requête améliorée
    $sql = "
        SELECT 
            a.commentaires, 
            a.note, 
            u.pseudo
        FROM 
            avis a
        JOIN 
            utilisateurs u ON a.utilisateur_id = u.id
        WHERE 
            a.utilisateur_id = :utilisateur_id 
            AND a.statut = 'validé'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
    $stmt->execute();
    $commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($commentaires) {
        // Amélioration de la présentation des commentaires
?>
<section class="commentaires container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="display-6 text-center mb-4">
                Commentaires de <?= htmlspecialchars($commentaires[0]['pseudo']) ?>
            </h2>

            <div class="comments-wrapper">
                <?php foreach ($commentaires as $commentaire) : ?>
                    <div class="comment-card mb-3 p-3 border rounded shadow-sm">
                        <div class="comment-header d-flex justify-content-between align-items-center mb-2">
                            <div class="rating">
                                <?php
                                $note = $commentaire['note'];
                                $rating_html = implode('', array_fill(0, $note, '🚗'));
                                echo htmlspecialchars($rating_html);
                                ?>
                            </div>
                        </div>
                        
                        <div class="comment-body">
                            <p class="text-muted">
                                <?= htmlspecialchars($commentaire['commentaires']) ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php
    } else {
        // Message d'absence de commentaires plus élégant
        echo "<div class='container text-center py-5'>
                <p class='alert alert-info'>Aucun commentaire trouvé pour cet utilisateur.</p>
              </div>";
    }
} else {
    // Message d'absence de sélection d'utilisateur plus élégant
    echo "<div class='container text-center py-5'>
            <p class='alert alert-warning'>Aucun utilisateur n'a été sélectionné.</p>
          </div>";
}

require_once('templates/footer.php');
?>