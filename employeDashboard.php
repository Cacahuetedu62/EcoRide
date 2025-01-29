<?php
require_once('templates/header.php');
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use MongoDB\Client;

// Vérification de session sécurisée
if (!isset($_SESSION['utilisateur']) || !isset($_SESSION['utilisateur']['type_acces']) 
    || ($_SESSION['utilisateur']['type_acces'] != 'employe' && $_SESSION['utilisateur']['type_acces'] != 'administrateur')) {
    header('Location: login.php');
    exit();
}

// Initialize MongoDB connection
try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $creditsPlateforme = $mongoClient->creditsPlateforme;
    $creditsCollection = $creditsPlateforme->credits;
} catch (Exception $e) {
    error_log("MongoDB Connection Error: " . $e->getMessage());
}

// Fonction pour tronquer le texte
function tronquer_texte($texte, $limite = 50) {
    if (strlen($texte) > $limite) {
        return substr($texte, 0, $limite) . '...';
    }
    return $texte;
}

function validateComment($comment) {
    if (empty($comment)) {
        return '';
    }
    
    // Sanitize first
    $comment = trim($comment);
    $comment = strip_tags($comment);
    
    // Liste étendue de motifs malveillants
    $maliciousPatterns = array(
        '/SELECT\s+.*\s+FROM/i',
        '/INSERT\s+INTO/i',
        '/UPDATE\s+.*\s+SET/i',
        '/DELETE\s+FROM/i',
        '/UNION\s+SELECT/i',
        '/DROP\s+TABLE/i',
        '/ALTER\s+TABLE/i',
        '/EXECUTE\s+/i',
        '/EXEC\s+/i',
        '/--/',
        '/;/',
        '/\/\*.*\*\//',
        '/<script/i',
        '/javascript:/i',
        '/onclick/i',
        '/onerror/i',
        '/onload/i',
        '/eval\s*\(/i'
    );

    foreach ($maliciousPatterns as $pattern) {
        if (preg_match($pattern, $comment)) {
            error_log("Contenu malveillant détecté: " . htmlspecialchars($comment));
            return false;
        }
    }
    
    return $comment;
}


function gererCredits($pdo, $creditsCollection, $avis_utilisateur_id, $trajet_id) {
    try {
        // Début de la transaction MySQL
        $pdo->beginTransaction();

        // Récupérer le prix du trajet et l'ID du chauffeur via la jointure
        $sql = "SELECT 
                t.prix_personnes, 
                t.prix_total, 
                t.nb_places,
                tu.utilisateur_id as chauffeur_id
                FROM trajets t
                JOIN trajet_utilisateur tu ON t.id = tu.trajet_id
                JOIN utilisateurs u ON tu.utilisateur_id = u.id
                WHERE t.id = :trajet_id 
                AND u.role = 'chauffeur'";
                
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
        $stmt->execute();
        $trajetInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$trajetInfo) {
            throw new Exception("Trajet non trouvé ou chauffeur non trouvé");
        }

        // Calculer commission plateforme (2 crédits)
        $commission = 2;
        
        // On utilise prix_personnes car c'est le prix par personne
        $prix_pour_passager = $trajetInfo['prix_personnes'];
        $montant_final = $prix_pour_passager - $commission;

        // 1. Ajouter les crédits de commission à MongoDB (plateforme)
        $dateNow = new DateTime();
        $formattedDate = $dateNow->format('Y-m-d H:i:s');
        $creditsCollection->insertOne([
            'montant' => $commission,
            'type' => 'commission_avis',
            'date' => $formattedDate,
            'description' => 'Commission sur validation d\'avis'
        ]);

        // 2. Débiter les crédits de l'utilisateur dans MySQL
        $sql = "UPDATE utilisateurs SET credits = credits - :montant WHERE id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':montant', $prix_pour_passager, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $avis_utilisateur_id, PDO::PARAM_INT);
        $stmt->execute();

        // 3. Créditer le chauffeur
        $sql = "UPDATE utilisateurs SET credits = credits + :montant_final WHERE id = :chauffeur_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':montant_final', $montant_final, PDO::PARAM_STR);
        $stmt->bindParam(':chauffeur_id', $trajetInfo['chauffeur_id'], PDO::PARAM_INT);
        $stmt->execute();

        // Si tout s'est bien passé, on valide la transaction
        $pdo->commit();
        return true;

    } catch (Exception $e) {
        // En cas d'erreur, on annule la transaction
        $pdo->rollBack();
        error_log("Erreur lors de la gestion des crédits: " . $e->getMessage());
        return false;
    }
}

