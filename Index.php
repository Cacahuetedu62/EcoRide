<?php
// Require necessary files with error handling
try {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/templates/header.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/pdo.php');
} catch (Exception $e) {
    // Log the error and show a generic error page
    error_log("Fichier requis non trouvé : " . $e->getMessage());
    header("Location: /error.php");
    exit();
}

// Advanced error handling configuration
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

// Validate and sanitize user inputs
function validateInput($input, $type = 'string') {
    // Trim whitespace
    $input = trim($input);

    switch ($type) {
        case 'string':
            // Allow letters, spaces, and hyphens
            return preg_match('/^[A-Za-zÀ-ÿ\s-]+$/', $input) ? $input : null;
        
        case 'date':
            // Ensure date is not in the past
            return (strtotime($input) >= strtotime('today')) ? $input : null;
        
        case 'passengers':
            // Ensure positive integer
            return (filter_var($input, FILTER_VALIDATE_INT) && $input > 0) ? $input : null;
        
        default:
            return null;
    }
}

// Handle form submission
$searchError = '';
$searchResults = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Erreur de sécurité : Token CSRF invalide.');
    }

    // Validate and sanitize inputs
    $villeDepart = validateInput($_POST['ville_depart']);
    $villeArrive = validateInput($_POST['ville_arrive']);
    $dateDepart = validateInput($_POST['date_depart'], 'date');
    $nbPassagers = validateInput($_POST['nb_passagers'], 'passengers');

    // Check validation results
    if ($villeDepart && $villeArrive && $dateDepart && $nbPassagers) {
        // Perform search (placeholder for actual database search)
        // In a real application, you would query your database here
        $searchResults = [
            // Example result structure
            ['depart' => $villeDepart, 'arrive' => $villeArrive, 'date' => $dateDepart]
        ];
    } else {
        $searchError = "Veuillez vérifier vos informations de recherche.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Covoiturage Électrique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/custom.css" rel="stylesheet">
</head>

<div id="cookieConsent" class="cookie-consent">
    <div class="container py-4">
        <div class="row align-items-center">
        <div class="col-md-8">
    <h3>🍪 Cookies techniques</h3>
    <p>
    EcoRide utilise uniquement des cookies techniques essentiels au fonctionnement du site 
                    (authentification et sécurité). Ces cookies sont automatiquement activés car ils sont 
                    nécessaires aux fonctionnalités de base comme la connexion à votre compte. Le bouton 
                    "J'ai compris" sert uniquement à masquer ce message. Que vous cliquiez ou non, le site 
                    fonctionnera de la même manière. Nous ne collectons aucune donnée à des fins publicitaires 
                    ou de tracking.
    </p>
</div>
<div class="col-md-4 text-end">
    <button onclick="acceptCookies()" class="btn btn-success btn-lg">J'ai compris</button>
    <a href="politiqueCookies.php" class="btn btn-outline-light btn-lg ms-2">Détails techniques</a>
</div>
        </div>
    </div>
</div>

    <div class="container">
        <div class="row p-3 justify-content-center align-items-md-stretch containHistoire">
            <!-- Section de Description de l'Entreprise -->
            <div class="col-md-8 d-flex justify-content-center flex-grow-7">
                <div class="h-100 p-1 rounded-3 image-backgroundHistoire">
                    <p class="descriptionHistoire">EcoRide est une entreprise de covoiturage innovante qui met l'accent sur l'utilisation de véhicules électriques, alliant ainsi mobilité durable et respect de l'environnement. En choisissant EcoRide, vous contribuez à réduire les émissions de CO2 tout en profitant d'un transport économique et pratique.</p>
                </div>
            </div>

            <!-- Section de Formulaire de Recherche de Trajet -->
            <div class="col-md-4 p-1 d-flex justify-content-center flex-grow-3">
                <div class="col-md-7 col-lg-8 contenair">
                    <h4 class="mb-3 text-center">Chercher un trajet</h4>
                    
                    <?php if (!empty($searchError)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($searchError); ?>
                        </div>
                    <?php endif; ?>

                    <form id="searchForm" method="POST" action="covoiturages.php" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        
                        <div class="row g-3 cardTrajet">
                            <!-- Ville de départ -->
                            <div class="col-12">
                                <label for="ville_depart" class="form-label">Ville de départ</label>
                                <input type="text" class="form-control" id="ville_depart" name="ville_depart" 
                                       placeholder="Ville de départ" required 
                                       value="<?php echo isset($_POST['ville_depart']) ? htmlspecialchars($_POST['ville_depart'], ENT_QUOTES, 'UTF-8') : ''; ?>" 
                                       pattern="[A-Za-zÀ-ÿ\s-]+" 
                                       title="Uniquement des lettres, espaces et traits d'union">
                            </div>

                            <!-- Ville d'arrivée -->
                            <div class="col-12">
                                <label for="ville_arrive" class="form-label">Ville d'arrivée</label>
                                <input type="text" class="form-control" id="ville_arrive" name="ville_arrive" 
                                       placeholder="Ville d'arrivée" required 
                                       value="<?php echo isset($_POST['ville_arrive']) ? htmlspecialchars($_POST['ville_arrive'], ENT_QUOTES, 'UTF-8') : ''; ?>" 
                                       pattern="[A-Za-zÀ-ÿ\s-]+" 
                                       title="Uniquement des lettres, espaces et traits d'union">
                            </div>

                            <!-- Date de départ -->
                            <div class="col-12">
                                <label for="date_depart" class="form-label">Date de départ</label>
                                <input type="date" class="form-control" id="date_depart" name="date_depart" 
                                       required 
                                       value="<?php echo isset($_POST['date_depart']) ? htmlspecialchars($_POST['date_depart'], ENT_QUOTES, 'UTF-8') : ''; ?>" 
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <!-- Nombre de passagers -->
                            <div class="col-12">
                                <label for="nb_passagers" class="form-label">Nombre de passagers</label>
                                <input type="number" class="form-control" id="nb_passagers" name="nb_passagers" 
                                       placeholder="Nombre de passagers" required min="1" 
                                       value="<?php echo isset($_POST['nb_passagers']) ? htmlspecialchars($_POST['nb_passagers'], ENT_QUOTES, 'UTF-8') : '1'; ?>">
                            </div>

                            <!-- Bouton de recherche -->
                            <div class="col-12">
                                <button class="btn btn-success w-100" type="submit" name="chercher">Chercher</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Section Résultats de Recherche -->
        <?php if (!empty($searchResults)): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <h4>Résultats de recherche</h4>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Départ</th>
                                <th>Arrivée</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($searchResults as $result): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($result['depart']); ?></td>
                                    <td><?php echo htmlspecialchars($result['arrive']); ?></td>
                                    <td><?php echo htmlspecialchars($result['date']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Section Marketing/Fonctionnalités -->
        <div class="container marketing">
            <div class="row carousselImageDescription">
            <div class="container marketing">
        <div class="row carousselImageDescription">
            <div class="col-lg-4 d-flex flex-column align-items-center text-center">
                <img src="images/poignee_de_main.jpg" class="bd-placeholder-img rounded-circle" width="200" height="150" alt="poignée de main">
                <p class="p-3">Le covoiturage favorise la solidarité et l'entraide entre les conducteurs et passagers, permettant de partager des trajets tout en économisant de l'argent. C'est aussi une manière de renforcer les liens sociaux et d'encourager une mobilité plus responsable.</p>
            </div>
            <div class="col-lg-4 d-flex flex-column align-items-center text-center">
                <img src="images/electric-car.jpg" class="bd-placeholder-img rounded-circle" width="200" height="140" alt="voiture électrique en charge">
                <p class="p-3">Le covoiturage électrique contribue à réduire les émissions de CO2 et à diminuer la pollution de l'air. En choisissant des véhicules électriques, nous participons activement à la préservation de l'environnement.</p>
            </div>
            <div class="col-lg-4 d-flex flex-column align-items-center text-center">
                <img src="images/pouce-levé.jpg" class="bd-placeholder-img rounded-circle" width="200" height="140" alt="pouce levé">
                <p class="p-3">L'application et le site web "EcoRide" sont conçus pour offrir une expérience utilisateur simple et rapide. Trouver un trajet éco-responsable n'a jamais été aussi facile grâce à une interface claire et intuitive.</p>
            </div>
        </div>
    </div>
            </div>
        </div>
    </div>


    <script>
function acceptCookies() {
    // Créer un cookie qui dure 1 an
    document.cookie = "cookiesAccepted=true; max-age=31536000; path=/";
    document.getElementById('cookieConsent').style.display = 'none';
}

// Vérifier si le cookie existe déjà
window.onload = function() {
    if (document.cookie.indexOf('cookiesAccepted=true') === -1) {
        document.getElementById('cookieConsent').style.display = 'block';
    }
}

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('searchForm');
        const fields = ['ville_depart', 'ville_arrive', 'date_depart', 'nb_passagers'];

        form.addEventListener('submit', function(event) {
            let isValid = true;

            fields.forEach(function(fieldId) {
                const field = document.getElementById(fieldId);
                
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                event.preventDefault();
                alert('Veuillez remplir tous les champs correctement.');
            }
        });

        // Ajouter la validation en temps réel
        fields.forEach(function(fieldId) {
            const field = document.getElementById(fieldId);
            field.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('is-invalid');
                }
            });
        });
    });
    </script>

    <?php
    require_once($_SERVER['DOCUMENT_ROOT'] . '/templates/footer.php');
    ?>
