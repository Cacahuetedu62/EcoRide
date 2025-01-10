<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

// Vérification de la session
if (!isset($_SESSION['utilisateur']) || !isset($_SESSION['utilisateur']['type_acces']) || $_SESSION['utilisateur']['type_acces'] != 'administrateur')?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord Administrateur</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Tableau de bord Administrateur</h1>
    <a href="create_employee.php">Créer un compte employé</a>
    <!-- Ajoutez d'autres fonctionnalités administratives ici -->
</body>
</html>