function envoyer_email_rejet($destinataire, $nom_utilisateur) {
    $mail = new PHPMailer(true);
    try {
        // Configuration du serveur SMTP avec les constantes
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // En développement, tous les emails vont à votre adresse
        $destinataire_test = 'rogez.aurore01@gmail.com';
        
        // Configuration de l'expéditeur et du destinataire
        $mail->setFrom(SMTP_USER, 'EcoRide');
        $mail->addAddress($destinataire_test);

        // Configuration du contenu de l'email
        $mail->isHTML(true);
        $mail->Subject = '[TEST] Rejet d\'avis - EcoRide';
        
        // Corps du message en HTML avec info sur l'utilisateur original
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 20px; border-radius: 4px;'>
                    <strong>Mode Test :</strong><br>
                    Email original destiné à : {$destinataire}<br>
                    Nom utilisateur : {$nom_utilisateur}
                </div>

                <h2 style='color: #e74c3c;'>Avis rejeté</h2>
                <p>Bonjour {$nom_utilisateur},</p>
                <p>Nous sommes désolés de vous informer que votre avis a été rejeté par notre équipe de modération.</p>
                <p>Voici les raisons possibles du rejet :</p>
                <ul>
                    <li>Contenu inapproprié ou offensant</li>
                    <li>Informations hors sujet</li>
                    <li>Non-respect des règles de la communauté</li>
                </ul>
                <p>Vous pouvez soumettre un nouvel avis en respectant nos directives de la communauté.</p>
                <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>
                <p style='margin-top: 20px;'>Cordialement,<br>L'équipe EcoRide</p>
            </div>
        </body>
        </html>";

        $mail->Body = $message;
        // Version texte pour les clients mail qui ne supportent pas l'HTML
        $mail->AltBody = "[TEST] Email original destiné à : {$destinataire}\n"
                      . "Nom utilisateur : {$nom_utilisateur}\n\n"
                      . "Bonjour {$nom_utilisateur},\n\n"
                      . "Votre avis a été rejeté par notre équipe de modération.\n\n"
                      . "Vous pouvez soumettre un nouvel avis en respectant nos directives de la communauté.\n\n"
                      . "Cordialement,\nL'équipe EcoRide";

        $mail->CharSet = 'UTF-8';

        // Ajout du mode debug si activé
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = 'error_log';
        }

        $mail->send();
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("Email de test envoyé avec succès à {$destinataire_test} (destinataire original : {$destinataire})");
        }
        
        return true;

    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi de l'email de rejet: " . $mail->ErrorInfo);
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("Détails supplémentaires: " . $e->getMessage());
            error_log("Destinataire test: {$destinataire_test}");
            error_log("Destinataire original: {$destinataire}");
            error_log("Nom utilisateur: {$nom_utilisateur}");
        }
        
        return false;
    }
}
// La requête SQL pour récupérer les avis
$sql = "SELECT
    t.id as trajet_id,
    t.lieu_depart,
    t.lieu_arrive,
    a.id as avis_id,
    a.commentaires,
    a.note,
    a.statut,
    a.utilisateur_id as avis_utilisateur_id,
    a.trajet_id,
    GROUP_CONCAT(
        DISTINCT
        CASE
            WHEN u.role = 'chauffeur' THEN u.pseudo
            ELSE u.pseudo
        END,
        '|',
        CASE
            WHEN u.role = 'chauffeur' THEN 'chauffeur'
            ELSE 'passager'
        END
        SEPARATOR ';'
    ) as participants_info,
    GROUP_CONCAT(
        DISTINCT
        CASE
            WHEN u.role = 'chauffeur' THEN u.email
            ELSE u.email
        END,
        '|',
        CASE
            WHEN u.role = 'chauffeur' THEN 'chauffeur'
            ELSE 'passager'
        END
        SEPARATOR ';'
    ) as emails_info,
    GROUP_CONCAT(
        DISTINCT
        u2.pseudo,
        '|',
        'passager'
        SEPARATOR ';'
    ) as additional_passengers_info,
    GROUP_CONCAT(
        DISTINCT
        u2.email,
        '|',
        'passager'
        SEPARATOR ';'
    ) as additional_passengers_emails,
    GROUP_CONCAT(
        DISTINCT
        CONCAT(p.nom_passager, ' ', p.prenom_passager),
        '|',
        'passager'
        SEPARATOR ';'
    ) as reservation_passengers_info
