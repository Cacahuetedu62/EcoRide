<?php
require_once('lib/pdo.php');
require_once('lib/config.php');

// Récupérer l'ID de l'utilisateur connecté
$utilisateur_id = 4;  // À remplacer par l'ID réel de l'utilisateur connecté via la session ou autre

// Récupérer les informations du véhicule de l'utilisateur
$sql = "SELECT * FROM voitures WHERE utilisateur_id = :utilisateur_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
$stmt->execute();
$voitures = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <meta name="description" content="EcoRide, quand économie rime avec écologie ! le covoiturage éléctrique, découvrez le covoiturage électrique pour des trajets plus verts et économiques.">
    <title>EcoRide | Covoiturage écologique et éléctrique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
</head>

<body>

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
