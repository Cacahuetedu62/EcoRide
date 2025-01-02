<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    // Récupérer l'ID de l'utilisateur connecté
    $utilisateur_id = $_SESSION['utilisateur']['id'];
    echo "L'utilisateur connecté a l'ID : " . htmlspecialchars($utilisateur_id, ENT_QUOTES, 'UTF-8');
} else {
    // Rediriger l'utilisateur vers la page de connexion s'il n'est pas connecté
    header("Location: login.php");
    exit;
}

// Préparer la requête pour récupérer les trajets de l'utilisateur connecté
$sql = "SELECT t.id, t.date_depart, t.heure_depart, t.lieu_depart
        FROM trajets t
        JOIN trajet_utilisateur tu ON t.id = tu.trajet_id
        WHERE tu.utilisateur_id = :utilisateur_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);

try {
    $stmt->execute();
    $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En cas d'erreur SQL, afficher un message et arrêter le script
    echo "Erreur lors de la récupération des trajets : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}
?>

<main class="form-page">
    <section class="custom-section2">
        <div class="custom-form-container">
            <h4>Mes trajets à venir</h4>
            <?php if (!empty($trajets)): ?>
                <?php foreach ($trajets as $trajet): ?>
                    <div class="vehicule">
                        <p>Date : <?= htmlspecialchars($trajet['date_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p>Heure de départ : <?= htmlspecialchars($trajet['heure_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p>Lieu de départ : <?= htmlspecialchars($trajet['lieu_depart'], ENT_QUOTES, 'UTF-8') ?></p>
                        <a href="detailsTrajet.php?trajet_id=<?= htmlspecialchars($trajet['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">Voir les détails</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun trajet à venir.</p>
            <?php endif; ?>
        </div>
    </section>
</main>