FROM trajets t
JOIN avis a ON t.id = a.trajet_id
JOIN historique h ON t.id = h.trajet_id
JOIN trajet_utilisateur tu ON t.id = tu.trajet_id
JOIN utilisateurs u ON tu.utilisateur_id = u.id
LEFT JOIN reservations r ON t.id = r.trajet_id
LEFT JOIN utilisateurs u2 ON r.utilisateur_id = u2.id
LEFT JOIN passagers p ON r.id = p.reservation_id
WHERE a.statut = 'en attente'
AND h.date_fin_reel IS NOT NULL
GROUP BY t.id, a.id";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour afficher les participants avec des badges
function format_with_badges($info_string) {
    if (!$info_string) return '';
    $participants = explode(';', $info_string);
    $formatted = '';
    foreach ($participants as $participant) {
        if (empty($participant)) continue;
        list($value, $role) = explode('|', $participant);
        $badge_class = $role === 'chauffeur' ? 'badge-conducteur' : 'badge-passager';
        $formatted .= '<div class="participant">';
        $formatted .= htmlspecialchars($value);
        $formatted .= '<span class="badge ' . $badge_class . '">' . ucfirst($role) . '</span>';
        $formatted .= '</div>';
    }
    return $formatted;
}

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['avis_id']) && isset($_POST['action'])) {
        $avis_id = $_POST['avis_id'];
        $action = $_POST['action'];
        $new_status = ($action == 'valider') ? 'validé' : 'rejeté';

        if ($action == 'valider') {
            // Récupérer l'ID de l'utilisateur et le trajet_id qui a fait l'avis
            $sql = "SELECT utilisateur_id, trajet_id FROM avis WHERE id = :avis_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':avis_id', $avis_id, PDO::PARAM_INT);
            $stmt->execute();
            $avis_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
            if (gererCredits($pdo, $creditsCollection, $avis_info['utilisateur_id'], $avis_info['trajet_id'])) {
                // Mettre à jour le statut de l'avis ET la date de validation
                $sql = "UPDATE avis 
                        SET statut = :new_status, 
                            date_validation = NOW() 
                        WHERE id = :avis_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':new_status', $new_status);
                $stmt->bindParam(':avis_id', $avis_id, PDO::PARAM_INT);
        
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Avis validé et crédits mis à jour avec succès.</div>";
                } else {
                    echo "<div class='alert alert-danger'>Erreur lors de la mise à jour du statut.</div>";
                }
            } else {
                echo "<div class='alert alert-danger'>Erreur lors de la gestion des crédits.</div>";
            }
        } else {
            // Cas du rejet
            $sql = "UPDATE avis SET statut = :new_status WHERE id = :avis_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':new_status', $new_status);
            $stmt->bindParam(':avis_id', $avis_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                // Récupérer les informations de l'utilisateur
                $sql = "SELECT u.email, u.pseudo FROM avis a JOIN utilisateurs u ON a.utilisateur_id = u.id WHERE a.id = :avis_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':avis_id', $avis_id, PDO::PARAM_INT);
                $stmt->execute();
                $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

                envoyer_email_rejet($user_info['email'], $user_info['pseudo']);
                echo "<div class='alert alert-danger'>Avis rejeté et email envoyé à l'utilisateur.</div>";
            } else {
                echo "<div class='alert alert-danger'>Erreur lors du rejet de l'avis.</div>";
            }
        }
    }
}
?>

<div id="commentModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Commentaire complet</h2>
        <p id="modalCommentText"></p>
    </div>
</div>
<div class="dashboard-container">
    <h1 class="dashboard-title">Gestion des Avis Clients</h1>
    <table class="dashboard-table">
        <thead>
            <tr>
                <th>N° Avis</th> <!-- Nouvelle colonne pour l'ID de l'avis -->
                <th>N° Trajet</th>
                <th>Participants</th>
                <th>Emails</th>
                <th>Départ</th>
                <th>Arrivée</th>
                <th>Commentaires</th>
                <th>Note</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($results) > 0): ?>
                <?php foreach ($results as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row["avis_id"]); ?></td> <!-- Affichage du N° Avis -->
                    <td><?php echo htmlspecialchars($row["trajet_id"]); ?></td>
                    <td>
                        <?php echo format_with_badges($row["participants_info"]); ?>
                        <?php echo format_with_badges($row["additional_passengers_info"]); ?>
                        <?php echo format_with_badges($row["reservation_passengers_info"]); ?>
                    </td>
                    <td>
                        <?php echo format_with_badges($row["emails_info"]); ?>
                        <?php echo format_with_badges($row["additional_passengers_emails"]); ?>
                    </td>
                    <td><?php echo htmlspecialchars($row["lieu_depart"]); ?></td>
                    <td><?php echo htmlspecialchars($row["lieu_arrive"]); ?></td>
                    <td>
                        <?php
                       $commentaire = validateComment($row["commentaires"]);
                       if ($commentaire === false) {
                           echo "Commentaire non valide";
                       } else {
                           $commentaire_tronque = tronquer_texte($commentaire);
                           echo htmlspecialchars($commentaire_tronque, ENT_QUOTES, 'UTF-8');
                           if (strlen($commentaire) > 50) {
                               // Stockage sécurisé du commentaire complet
                               $commentaire_securise = htmlspecialchars($commentaire, ENT_QUOTES, 'UTF-8');
                               echo '<button class="btn-voir-plus" 
                                      data-commentaire="' . $commentaire_securise . '"
                                      data-avis-id="' . htmlspecialchars($row["avis_id"], ENT_QUOTES, 'UTF-8') . '">
                                      Voir plus
                                    </button>';
                           }
                       }
                       ?>
                   </td>
                    <td><?php echo htmlspecialchars($row["note"]); ?></td>
                    <td><?php echo htmlspecialchars($row["statut"]); ?></td>
                    <td class="actions">
                    <form method="post" action="" class="action-buttons" onsubmit="return handleSubmit(this);">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" name="avis_id" value="<?php echo $row["avis_id"]; ?>">
            <button type="submit" name="action" value="valider" class="btn btn-validate">Valider</button>
        <button type="submit" name="action" value="rejeter" class="btn btn-reject">Rejeter</button>
    </form>
