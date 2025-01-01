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

// Récupérer les informations du véhicule de l'utilisateur
$sql = "SELECT * FROM voitures WHERE utilisateur_id = :utilisateur_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
$stmt->execute();
$voitures = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<main class="form-page">
    <section class="custom-section2">
        <div class="custom-form-container">
            <h4>Mes véhicules</h4>

            <!-- Affichage des véhicules -->
            <div class="vehicule">
                <?php foreach ($voitures as $voiture): ?>
                    <div class="form-group">
                        <h5><?php echo htmlspecialchars($voiture['modele']); ?></h5>
                        <p>Immatriculation: <?php echo htmlspecialchars($voiture['immatriculation']); ?></p>
                        <p>Marque: <?php echo htmlspecialchars($voiture['marque']); ?></p>
                        <p>Couleur: <?php echo htmlspecialchars($voiture['couleur']); ?></p>
                        <p>Date de 1ère immatriculation: <?php echo htmlspecialchars($voiture['date_premiere_immatriculation']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Bouton "Ajouter un véhicule" -->
            <button class="buttonVert m-2" data-bs-toggle="modal" data-bs-target="#addVehicleModal">+ Ajouter un véhicule</button>
        </div>
    </section>
</main>