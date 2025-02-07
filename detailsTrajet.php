<?php
require_once('templates/header.php');


// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur']['id'])) {
    $_SESSION['message'] = "Utilisateur non connecté.";
    $_SESSION['message_type'] = "danger";
    header('Location: login.php');
    exit;
}

$utilisateur_id = $_SESSION['utilisateur']['id'];

// Vérifier si un ID de trajet est passé via l'URL
if (!isset($_GET['trajet_id'])) {
    $_SESSION['message'] = "Aucun trajet sélectionné.";
    $_SESSION['message_type'] = "danger";
    header('Location: index.php');
    exit;
}

$trajet_id = (int) $_GET['trajet_id'];

// Préparer et exécuter la requête pour obtenir les détails du trajet
$sql = "
    SELECT t.*, u.pseudo, u.photo, u.note_moyenne, u.credits, v.energie, v.nb_places AS voiture_places,
           t.prix_personnes, t.preferences, t.fumeur, t.animaux, u.id AS utilisateur_id, v.marque, v.modele,
           TIMESTAMPDIFF(MINUTE, CONCAT(t.date_depart, ' ', t.heure_depart),
                        CONCAT(t.date_arrive, ' ', t.heure_arrive)) AS duree_minutes,
           a.commentaires,
           GROUP_CONCAT(DISTINCT CONCAT(p.prenom_passager, ' ', p.nom_passager) SEPARATOR ' | ') AS invites
    FROM trajets t
    JOIN trajet_utilisateur tu ON t.id = tu.trajet_id
    JOIN utilisateurs u ON tu.utilisateur_id = u.id
    LEFT JOIN voitures v ON v.utilisateur_id = u.id
    LEFT JOIN avis a ON a.utilisateur_id = u.id
    LEFT JOIN reservations r ON t.id = r.trajet_id
    LEFT JOIN passagers p ON r.id = p.reservation_id
    WHERE t.id = :id
    GROUP BY t.id, u.pseudo, u.photo, u.note_moyenne, u.credits, v.energie, v.nb_places,
             t.prix_personnes, t.preferences, t.fumeur, t.animaux, u.id, v.marque, v.modele,
             a.commentaires
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $trajet_id, PDO::PARAM_INT);
    $stmt->execute();
    $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trajet) {
        $_SESSION['message'] = "Trajet non trouvé.";
        $_SESSION['message_type'] = "danger";
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Erreur SQL: " . $e->getMessage());
    $_SESSION['message'] = "Une erreur est survenue lors de la récupération des détails du trajet.";
    $_SESSION['message_type'] = "danger";
    header('Location: error.php');
    exit;
}

// Vérification des crédits et des places disponibles
$nb_personnes = isset($_POST['nb_personnes']) ? (int)$_POST['nb_personnes'] : 1;
$places_suffisantes = $trajet['voiture_places'] >= $nb_personnes;
$credits_suffisants = $trajet['credits'] >= ($trajet['prix_personnes'] * $nb_personnes);
?>

<div class="container">
<a href="javascript:history.back()" class="btn btn-secondary p-2">Retour</a>


    <div class="container">
        <!-- Titre principal -->
        <div class="row justify-content-center">
            <div class="col-12">
                <h4 class="text-center mt-4">Détails du trajet n° <?= htmlspecialchars($trajet['id']) ?></h4>
            </div>
        </div>

        <!-- Informations sur le trajet -->
        <div class="row mb-3">
            <!-- Départ -->
            <div class="col-md-6 departArrive">
                <h4>Départ</h4>
                <label class="form-label">Ville de départ</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($trajet['lieu_depart']) ?>" disabled>
                <label class="form-label">Date</label>
                <input type="date" class="form-control" value="<?= $trajet['date_depart'] ?>" disabled>
                <label class="form-label">Heure</label>
                <input type="text" class="form-control" value="<?= $trajet['heure_depart'] ?>" disabled>
            </div>

            <!-- Arrivée -->
            <div class="col-md-6 departArrive">
                <h4>Arrivée</h4>
                <label class="form-label">Ville d'arrivée</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($trajet['lieu_arrive']) ?>" disabled>
                <label class="form-label">Date</label>
                <input type="date" class="form-control" value="<?= $trajet['date_arrive'] ?>" disabled>
                <label class="form-label">Heure</label>
                <input type="text" class="form-control" value="<?= $trajet['heure_arrive'] ?>" disabled>
            </div>
        </div>

        <!-- Durée et réservation -->
        <div class="row mb-3">
            <div class="col-md-6 departArrive">
                <h4>Durée du trajet</h4>
                <p><?= floor($trajet['duree_minutes'] / 60) . ' h ' . ($trajet['duree_minutes'] % 60) . ' min' ?></p>
            </div>

            <div class="col-md-6 departArrive">
                <h4>Réservation</h4>
                <p>Places restantes : <?= $trajet['voiture_places'] ?></p>
                <p>Prix par personne : <?= $trajet['prix_personnes'] ?> €</p>

                <?php
// Récupérer les passagers invités à partir de la table passagers
$invites = isset($trajet['invites']) && !is_null($trajet['invites']) ? explode(' | ', $trajet['invites']) : [];
$total_passagers = count($invites) + 1; // Inclure l'utilisateur principal
$prix_total = $trajet['prix_personnes'] * $total_passagers;
?>


                <p><strong>Invités :</strong><br>
                <?php
                if (!empty($invites)) {
                    echo htmlspecialchars(implode('<br>', $invites));
                } else {
                    echo "Aucun invité";
                }
                ?></p>

                <p><strong>Nombre total de passagers :</strong> <?= $total_passagers ?></p>
                <p><strong>Prix total pour ce trajet :</strong> <?= $prix_total ?> €</p>

                <p class="text-muted">
                    ⚠️ Le montant total sera prélevé de vos crédits après validation du trajet par nos équipes.
                </p>
            </div>
        </div>

        <!-- Informations sur le chauffeur -->
        <div class="row mb-3">
            <div class="col-md-6 departArrive">
                <h4>Informations sur le chauffeur</h4>
                <p class="profilPhotoPseudo">
            <img src="<?= htmlspecialchars($trajet['photo']) ?>" alt="Photo du chauffeur" class="bd-placeholder-img rounded-circle" width="75" height="75">
            <?= htmlspecialchars($trajet['pseudo']) ?>
        </p>

                <!-- Note du chauffeur -->
                <p>Note :
                    <?php if (!empty($trajet['note_moyenne']) && (int)$trajet['note_moyenne'] > 0): ?>
                        <?= str_repeat("🚗", (int)$trajet['note_moyenne']) ?>
                    <?php else: ?>
                        <span>Pas encore de note</span>
                    <?php endif; ?>
                </p>

                <!-- Lien vers les commentaires -->
                <p>
                    <a href="commentaires.php?id=<?= htmlspecialchars($trajet['utilisateur_id']) ?>" class="buttonVert">Voir les commentaires</a>
                </p>
            </div>

            <!-- Informations sur la voiture -->
            <div class="col-md-6 departArrive">
                <h4>Voiture</h4>
                <p>Modèle : <?= htmlspecialchars($trajet['modele']) ?></p>
                <p>Marque : <?= htmlspecialchars($trajet['marque']) ?></p>
                <p>Énergie : <?= htmlspecialchars($trajet['energie']) ?></p>
            </div>
        </div>
    </div>
</div>

<?php
require_once('templates/footer.php');
?>