</td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="no-data">Aucun avis en attente pour les trajets terminés.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="commentModal" class="modal" role="dialog" aria-labelledby="modalTitle" aria-modal="true">
    <div class="modal-content">
        <span class="close" aria-label="Fermer">&times;</span>
        <h2 id="modalTitle">Commentaire complet</h2>
        <div id="modalCommentText" class="comment-content"></div>
        <div id="warningMessage" class="warning-message" style="display: none;">
            <div class="warning-icon">⚠️</div>
            <div class="warning-text">
                <strong>ALERTE DE SÉCURITÉ</strong>
                <p>Ce commentaire contient du contenu potentiellement malveillant !</p>
                <ul>
                    <li>Il pourrait contenir une tentative d'injection SQL</li>
                    <li>Le contenu a été détecté comme suspect</li>
                    <li>Veuillez vérifier attentivement avant validation</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('commentModal');
    const modalText = document.getElementById('modalCommentText');
    const warningMessage = document.getElementById('warningMessage');
    const closeBtn = document.querySelector('.close');
    
    function detectSuspiciousContent(text) {
    // Encoder le texte avant de l'afficher
    text = text.replace(/&/g, '&amp;')
               .replace(/</g, '&lt;')
               .replace(/>/g, '&gt;')
               .replace(/"/g, '&quot;')
               .replace(/'/g, '&#039;');
               
    const suspiciousPatterns = [
        /<script/i,
        /javascript:/i,
        /onclick/i,
        /onerror/i,
        /onload/i,
        /SELECT.*FROM/i,
        /UNION.*SELECT/i,
        /data:/i,
        /base64/i
    ];
    
    return suspiciousPatterns.some(pattern => pattern.test(text));
}

    // Gestionnaire pour les boutons de contenu suspect
    document.querySelectorAll('.btn-voir-contenu-suspect').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm("⚠️ ATTENTION : Vous êtes sur le point d'afficher un contenu potentiellement dangereux. Êtes-vous sûr de vouloir continuer ?")) {
                const commentaire = this.getAttribute('data-commentaire');
                modalText.textContent = commentaire;
                warningMessage.style.display = 'block';
                modal.style.display = 'block';
            }
        });
    });

    // Gestionnaire pour les boutons "Voir plus"
    document.querySelectorAll('.btn-voir-plus').forEach(button => {
        button.addEventListener('click', function() {
            const commentaire = this.getAttribute('data-commentaire');
            modalText.textContent = commentaire;
            warningMessage.style.display = detectSuspiciousContent(commentaire) ? 'block' : 'none';
            modal.style.display = 'block';
        });
    });

    // Gestionnaires de fermeture
    function closeModal() {
        modal.style.display = 'none';
        modalText.textContent = '';
        warningMessage.style.display = 'none';
    }

    closeBtn.addEventListener('click', closeModal);
    window.addEventListener('click', e => e.target === modal && closeModal());
    document.addEventListener('keydown', e => e.key === 'Escape' && closeModal());
});
function confirmAction(form) {
    const actionButton = document.activeElement;
    const isRejet = actionButton.value === 'rejeter';
    const message = isRejet 
        ? 'Êtes-vous sûr de vouloir rejeter cet avis ? Un email sera envoyé à l\'utilisateur.' 
        : 'Êtes-vous sûr de vouloir valider cet avis ? Les crédits seront transférés.';
        
    if (confirm(message)) {
        // Laisser le temps au formulaire de se soumettre avant de recharger
        setTimeout(() => {
            window.location.reload();
        }, 100);
        return true;
    }
    return false;
}

</script>

