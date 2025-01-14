<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use MongoDB\Client;

// Vérification de la session employé
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

// Fonction pour gérer les crédits
function gererCredits($pdo, $creditsCollection, $avis_utilisateur_id, $trajet_id) {
    try {
        // Début de la transaction MySQL
        $pdo->beginTransaction();

        // Récupérer le prix du trajet
        $sql = "SELECT prix_par_personne, nb_places FROM trajets WHERE id = :trajet_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
        $stmt->execute();
        $trajetInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        // Calculer commission plateforme (2 crédits)
        $commission = 2;
        $montant_final = $trajetInfo['prix_par_personne'] - $commission;

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
        $stmt->bindParam(':montant', $trajetInfo['prix_par_personne'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $avis_utilisateur_id, PDO::PARAM_INT);
        $stmt->execute();

        // 3. Créditer le chauffeur
        $sql = "UPDATE utilisateurs u 
                JOIN trajets t ON t.chauffeur_id = u.id 
                SET u.credits = u.credits + :montant_final 
                WHERE t.id = :trajet_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':montant_final', $montant_final, PDO::PARAM_INT);
        $stmt->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
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

// Fonction pour envoyer un email de rejet
function envoyer_email_rejet($destinataire, $nom_utilisateur) {
    // ... [Le reste de la fonction reste identique]
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
                // Mettre à jour le statut de l'avis
                $sql = "UPDATE avis SET statut = :new_status WHERE id = :avis_id";
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

<!-- Le reste du code HTML et JavaScript reste identique -->
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
        <!-- Le reste du code de la table reste identique -->
    </table>
</div>

<script>
// Le code JavaScript reste identique
</script>