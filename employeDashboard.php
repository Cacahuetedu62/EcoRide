<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');
require_once('vendor/autoload.php'); // Ajoutez cette ligne pour inclure PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fonction pour tronquer le texte
function tronquer_texte($texte, $limite = 50) {
    if (strlen($texte) > $limite) {
        return substr($texte, 0, $limite) . '...';
    }
    return $texte;
}

// La requête SQL corrigée
$sql = "SELECT
    t.id as trajet_id,
    t.lieu_depart,
    t.lieu_arrive,
    a.id as avis_id,
    a.commentaires,
    a.note,
    a.statut,
    a.utilisateur_id as avis_utilisateur_id,
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

function format_with_badges($info_string) {
    $participants = explode(';', $info_string);
    $formatted = '';
    foreach ($participants as $participant) {
        list($value, $role) = explode('|', $participant);
        $badge_class = $role === 'chauffeur' ? 'badge-conducteur' : 'badge-passager';
        $formatted .= '<div class="participant">';
        $formatted .= htmlspecialchars($value);
        $formatted .= '<span class="badge ' . $badge_class . '">' . ucfirst($role) . '</span>';
        $formatted .= '</div>';
    }
    return $formatted;
}

// Fonction pour envoyer un email de rejet
function envoyer_email_rejet($destinataire, $nom_utilisateur) {
    $mail = new PHPMailer(true);

    try {
        // Configuration du serveur SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'testing.projets.siteweb@gmail.com';
        $mail->Password = 'sljw jlop qtyy mqae';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Destinataire
        $mail->setFrom('testing.projets.siteweb@gmail.com', 'Équipe de modération');
        $mail->addAddress($destinataire, $nom_utilisateur);

        // Contenu de l'email
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8'; // Assurez-vous que le contenu est encodé en UTF-8
        $mail->Subject = 'Votre avis n\'a pas été publié';
        $mail->Body    = "Bonjour !<br><br>
                         Nous vous remercions d'avoir pris le temps de partager votre expérience sur Ecoride.fr.<br><br>
                         Nous avons été amenés à ne pas publier votre avis car il ne respecte pas nos conditions générales de modération.<br><br>
                         Si vous souhaitez soumettre à nouveau votre avis, nous vous invitons à le modifier en tenant compte de nos directives de publication.<br><br>
                         Pour toute question ou clarification concernant cette décision, n'hésitez pas à nous contacter à l'adresse moderation@ecoride.com. Notre équipe se fera un plaisir de vous accompagner.<br><br>
                         Vous pouvez soumettre à nouveau votre avis après l'avoir adapté en vous connectant à votre compte.<br><br>
                         Cordialement,<br>
                         L'équipe de modération";
        $mail->send();
        echo 'Email envoyé !';
    } catch (Exception $e) {
        echo "L'email n'a pas pu être envoyé. {$mail->ErrorInfo}";
    }
}

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['avis_id']) && isset($_POST['action'])) {
        $avis_id = $_POST['avis_id'];
        $action = $_POST['action'];

        $new_status = ($action == 'valider') ? 'validé' : 'rejeté';

        $sql = "UPDATE avis SET statut = :new_status WHERE id = :avis_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':new_status', $new_status);
        $stmt->bindParam(':avis_id', $avis_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            if ($new_status == 'rejeté') {
                // Utiliser l'adresse email spécifiée pour le destinataire
                $destinataire = 'rogez.aurore01@gmail.com';
                $nom_utilisateur = 'Utilisateur'; // Vous pouvez personnaliser ce nom si nécessaire
                envoyer_email_rejet($destinataire, $nom_utilisateur);
            }
            echo "<div class='alert alert-success'>Statut mis à jour avec succès.</div>";
        } else {
            echo "<div class='alert alert-danger'>Erreur lors de la mise à jour du statut.</div>";
        }
    }
}
?>

<!-- Modal pour afficher le commentaire complet -->
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
                        $commentaire_tronque = tronquer_texte($row["commentaires"]);
                        echo htmlspecialchars($commentaire_tronque);
                        if (strlen($row["commentaires"]) > 50) {
                            echo ' <button class="btn-voir-plus" onclick="afficherCommentaire(\'' . htmlspecialchars(addslashes($row["commentaires"])) . '\')">Voir plus</button>';
                        }
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($row["note"]); ?></td>
                    <td><?php echo htmlspecialchars($row["statut"]); ?></td>
                    <td class="actions">
                        <form method="post" action="" class="action-buttons">
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

<script>
// Fonction pour afficher la modal avec le commentaire
function afficherCommentaire(commentaire) {
    const modal = document.getElementById('commentModal');
    const modalText = document.getElementById('modalCommentText');
    modalText.textContent = commentaire;
    modal.style.display = "block";
}

// Fermer la modal quand on clique sur le X
document.querySelector('.close').onclick = function() {
    document.getElementById('commentModal').style.display = "none";
}

// Fermer la modal quand on clique en dehors
window.onclick = function(event) {
    const modal = document.getElementById('commentModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>
