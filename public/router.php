<?php
// router.php
require_once '../lib/header.php'; // Inclure votre en-tête
$uri = trim($_SERVER['REQUEST_URI'], '/');

// Définir les routes
switch ($uri) {
    case '':
        require 'index.php'; // La page d'accueil
        break;
    case 'test':
        require 'test.php'; // Page de test
        break;
    case 'terminerTrajet':
        require 'terminerTrajet.php'; // Page pour terminer un trajet
        break;
    case 'style':
        require 'style.css'; // Fichier CSS
        break;
    case 'security-headers':
        require 'security-headers.conf'; // Fichier de configuration de sécurité
        break;
    case 'reserverTrajet':
        require 'reserverTrajet.php'; // Page pour réserver un trajet
        break;
    case 'reservations':
        require 'reservations.php'; // Page des réservations
        break;
    case 'politiqueCookies':
        require 'politiqueCookies.php'; // Page sur la politique des cookies
        break;
    case 'mesVehicules':
        require 'mesVehicules.php'; // Page des véhicules
        break;
    case 'mesInformations':
        require 'mesInformations.php'; // Page des informations
        break;
    case 'mesTrajets':
        require 'mesTrajets.php'; // Page des trajets
        break;
    case 'mentionsLegales':
        require 'mentionsLegales.php'; // Page des mentions légales
        break;
    case 'manage':
        require 'manage.php'; // Page de gestion
        break;
    case 'logout':
        require 'logout.php'; // Page de déconnexion
        break;
    case 'lancerTrajet':
        require 'lancerTrajet.php'; // Page pour lancer un trajet
        break;
    case 'inscription':
        require 'inscription.php'; // Page d'inscription
        break;
    default:
        http_response_code(404);
        require '404.php'; // Page d'erreur 404
        break;
}
?>
