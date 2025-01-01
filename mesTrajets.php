<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');


if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    
    
    // L'utilisateur est connecté, récupère son ID
        $utilisateur_id = $_SESSION['utilisateur']['id'];
        
      
    echo "L'utilisateur connecté a l'ID : " . $utilisateur_id;
    } 
    else {
        // L'utilisateur n'est pas connecté
        
        
    echo "Utilisateur non connecté.";
    }
    // Préparer la requête pour récupérer les trajets de l'utilisateur connecté
    $sql = "SELECT t.date_depart, t.heure_depart, t.lieu_depart
            FROM trajets t
            JOIN trajet_utilisateur tu ON t.id = tu.trajet_id
            WHERE tu.utilisateur_id = :utilisateur_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
    $stmt->execute();
    $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun trajet à venir.</p>
            <?php endif; ?>
        </div>

    </section>
</main>
