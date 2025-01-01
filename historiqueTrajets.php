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


?>

<main class="form-page">
    <section class="custom-section2">
        <div class="custom-form-container">
            <h4>Historique</h4>


            <div class="vehicule">
                        <p>Date </p>
                        <p>Destination</p>
                        <p>+ détails</p>
                    </div>
            </div>

            <!-- Bouton "Ajouter un véhicule" -->
            <button class="buttonVert m-2" data-bs-toggle="modal" data-bs-target="#addVehicleModal">+ Ajouter un véhicule</button>
        </div>
    </section>
</main>
