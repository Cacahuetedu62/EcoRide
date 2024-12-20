<?php
session_start();
session_unset();  // Supprimer toutes les variables de session
session_destroy();  // Détruire la session
header('Location: index.php');  // Rediriger l'utilisateur vers la page d'accueil
exit;
?>