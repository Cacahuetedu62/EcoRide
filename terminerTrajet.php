<?php
require_once('templates/header.php');
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    $utilisateur_id = $_SESSION['utilisateur']['id'];
} else {
    echo "Utilisateur non connecté.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trajet_id'])) {
    $trajet_id = (int)$_POST['trajet_id'];

    // Activation du mode erreur PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifiez si l'utilisateur est le chauffeur du trajet
    $sqlCheckChauffeur = "SELECT 1
                          FROM trajet_utilisateur
                          WHERE trajet_id = :trajet_id
                          AND utilisateur_id = :utilisateur_id";
    $stmtCheckChauffeur = $pdo->prepare($sqlCheckChauffeur);
    $stmtCheckChauffeur->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
    $stmtCheckChauffeur->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
    $stmtCheckChauffeur->execute();

    if (!$stmtCheckChauffeur->fetchColumn()) {
        echo "Vous n'êtes pas le chauffeur de ce trajet.";
        exit;
    }

    // Requête mise à jour avec condition sur le statut
    $sqlUpdate = "UPDATE reservations
                  SET statut = 'termine'
                  WHERE trajet_id = :trajet_id
                  AND statut = 'en_cours'";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);

    $sqlHistorique = "UPDATE historique SET date_fin_reel = NOW() WHERE trajet_id = :trajet_id";
    $stmtHistorique = $pdo->prepare($sqlHistorique);
    $stmtHistorique->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);

    try {
        $pdo->beginTransaction();

        // Exécution avec vérification
        $stmtUpdate->execute();
        $stmtHistorique->execute();

        $pdo->commit();


echo "<div class='mt-5'>
    <div class='alert alert-success'>
        <h4 class='alert-heading mb-3'>Trajet terminé avec succès !</h4>
        
        <p><i class='fas fa-envelope me-2'></i> Un email a été envoyé à vos passagers pour qu'ils puissent donner leur avis sur le trajet.</p>
        
        <hr>
        
        <p class='mb-0'><strong>Comment recevoir vos crédits ?</strong></p>
        <p>Une fois que le passager aura donné son avis, notre équipe le validera et vos crédits seront automatiquement versés sur votre compte.</p>
        
        <div class='mt-4'>
            <a href='mesTrajets.php' class='btn btn-primary'>Retour à mes trajets</a>
        </div>
    </div>
</div>";

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $config['smtp']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp']['user'];
            $mail->Password = $config['smtp']['pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('rogez.aurore01@gmail.com', 'EcoRide');
            $mail->addAddress('rogez.aurore01@gmail.com');

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;

            $mail->send();


$subject = "Action requise : Validez votre trajet EcoRide";
$message = "
    <html>
    <head>
        <title>Action requise : Validez votre trajet EcoRide</title>
        <meta charset='UTF-8'>
    </head>
    <body>
        <p>Bonjour,</p>
        <p>Votre trajet avec EcoRide est maintenant terminé, mais il reste une dernière étape importante.</p>
        <p>💡 <strong>Pourquoi est-ce important ?</strong></p>
        <ul>
            <li>✅ <strong>Sans validation</strong>, le paiement ne sera pas traité.</li>
            <li>✅ Le conducteur ne recevra pas sa rémunération.</li>
            <li>✅ La plateforme ne pourra pas finaliser la transaction.</li>
        </ul>
        <p>📢 <strong>Nous avons besoin de votre retour !</strong></p>
        <p>Merci de bien vouloir noter et commenter votre trajet pour finaliser le processus.</p>
        <p><a href='http://localhost:3000/avis.php?trajet_id=$trajet_id' style='color: white; background: #28a745; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>💬 Je valide mon trajet</a></p>
        <p>Si vous avez rencontré un problème, contactez notre support.</p>
        <p>Merci pour votre confiance et à bientôt sur EcoRide ! 🚗💚</p>
    </body>
    </html>
";

        } catch (Exception $e) {
            echo "L'email n'a pas pu être envoyé : {$mail->ErrorInfo}";
        }
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

<?php require_once('templates/footer.php'); ?>
