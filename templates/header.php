<?php
require_once('lib/config.php');
require_once('lib/pdo.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
</head>

<header>
    <div class="px-3 py-2 text-bg-light border-bottom">
        <div class="container">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0" />
                        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1" />
                    </svg>
                    <span class="ms-2 text-black fs-5">
                        <?php
                        if (isset($_SESSION['utilisateur'])) {
                            echo $pseudo;
                            if ($type_acces !== 'administrateur') {
                                echo " - Crédits : " . $credits;
                            }
                            echo '<a href="logout.php" class="btn btn-danger btn-sm p-2 m-2">
                            Se déconnecter
                          </a>';
                                            } else {
                            echo '<a href="connexion.php" class="btn btn-success btn-sm">Se connecter</a>';
                        }
                        ?>
                    </span>
                </div>
                <?php
                if ($afficher_bouton_dashboard) {
                    echo '<a href="' . $dashboard_url . '" class="btn-dashboard">';
                    echo '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h240v-560H200v560Zm320 0h240v-280H520v280Zm0-360h240v-200H520v200Z"/></svg>';
                    echo 'Accéder au tableau de bord</a>';
                }
                ?>
                <ul class="nav col-12 col-lg-auto my-2 justify-content-center my-md-0 text-small">
                    <?php if (isset($_SESSION['utilisateur']) && $type_acces !== 'administrateur'): ?>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link text-black d-flex flex-column align-items-center dropdown-toggle" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
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
                    <?php endif; ?>
                    <li>
                        <a href="covoiturages.php" class="nav-link text-black d-flex flex-column align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-ev-front" viewBox="0 0 16 16">
                                <path d="M9.354 4.243a.19.19 0 0 0-.085-.218.186.186 0 0 0-.23.034L6.051 7.246a.188.188 0 0 0 .136.316h1.241l-.673 2.195a.19.19 0 0 0 .085.218c.075.043.17.03.23-.034l2.88-3.187a.188.188 0 0 0-.137-.316H8.572z" />
                                <path d="M4.819 2A2.5 2.5 0 0 0 2.52 3.515l-.792 1.848a.8.8 0 0 1-.38.404c-.5.25-.855.715-.965 1.262L.05 8.708a2.5 2.5 0 0 0-.049.49v.413c0 .814.39 1.543 1 1.997V13.5a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5v-1.338c1.292.048 2.745.088 4 .088s2.708-.04 4-.088V13.5a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5v-1.892c.61-.454 1-1.183 1-1.997v-.413q0-.248-.049-.49l-.335-1.68a1.8 1.8 0 0 0-.964-1.261.8.8 0 0 1-.381-.404l-.792-1.848A2.5 2.5 0 0 0 11.181 2H4.82ZM3.44 3.91A1.5 1.5 0 0 1 4.82 3h6.362a1.5 1.5 0 0 1 1.379.91l.792 1.847a1.8 1.8 0 0 0 .853.904c.222.112.381.32.43.564l.336 1.679q.03.146.029.294v.413a1.48 1.48 0 0 1-1.408 1.484c-1.555.07-3.786.155-5.592.155s-4.037-.084-5.592-.155A1.48 1.48 0 0 1 1 9.611v-.413q0-.148.03-.294l.335-1.68a.8.8 0 0 1 .43-.563c.383-.19.685-.511.853-.904z" />
                            </svg>
                            Les covoiturages
                        </a>
                    </li>
                    <li>
                        <a href="contact.php" class="nav-link text-black d-flex flex-column align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                                <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4" />
                            </svg>
                            Contact
                        </a>
                    </li>
                    <li>
                        <a href="index.php" class="nav-link text-black d-flex flex-column align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-house" viewBox="0 0 16 16">
                                <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V7.207l5-5z" />
                            </svg>
                            Accueil
                        </a>
                    </li>
                </ul>
                <img src="images/Logo_EcoRide transparent.png" alt="Logo EcoRide" width="120" height="60">
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
