<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');
require_once 'vendor/autoload.php'; // Charger l'autoload de Composer pour PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    $utilisateur_id = $_SESSION['utilisateur']['id'];
    $utilisateur_email = $_SESSION['utilisateur']['email']; // Assurez-vous que l'email de l'utilisateur est stocké dans la session
} else {
    echo "Utilisateur non connecté.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trajet_id'])) {
    $trajet_id = (int)$_POST['trajet_id'];

    // Mettre à jour le statut du trajet à "terminé"
    $sqlUpdate = "UPDATE trajets SET statut = 'terminé' WHERE id = :trajet_id";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);

    // Mettre à jour la table historique avec la date de fin réelle
    $sqlHistorique = "UPDATE historique SET date_fin_reel = NOW() WHERE trajet_id = :trajet_id";
    $stmtHistorique = $pdo->prepare($sqlHistorique);
    $stmtHistorique->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);

    try {
        // Démarre la transaction
        $pdo->beginTransaction();
        $stmtUpdate->execute();
        $stmtHistorique->execute();
        $pdo->commit();

        // Envoi de l'email avec PHPMailer
        $subject = "Votre trajet est terminé";
        $message = "
            <html>
            <head>
                <title>Votre trajet est terminé</title>
                <meta charset='UTF-8'>
            </head>
            <body>
                <p>Bonjour,</p>
                <p>Le trajet que vous avez effectué est maintenant terminé. Rendez-vous sur votre espace pour donner votre avis et votre note.</p>
                <p><a href='http://localhost:3000/avis.php?trajet_id=$trajet_id'>Cliquez ici pour accéder à votre espace et soumettre votre avis</a></p>
                <p>Merci pour votre participation!</p>
            </body>
            </html>
        ";

        // Crée une instance de PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Configuration SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'testing.projets.siteweb@gmail.com'; // Adresse email d'expéditeur
            $mail->Password = 'sljw jlop qtyy mqae'; // Mot de passe d'application Gmail
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Destinataire
            $mail->setFrom('rogez.aurore01@gmail.com', 'EcoRide');
            $mail->addAddress('rogez.aurore01@gmail.com'); // Envoi à ton adresse email pour les tests


            // Contenu de l'email
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;

            // Envoie de l'email
            $mail->send();
            echo "Message envoyé avec succès !"; // Message de débogage
        } catch (Exception $e) {
            echo "L'email n'a pas pu être envoyé : {$mail->ErrorInfo}"; // Message d'erreur si l'email échoue
        }

        // Afficher le modal de remerciement et redirection
        echo '<script>
                window.onload = function() {
                    $("#merciModal").modal("show");
                    setTimeout(function() {
                        window.location.href = "espace.php"; // Rediriger après 3 secondes
                    }, 3000);
                }
              </script>';
    } catch (Exception $e) {
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
