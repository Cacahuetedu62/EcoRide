<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');
require_once 'vendor/autoload.php'; // Charger l'autoload de Composer pour PHPMailer

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

    // Mettre à jour la table historique avec la date de fin réelle
    $sqlHistorique = "UPDATE historique SET date_fin_reel = NOW() WHERE trajet_id = :trajet_id";
    $stmtHistorique = $pdo->prepare($sqlHistorique);
    $stmtHistorique->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);

    try {
        $pdo->beginTransaction();

        // Mettre à jour le statut du trajet
        $stmtUpdate->execute();

        // Mettre à jour la table historique (si applicable)
        $stmtHistorique->execute();

        // Commit les changements dans la base de données
        $pdo->commit();

        // Envoi de l'email pour notifier l'utilisateur
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

        // Création de l'instance PHPMailer
        $mail = new PHPMailer\PHPMailer\PHPMailer();
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'testing.projets.siteweb@gmail.com'; // Ton email d'expéditeur
        $mail->Password = 'sljw jlop qtyy mqae'; // Le mot de passe d'application pour testing.projets.siteweb@gmail.com
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('testing.projets.siteweb@gmail.com', 'EcoRide'); // Email d'expéditeur
        $mail->addAddress('rogez.aurore01@gmail.com'); // Teste l'email avec ta propre adresse (rogez.aurore01@gmail.com)

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->CharSet = 'UTF-8'; // Spécifier l'encodage des caractères

        if ($mail->send()) {
            echo 'Email envoyé avec succès.';
        } else {
            echo 'Erreur lors de l\'envoi de l\'email : ' . $mail->ErrorInfo;
        }

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

require_once('templates/footer.php');
?>
