<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    // L'utilisateur est connecté, récupère son ID
    $utilisateur_id = $_SESSION['utilisateur']['id'];
    echo "L'utilisateur connecté a l'ID : " . htmlspecialchars($utilisateur_id, ENT_QUOTES, 'UTF-8');

    // Préparer la requête pour récupérer les trajets de l'utilisateur connecté depuis la table historique et trajets
    $sql = "SELECT
    h.id,
    h.date_debut_reel,
    h.date_fin_reel,
    h.date_enregistrement,
    t.id AS trajet_id,
    t.lieu_depart,
    t.lieu_arrive,
    t.prix_personnes,
    t.date_depart,
    t.heure_depart,
    t.date_arrive,
    t.heure_arrive,
    t.statut AS statut_trajet,  -- Utilisation du statut de la table trajets
    GROUP_CONCAT(DISTINCT CONCAT(p.prenom_passager, ' ', p.nom_passager) SEPARATOR ', ') AS participants,
    u.role,
    COUNT(DISTINCT p.id) AS nombre_passagers,
    t.prix_personnes * COUNT(DISTINCT p.id) AS prix_total
FROM
    historique h
JOIN
    trajets t ON h.trajet_id = t.id
LEFT JOIN
    passagers p ON h.id = p.reservation_id
JOIN
    utilisateurs u ON h.utilisateur_id = u.id
WHERE
    h.utilisateur_id = :utilisateur_id
GROUP BY
    h.id, h.date_debut_reel, h.date_fin_reel, h.date_enregistrement, t.id, t.lieu_depart, t.lieu_arrive, t.prix_personnes, t.date_depart, t.heure_depart, t.date_arrive, t.heure_arrive, u.role, t.statut";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);

    try {
        $stmt->execute();
        $historique = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // En cas d'erreur SQL, afficher un message et arrêter le script
        echo "Erreur lors de la récupération de l'historique : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        exit;
    }
} else {
    // L'utilisateur n'est pas connecté
    echo "Utilisateur non connecté.";
    exit;
}

// Fonction pour formater les dates en français
function formaterDateFrancais($date) {
    if ($date === null) {
        return 'Date inconnue';
    }

    // Tableaux des jours de la semaine et des mois en français
    $jours = array(
        "Sunday" => "dimanche", "Monday" => "lundi", "Tuesday" => "mardi", "Wednesday" => "mercredi",
        "Thursday" => "jeudi", "Friday" => "vendredi", "Saturday" => "samedi"
    );
    $mois = array(
        "January" => "janvier", "February" => "février", "March" => "mars", "April" => "avril",
        "May" => "mai", "June" => "juin", "July" => "juillet", "August" => "août",
        "September" => "septembre", "October" => "octobre", "November" => "novembre", "December" => "décembre"
    );

    // Créer un objet DateTime à partir de la date
    $datetime = new DateTime($date);

    // Formater la date
    $jour_semaine = $jours[$datetime->format('l')]; // Récupérer le jour de la semaine en français
    $jour = $datetime->format('d'); // Jour du mois
    $mois_annee = $mois[$datetime->format('F')]; // Mois en français
    $annee = $datetime->format('Y'); // Année

    // Retourner la date au format souhaité
    return $jour_semaine . ' ' . $jour . ' ' . $mois_annee . ' ' . $annee;
}

// Fonction pour formater les heures
function formaterHeure($date) {
    if ($date === null) {
        return 'Heure inconnue';
    }
    return date('H:i:s', strtotime($date));
}
?>

<main class="form-page">
    <section class="custom-section2">
        <div class="colonnesConducPassagers">
            <h4>Historique</h4>

            <!-- Trajets en tant que conducteur -->
            <div class="row">
                <h5>Trajets en tant que Conducteur</h5>
                <?php
                $trajets_conducteur = array_filter($historique, fn($trajet) => $trajet['role'] === 'conducteur');
                if (!empty($trajets_conducteur)): ?>
                    <?php foreach ($trajets_conducteur as $trajet): ?>
                        <div class="vehicule givre">
                            <p><b>ID du trajet : <?= htmlspecialchars($trajet['trajet_id'], ENT_QUOTES, 'UTF-8') ?></b></p>
                            <p><b>Voyage effectué le : <?= htmlspecialchars(formaterDateFrancais($trajet['date_enregistrement']), ENT_QUOTES, 'UTF-8') ?> à <?= htmlspecialchars(formaterHeure($trajet['date_enregistrement']), ENT_QUOTES, 'UTF-8') ?></b></p>
                            <p>Date de départ initiale : <?= htmlspecialchars(formaterDateFrancais($trajet['date_depart']), ENT_QUOTES, 'UTF-8') ?> à <?= htmlspecialchars(formaterHeure($trajet['heure_depart']), ENT_QUOTES, 'UTF-8') ?></p>
                            <p>Date de début : <?= htmlspecialchars(formaterDateFrancais($trajet['date_debut_reel']), ENT_QUOTES, 'UTF-8') ?> à <?= htmlspecialchars(formaterHeure($trajet['date_debut_reel']), ENT_QUOTES, 'UTF-8') ?></p>
                            <br>
                            <p>Statut du trajet : <?= htmlspecialchars($trajet['statut_trajet'], ENT_QUOTES, 'UTF-8') ?></p>  <!-- Affichage du statut de la table trajets -->
                            <p>Date d'arrivée initiale : <?= htmlspecialchars(formaterDateFrancais($trajet['date_arrive']), ENT_QUOTES, 'UTF-8') ?> à <?= htmlspecialchars(formaterHeure($trajet['heure_arrive']), ENT_QUOTES, 'UTF-8') ?></p>
                            <p>Statut : 
    <?php if ($trajet['statut_trajet'] === 'terminé'): ?>
        <span class="point-termine"></span> <!-- Point vert si terminé -->
        Trajet réalisé avec succès le : <?= htmlspecialchars(formaterDateFrancais($trajet['date_fin_reel'] ?? 'Date inconnue'), ENT_QUOTES, 'UTF-8') ?> à <?= htmlspecialchars(formaterHeure($trajet['date_fin_reel'] ?? 'Heure inconnue'), ENT_QUOTES, 'UTF-8') ?>
    <?php else: ?>
        <span class="point-en-cours"></span> <!-- Point orange si en cours -->
        En cours
    <?php endif; ?>
