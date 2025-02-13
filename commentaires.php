<?php require_once('templates/header.php'); ?>

<?php
if (isset($_GET['id'])) {
   $utilisateur_id = (int) $_GET['id'];

   $user_sql = "SELECT nom, prenom FROM utilisateurs WHERE id = :utilisateur_id";
   $user_stmt = $pdo->prepare($user_sql);
   $user_stmt->bindValue(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
   $user_stmt->execute();
   $utilisateur = $user_stmt->fetch(PDO::FETCH_ASSOC);

   $sql = "SELECT
   a.id,
   a.commentaires,
   a.note,
   a.statut,
   a.date_validation,
   u_origine.nom AS nom_origine,
   u_origine.prenom AS prenom_origine,
   t.lieu_depart,
   t.lieu_arrive
FROM
   avis a
JOIN utilisateurs u_origine ON a.utilisateur_id = u_origine.id
JOIN trajets t ON a.trajet_id = t.id
JOIN trajet_utilisateur tu ON t.id = tu.trajet_id
WHERE 
   tu.utilisateur_id = :utilisateur_id 
   AND a.statut = 'valide'
ORDER BY a.date_validation DESC";

   $stmt = $pdo->prepare($sql);
   $stmt->bindValue(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
   $stmt->execute();
   $commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

   if ($commentaires) {
?>

<div class="container mb-4">
    <a href="javascript:history.back()" class="btn btn-primary">
        <i class="fas fa-arrow-left me-2"></i>Retour à la page précédente
    </a>
</div>

<section class="commentairesContainer py-5">
   <div class="row justify-content-center">
       <div class="col-md-10 col-lg-8">
           <h2 class="display-6 text-center mb-5 text-primary">
               Commentaires de <?= htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?>
           </h2>

           <div class="comments-wrapper">
               <?php foreach ($commentaires as $commentaire) : ?>
                   <div class="comment-card mb-4 p-4 border rounded shadow-sm">
                       <div class="comment-meta">
                           <div class="comment-author">
                               <i class="fas fa-user me-2"></i>
                               <?= htmlspecialchars($commentaire['prenom_origine'] . ' ' . $commentaire['nom_origine']) ?>
                           </div>
                           <div class="comment-date">
                               <i class="fas fa-calendar me-2"></i>
                               <?= $commentaire['date_validation'] 
        ? htmlspecialchars(date('d/m/Y H:i', strtotime($commentaire['date_validation']))) 
        : 'Date inconnue' 
    ?>                           </div>
                       </div>

                       <div class="d-flex justify-content-between align-items-center mb-3">
                           <div class="rating-stars">
                               <?php
                               $note = $commentaire['note'] ?? 0;
                               echo str_repeat('🚗', $note);
                               ?>
                           </div>
                           <div class="rating-note">
                               <?= $note ?>/5
                           </div>
                       </div>

                       <div class="comment-body">
                           <p class="text-muted">
                               <?= htmlspecialchars($commentaire['commentaires'] ?? 'Aucun commentaire') ?>
                           </p>
                       </div>

                       <?php if ($commentaire['lieu_depart'] && $commentaire['lieu_arrive']): ?>
                       <div class="comment-trip small mt-3">
                           <i class="fas fa-route me-2"></i>
                           Trajet : <?= htmlspecialchars($commentaire['lieu_depart']) ?> → <?= htmlspecialchars($commentaire['lieu_arrive']) ?>
                       </div>
                       <?php endif; ?>
                   </div>
               <?php endforeach; ?>
           </div>
       </div>
   </div>
</section>
<?php
   } else {
       echo "<div class='text-center py-5'>
               <p class='alert alert-info'>Aucun commentaire trouvé pour cet utilisateur.</p>
             </div>";
   }
} else {
   echo "<div class='text-center py-5'>
           <p class='alert alert-warning'>Aucun utilisateur n'a été sélectionné.</p>
         </div>";
}
?>

<?php require_once('templates/footer.php'); ?>