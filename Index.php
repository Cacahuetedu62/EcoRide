<?php
require_once('lib/config.php');
require_once('lib/pdo.php');

// if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
//     header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
//     exit();
// }

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des tentatives de connexion
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = time();
}

if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_attempt']) < 300) {
    // Bloque pendant 5 minutes après 5 échecs
    die("Trop de tentatives. Réessayez dans quelques minutes.");
}

function getUserInfo($pdo, $utilisateur_id) {
    $query = "SELECT pseudo, credits, type_acces FROM utilisateurs WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $utilisateur_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$pseudo = 'Invité';
$credits = 0;
$type_acces = 'utilisateur';
$afficher_bouton_dashboard = false;
$dashboard_url = '';

if (isset($_SESSION['utilisateur'])) {
    $utilisateur_id = $_SESSION['utilisateur']['id'];
    $utilisateur = getUserInfo($pdo, $utilisateur_id);

    $pseudo = $utilisateur['pseudo'];
    $credits = $utilisateur['credits'];
    $type_acces = $utilisateur['type_acces'];

    if ($type_acces == 'administrateur') {
        $credits = 0;
        $afficher_bouton_dashboard = true;
        $dashboard_url = 'adminDashboard.php';
    } elseif ($type_acces == 'employe') {
        $afficher_bouton_dashboard = true;
        $dashboard_url = 'employeDashboard.php';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <meta name="description" content="EcoRide, quand économie rime avec écologie ! le covoiturage éléctrique, découvrez le covoiturage électrique pour des trajets plus verts et économiques.">
    <title>EcoRide | Covoiturage écologique et éléctrique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder@2.1.1/dist/Control.Geocoder.js"></script>
</head>


<body>
    <header>
        <div class="container-fluid navbar-ecoride px-3 py-2">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <!-- Authentication and Dashboard Button -->
                <div class="user-info-container">
                    <?php
                    if (isset($_SESSION['utilisateur'])) {
                        echo '<a href="logout.php" class="btn-disconnect">
                                Se déconnecter
                              </a>';
                        echo '<span class="pseudo-display">' . $pseudo . " - Crédits : " . $credits . '</span>';
                    } else {
                        echo '<a href="connexion.php" class="btn-connect">Se connecter</a>';
                    }
                    ?>
                </div>

                <!-- Navigation Menu -->
                <?php if (isset($_SESSION['utilisateur'])): ?>
                <div class="navbar-menu-container">
                    <ul class="nav col-12 col-lg-auto my-2">
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M2.5 5a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-11zm0 3a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-11zm0 3a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-11z" />
                                </svg>
                                Menu
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="mesInformations.php">Mes informations</a></li>
                                <li><a class="dropdown-item" href="mesVehicules.php">Mes véhicules</a></li>
                                <li><a class="dropdown-item" href="mesTrajets.php">Mes trajets à venir</a></li>
                                <li><a class="dropdown-item" href="historiqueTrajets.php">Historique de mes trajets</a></li>
                                <li><a class="dropdown-item" href="addTrajets.php">Proposer un trajet</a></li>
                            </ul>
                        </li>
                        <?php if ($afficher_bouton_dashboard): ?>
                        <li>
                            <a href="<?php echo $dashboard_url; ?>" class="nav-link">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 -960 960 960">
                                    <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h240v-560H200v560Zm320 0h240v-280H520v280Zm0-360h240v-200H520v200Z"/>
                                </svg>
                                Tableau de bord
                            </a>
                        </li>
                        <?php endif; ?>
                        <li>
                            <a href="covoiturages.php" class="nav-link">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-ev-front" viewBox="0 0 16 16">
                                    <path d="M9.354 4.243a.19.19 0 0 0-.085-.218.186.186 0 0 0-.23.034L6.051 7.246a.188.188 0 0 0 .136.316h1.241l-.673 2.195a.19.19 0 0 0 .085.218c.075.043.17.03.23-.034l2.88-3.187a.188.188 0 0 0-.137-.316H8.572z" />
                                    <path d="M4.819 2A2.5 2.5 0 0 0 2.52 3.515l-.792 1.848a.8.8 0 0 1-.38.404c-.5.25-.855.715-.965 1.262L.05 8.708a2.5 2.5 0 0 0-.049.49v.413c0 .814.39 1.543 1 1.997V13.5a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5v-1.338c1.292.048 2.745.088 4 .088s2.708-.04 4-.088V13.5a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5v-1.892c.61-.454 1-1.183 1-1.997v-.413q0-.248-.049-.49l-.335-1.68a1.8 1.8 0 0 0-.964-1.261.8.8 0 0 1-.381-.404l-.792-1.848A2.5 2.5 0 0 0 11.181 2H4.82ZM3.44 3.91A1.5 1.5 0 0 1 4.82 3h6.362a1.5 1.5 0 0 1 1.379.91l.792 1.847a1.8 1.8 0 0 0 .853.904c.222.112.381.32.43.564l.336 1.679q.03.146.029.294v.413a1.48 1.48 0 0 1-1.408 1.484c-1.555.07-3.786.155-5.592.155s-4.037-.084-5.592-.155A1.48 1.48 0 0 1 1 9.611v-.413q0-.148.03-.294l.335-1.68a.8.8 0 0 1 .43-.563c.383-.19.685-.511.853-.904z" />
                                </svg>
                                Les covoiturages
                            </a>
                        </li>
                        <li>
                            <a href="contact.php" class="nav-link">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                                    <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4" />
                                </svg>
                                Contact
                            </a>
                        </li>
                        <li>
                            <a href="index.php" class="nav-link">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-house" viewBox="0 0 16 16">
                                    <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V7.207l5-5z" />
                                </svg>
                                Accueil
                            </a>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Logo -->
                <div class="navbar-logo-container">
                    <img src="images/Logo_EcoRide transparent.png" alt="Logo EcoRide" width="200" height="100">
                </div>
            </div>
        </div>
    </header>

    
<script>
    function loadContent(page) {
        fetch(page)
            .then(response => response.text())
            .then(data => {
                document.getElementById('content').innerHTML = data;
            })
            .catch(error => console.error('Error loading content:', error));
    }
</script>
<main>


    <div class="row p-3 justify-content-center align-items-md-stretch containHistoire">
        <div class="col-md-8 d-flex justify-content-center" style="flex: 7;">
            <div class="h-100 p-1 rounded-3 image-backgroundHistoire">
                <p class="descriptionHistoire">EcoRide est une entreprise de covoiturage innovante qui met l'accent sur l'utilisation de véhicules électriques, alliant ainsi mobilité durable et respect de l'environnement. En choisissant EcoRide, vous contribuez à réduire les émissions de CO2 tout en profitant d'un transport économique et pratique. Nous offrons une alternative écologique aux trajets quotidiens, en permettant à nos utilisateurs de partager des trajets en toute sécurité et à moindre coût. Grâce à notre plateforme facile d'accès, chacun peut trouver un trajet éco-responsable adapté à ses besoins. EcoRide, c'est la solution idéale pour ceux qui veulent voyager intelligemment, économiquement et en respectant la planète. Rejoignez notre communauté et participez à la révolution verte du transport !</p>
            </div>
        </div>

        <div class="col-md-4 p-1 d-flex justify-content-center" style="flex: 3;">
            <div class="col-md-7 col-lg-8 contenair">
                <h4 class="mb-3 text-center">Chercher un trajet</h4>
                <form method="POST" action="covoiturages.php">
                    <div class="row g-3 cardTrajet">
                        <!-- Ville de départ -->
<!-- Ville de départ -->
<div class="col-12">
    <label for="ville_depart" class="form-label">Ville de départ</label>
    <input type="text" class="form-control" id="ville_depart" name="ville_depart" 
           placeholder="Entrez une ville de départ" required 
           value="<?php echo isset($_POST['ville_depart']) ? htmlspecialchars($_POST['ville_depart'], ENT_QUOTES, 'UTF-8') : ''; ?>">
</div>

<!-- Ville d'arrivée -->
<div class="col-12">
    <label for="ville_arrive" class="form-label">Ville d'arrivée</label>
    <input type="text" class="form-control" id="ville_arrive" name="ville_arrive" 
           placeholder="Entrez une ville d'arrivée" required 
           value="<?php echo isset($_POST['ville_arrive']) ? htmlspecialchars($_POST['ville_arrive'], ENT_QUOTES, 'UTF-8') : ''; ?>">
</div>

                        <!-- Date de départ -->
                        <div class="col-12">
                            <label for="date_depart" class="form-label">Date de départ</label>
                            <input type="date" class="form-control" id="date_depart" name="date_depart" required
                                   value="<?php echo isset($_POST['date_depart']) ? htmlspecialchars($_POST['date_depart'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                                   min="<?php echo date('Y-m-d'); ?>" >
                            <div class="invalid-feedback">
                                Veuillez saisir une date de départ valide (pas avant aujourd'hui).
                            </div>
                        </div>

                        <!-- Nombre de passagers -->
                        <div class="col-12">
                            <label for="nb_passagers" class="form-label">Nombre de passagers</label>
                            <input type="number" class="form-control" id="nb_passagers" name="nb_passagers" placeholder="Nombre de passagers" required value="<?php echo isset($_POST['nb_passagers']) ? htmlspecialchars($_POST['nb_passagers'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                            <div class="invalid-feedback">
                                Veuillez saisir le nombre de passagers
                            </div>
                        </div>

                        <!-- Bouton de recherche -->
                        <button class="buttonVert" type="submit" name="chercher">Chercher</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



        <div class="row carousselImageDescription">
            <div class="col-lg-4 d-flex flex-column align-items-center text-center">
                <img src="images/poignee_de_main.jpg" class="bd-placeholder-img rounded-circle" width="200" height="150" alt="poignée de main">
                <p class="p-3">Le covoiturage favorise la solidarité et l'entraide entre les conducteurs et passagers, permettant de partager des trajets tout en économisant de l'argent. C'est aussi une manière de renforcer les liens sociaux et d'encourager une mobilité plus responsable.</p>
            </div>
            <div class="col-lg-4 d-flex flex-column align-items-center text-center">
                <img src="images/electric-car.jpg" class="bd-placeholder-img rounded-circle" width="200" height="140" alt="voiture éléctrique en charge">
                <p class="p-3">Le covoiturage électrique contribue à réduire les émissions de CO2 et à diminuer la pollution de l'air. En choisissant des véhicules électriques, nous participons activement à la préservation de l'environnement.</p>
            </div>
            <div class="col-lg-4 d-flex flex-column align-items-center text-center">
                <img src="images/pouce-levé.jpg" class="bd-placeholder-img rounded-circle" width="200" height="140" alt="pouce levé">
                <p class="p-3">L'application et le site web "EcoRide" sont conçus pour offrir une expérience utilisateur simple et rapide. Trouver un trajet éco-responsable n'a jamais été aussi facile grâce à une interface claire et intuitive.</p>
            </div>
        </div>

        <script>
function setupAutocomplete(inputId) {
    const input = document.getElementById(inputId);
    let timeout = null;

    input.addEventListener('input', function() {
        clearTimeout(timeout);
        const query = this.value;

        if (query.length < 3) return; // Attendre au moins 3 caractères

        timeout = setTimeout(() => {
            fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}, France&format=json&limit=5`)
                .then(response => response.json())
                .then(data => {
                    const datalist = document.getElementById(inputId + '-list');
                    if (!datalist) {
                        const newDatalist = document.createElement('datalist');
                        newDatalist.id = inputId + '-list';
                        input.parentNode.appendChild(newDatalist);
                        input.setAttribute('list', inputId + '-list');
                    }

                    document.getElementById(inputId + '-list').innerHTML = data
                        .map(item => `<option value="${item.display_name}">`)
                        .join('');
                });
        }, 300);
    });
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter les datalists pour l'autocomplétion
    setupAutocomplete('ville_depart');
    setupAutocomplete('ville_arrive');
});
</script>

</main>
<div class="cookie-consent" id="cookieConsent">
    <div class="container">
        <h3>Nous utilisons des cookies</h3>
        <p>Nous utilisons des cookies pour améliorer votre expérience sur notre site. En continuant à naviguer, vous acceptez notre utilisation des cookies.</p>
        <div class="text-center">
            <button class="btn btn-primary" id="acceptCookies">J'accepte</button>
            <button class="btn btn-secondary" id="declineCookies">Refuser</button>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cookieConsent = document.getElementById('cookieConsent');
    const acceptCookies = document.getElementById('acceptCookies');
    const declineCookies = document.getElementById('declineCookies');

    // Vérifie si le consentement a déjà été donné
    const consent = getCookie('cookieConsent');
    if (consent !== 'accepted') {
        cookieConsent.style.display = 'block';
    }

    acceptCookies.addEventListener('click', function() {
        setCookie('cookieConsent', 'accepted', 365); // Cookie persistant pendant 1 an
        enableNonEssentialCookies(); // Activer les cookies non essentiels
        cookieConsent.style.display = 'none';
    });

    declineCookies.addEventListener('click', function() {
        setCookie('cookieConsent', 'declined', null); // Cookie de session
        disableNonEssentialCookies(); // Désactiver les cookies non essentiels
        cookieConsent.style.display = 'none';
    });

    // Fonction pour activer ou désactiver les cookies non essentiels
    function manageCookies() {
        const consent = getCookie('cookieConsent');
        if (consent === 'accepted') {
            enableNonEssentialCookies(); // Activer les cookies non essentiels
        } else if (consent === 'declined') {
            disableNonEssentialCookies(); // Désactiver les cookies non essentiels
        }
    }

    // Appeler la fonction pour gérer les cookies au chargement de la page
    manageCookies();

    // Fonction pour définir un cookie
    function setCookie(name, value, days) {
        let expires = "";
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    // Fonction pour obtenir un cookie
    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    // Fonction pour activer les cookies non essentiels
    function enableNonEssentialCookies() {
        // Ajoutez ici le code pour activer les cookies non essentiels
        console.log('Cookies non essentiels activés');
        // Exemple : activer Google Analytics
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'YOUR_GOOGLE_ANALYTICS_ID');
    }

    // Fonction pour désactiver les cookies non essentiels
    function disableNonEssentialCookies() {
        // Ajoutez ici le code pour désactiver les cookies non essentiels
        console.log('Cookies non essentiels désactivés');
        // Exemple : désactiver Google Analytics
        window['ga-disable-YOUR_GOOGLE_ANALYTICS_ID'] = true;
    }
});
</script>




<footer>
    <div class="footer-container">
        <div class="footer-content">
            <nav class="footer-nav">
                <ul class="footer-list">
                    <li class="footer-item">
                        <a href="contact.php" class="footer-link">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="footer-icon" viewBox="0 0 16 16">
                                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741M1 11.105l4.708-2.897L1 5.383z"/>
                            </svg>
                            <span>E-mail</span>
                        </a>
                    </li>
                    <li class="footer-item">
                        <a href="#" class="footer-link">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="footer-icon" viewBox="0 0 16 16">
                                <path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865z"/>
                            </svg>
                            <span>Twitter X</span>
                        </a>
                    </li>
                    <li class="footer-item">
                        <a href="#" class="footer-link">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="footer-icon" viewBox="0 0 16 16">
                                <path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951"/>
                            </svg>
                            <span>Facebook</span>
                        </a>
                    </li>
                    <li class="footer-item">
                        <a href="mentionsLegales.php" class="footer-link">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="footer-icon" viewBox="0 0 16 16">
                                <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/>
                                <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                            </svg>
                            <span>Mentions légales</span>
                        </a>
                    </li>
                    <li class="footer-item">
                        <a href="CGDV.php" class="footer-link">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="footer-icon">
                                <path d="m40-240 20-80h220l-20 80H40Zm80-160 20-80h260l-20 80H120Zm623 240 20-160 29-240 10-79-59 479ZM240-80q-33 0-56.5-23.5T160-160h583l59-479H692l-11 85q-2 17-15 26.5t-30 7.5q-17-2-26.5-14.5T602-564l9-75H452l-11 84q-2 17-15 27t-30 8q-17-2-27-15t-8-30l9-74H220q4-34 26-57.5t54-23.5h80q8-75 51.5-117.5T550-880q64 0 106.5 47.5T698-720h102q36 1 60 28t19 63l-60 480q-4 30-26.5 49.5T740-80H240Zm220-640h159q1-33-22.5-56.5T540-800q-35 0-55.5 21.5T460-720Z"/>
                            </svg>
                            <span>CGV</span>
                        </a>
                    </li>
                    <li class="footer-item">
                        <a href="politiqueCookies.php" class="footer-link">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="footer-icon" ><path d="M480-320q75 0 127.5-52.5T660-500q0-75-52.5-127.5T480-680q-75 0-127.5 52.5T300-500q0 75 52.5 127.5T480-320Zm0-72q-45 0-76.5-31.5T372-500q0-45 31.5-76.5T480-608q45 0 76.5 31.5T588-500q0 45-31.5 76.5T480-392Zm0 192q-146 0-266-81.5T40-500q54-137 174-218.5T480-800q146 0 266 81.5T920-500q-54 137-174 218.5T480-200Zm0-300Zm0 220q113 0 207.5-59.5T832-500q-50-101-144.5-160.5T480-720q-113 0-207.5 59.5T128-500q50 101 144.5 160.5T480-280Z"/></svg>
                            <span>Cookies</span>
                        </a>
                    </li>
                    <li class="footer-item">
                        <a href="contact.php" class="footer-link">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="footer-icon"><path d="m480-80-10-120h-10q-142 0-241-99t-99-241q0-142 99-241t241-99q71 0 132.5 26.5t108 73q46.5 46.5 73 108T800-540q0 75-24.5 144t-67 128q-42.5 59-101 107T480-80Zm80-146q71-60 115.5-140.5T720-540q0-109-75.5-184.5T460-800q-109 0-184.5 75.5T200-540q0 109 75.5 184.5T460-280h100v54Zm-101-95q17 0 29-12t12-29q0-17-12-29t-29-12q-17 0-29 12t-12 29q0 17 12 29t29 12Zm-29-127h60q0-30 6-42t38-44q18-18 30-39t12-45q0-51-34.5-76.5T460-720q-44 0-74 24.5T344-636l56 22q5-17 19-33.5t41-16.5q27 0 40.5 15t13.5 33q0 17-10 30.5T480-558q-35 30-42.5 47.5T430-448Zm30-65Z"/></svg>                            
                        <span>Contact</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <p class="footer-copyright">© 2024/2025 Rogez Aurore - ECF EcoRide</p>
        </div>
    </div>
</footer>
</body>
</html>