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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trajet_id'])) {
    $trajet_id = (int)$_POST['trajet_id'];

    // Mettre à jour le statut du trajet à "terminé" dans la table trajets
    $sqlUpdate = "UPDATE trajets SET statut = 'terminé' WHERE id = :trajet_id";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);

    // Mettre à jour la table historique avec la date de fin réelle (si nécessaire)
    $sqlHistorique = "UPDATE historique SET date_fin_reel = NOW() WHERE trajet_id = :trajet_id";
    $stmtHistorique = $pdo->prepare($sqlHistorique);
    $stmtHistorique->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);

    // Supprimer le trajet terminé de la table reservations (si nécessaire)
    $sqlDelete = "DELETE FROM reservations WHERE trajet_id = :trajet_id";
    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);

    try {
        $pdo->beginTransaction();

        // Mettre à jour le statut du trajet
        $stmtUpdate->execute();

        // Mettre à jour la table historique (si applicable)
        $stmtHistorique->execute();

        // Supprimer la réservation du trajet (si nécessaire)
        $stmtDelete->execute();

        // Commit les changements dans la base de données
        $pdo->commit();

        // Envoi de l'email pour notifier l'utilisateur
        $utilisateur_email = $_SESSION['utilisateur']['email']; // Récupère l'email de l'utilisateur

        $subject = "Votre trajet est terminé";
        $message = "
            <html>
            <head>
                <title>Votre trajet est terminé</title>
            </head>
            <body>
                <p>Bonjour,</p>
                <p>Le trajet que vous avez effectué est maintenant terminé. Rendez-vous sur votre espace pour donner votre avis et votre note.</p>
                <p><a href='http://votre-site.com/avis.php?trajet_id=$trajet_id'>Cliquez ici pour accéder à votre espace et soumettre votre avis</a></p>
                <p>Merci pour votre participation!</p>
            </body>
            </html>
        ";

        // Fonction pour envoyer l'email
        function envoyerEmail($to, $subject, $message) {
            $headers = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
            $headers .= 'From: no-reply@votre-site.com' . "\r\n";
            mail($to, $subject, $message, $headers);
        }

        // Envoie de l'email
        envoyerEmail($utilisateur_email, $subject, $message);

        // Afficher le modal de remerciement
        echo '<script>
                window.onload = function() {
                    $("#merciModal").modal("show");
                    setTimeout(function() {
                        window.location.href = "espace.php"; // Rediriger après 3 secondes
                    }, 3000);
                }
              </script>';

    } catch (PDOException $e) {
        // En cas d'erreur, on annule la transaction
        $pdo->rollBack();
        echo "Erreur lors de la fin du trajet : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        exit;
    }
} else {
    echo "Aucun trajet valide spécifié.";
    exit;
}
?>

<!-- Modal Bootstrap -->
<div class="modal fade" id="merciModal" tabindex="-1" aria-labelledby="merciModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="merciModalLabel">Merci !</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Merci de nous avoir fait confiance ! Votre trajet est maintenant terminé. À bientôt.</p>
      </div>
    </div>
  </div>
</div>

<?php require_once('templates/footer.php'); ?>