</p>


                            <br>
                            <p>Lieu de départ : <?= htmlspecialchars($trajet['lieu_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p>Lieu d'arrivée : <?= htmlspecialchars($trajet['lieu_arrive'], ENT_QUOTES, 'UTF-8') ?></p>
                            <br>
                            <p>Prix par personne : <?= htmlspecialchars($trajet['prix_personnes'], ENT_QUOTES, 'UTF-8') ?> €</p>
                            <p>Vous avez participé à ce trajet avec <?= htmlspecialchars($trajet['nombre_passagers'], ENT_QUOTES, 'UTF-8') ?> personnes, qui étaient <?= htmlspecialchars($trajet['participants'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p>Pour un prix total de <?= htmlspecialchars($trajet['prix_total'], ENT_QUOTES, 'UTF-8') ?> €</p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Aucun trajet en tant que conducteur.</p>
                <?php endif; ?>

                <!-- Trajets en tant que passager -->
                <div class="col-md-6">
                    <h5>Trajets en tant que Passager</h5>
                    <?php
                    $trajets_passager = array_filter($historique, fn($trajet) => $trajet['role'] === 'passager');
                    if (!empty($trajets_passager)): ?>
                        <?php foreach ($trajets_passager as $trajet): ?>
                            <div class="vehicule givre">
                            <p><b>ID du trajet : <?= htmlspecialchars($trajet['trajet_id'], ENT_QUOTES, 'UTF-8') ?></b></p>
<p><b>Voyage effectué le : <?= htmlspecialchars(formaterDateFrancais($trajet['date_enregistrement'] ?? ''), ENT_QUOTES, 'UTF-8') ?> à <?= htmlspecialchars(formaterHeure($trajet['date_enregistrement'] ?? ''), ENT_QUOTES, 'UTF-8') ?></b></p>
<p>Date de départ initiale : <?= htmlspecialchars(formaterDateFrancais($trajet['date_depart'] ?? ''), ENT_QUOTES, 'UTF-8') ?> à <?= htmlspecialchars(formaterHeure($trajet['heure_depart'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
<p>Date de début : <?= htmlspecialchars(formaterDateFrancais($trajet['date_debut_reel'] ?? ''), ENT_QUOTES, 'UTF-8') ?> à <?= htmlspecialchars(formaterHeure($trajet['date_debut_reel'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
<br>
<p>Date d'arrivée initiale : <?= htmlspecialchars(formaterDateFrancais($trajet['date_arrive'] ?? ''), ENT_QUOTES, 'UTF-8') ?> à <?= htmlspecialchars(formaterHeure($trajet['heure_arrive'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
<p>Statut : 
    <?php if ($trajet['statut_trajet'] === 'terminé'): ?>
        <span class="point-termine"></span> <!-- Point vert si terminé -->
        Trajet réalisé avec succès le : <?= htmlspecialchars(formaterDateFrancais($trajet['date_fin_reel'] ?? 'Date inconnue'), ENT_QUOTES, 'UTF-8') ?> à <?= htmlspecialchars(formaterHeure($trajet['date_fin_reel'] ?? 'Heure inconnue'), ENT_QUOTES, 'UTF-8') ?>
    <?php else: ?>
        <span class="point-en-cours"></span> <!-- Point orange si en cours -->
        En cours
    <?php endif; ?>
</p>

</p>

<br>
<p>Lieu de départ : <?= htmlspecialchars($trajet['lieu_depart'] ?? 'Lieu inconnu', ENT_QUOTES, 'UTF-8') ?></p>
<p>Lieu d'arrivée : <?= htmlspecialchars($trajet['lieu_arrive'] ?? 'Lieu inconnu', ENT_QUOTES, 'UTF-8') ?></p>
<br>
<p>Prix par personne : <?= htmlspecialchars($trajet['prix_personnes'] ?? 'Prix inconnu', ENT_QUOTES, 'UTF-8') ?> €</p>
<p>Vous avez participé à ce trajet avec <?= htmlspecialchars($trajet['nombre_passagers'] ?? 0, ENT_QUOTES, 'UTF-8') ?> personnes, qui étaient <?= htmlspecialchars($trajet['participants'] ?? 'Inconnu', ENT_QUOTES, 'UTF-8') ?></p>
<p>Pour un prix total de <?= htmlspecialchars($trajet['prix_total'] ?? 0, ENT_QUOTES, 'UTF-8') ?> €</p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: white; font-size: 20px;">Aucun trajet en tant que passager.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once('templates/footer.php'); ?>
