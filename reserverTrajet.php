<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

// echo "<pre>";
// print_r($_POST); // Affiche toutes les données reçues par la page
// echo "</pre>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifiez si l'utilisateur est connecté
    if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
        $utilisateur_id = $_SESSION['utilisateur']['id'];

        // Récupérer le nombre de passagers
        $nb_personnes = isset($_POST['nb_personnes']) ? intval($_POST['nb_personnes']) : 1;

        // Récupérer les données des passagers
        $noms = isset($_POST['noms']) ? $_POST['noms'] : [];

        // Récupérer l'ID du trajet
        $trajet_id = isset($_POST['trajet_id']) ? $_POST['trajet_id'] : null;

        if (isset($_POST['trajet_id']) && isset($_POST['nb_personnes'])) {
            $trajet_id = $_POST['trajet_id'];
            $nb_personnes = $_POST['nb_personnes'];

            // Vérifier les places disponibles et le coût du trajet
            $sql_check_places = "SELECT nb_places, prix_personnes FROM trajets WHERE id = :trajet_id";
            $stmt_check = $pdo->prepare($sql_check_places);
            $stmt_check->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
            $stmt_check->execute();
            $trajet = $stmt_check->fetch();

            if ($trajet && $trajet['nb_places'] >= $nb_personnes) {
                // Calculer le coût total
                $total_cost = $trajet['prix_personnes'] * $nb_personnes;

                // Afficher un récapitulatif et demander confirmation
                echo "Le coût total pour votre réservation est de : " . $total_cost . " crédits.<br>";
                echo "Souhaitez-vous vraiment utiliser vos crédits pour cette réservation ?<br>";

                echo '<form method="POST" action="confirmationReservation.php">
                        <input type="hidden" name="trajet_id" value="' . $trajet_id . '">
                        <input type="hidden" name="nb_personnes" value="' . $nb_personnes . '">
                        <input type="hidden" name="total_cost" value="' . $total_cost . '">';

                // Ajouter des champs cachés pour chaque passager
                foreach ($noms as $index => $passenger) {
                    $nom = isset($passenger['nom']) ? $passenger['nom'] : '';
                    $prenom = isset($passenger['prenom']) ? $passenger['prenom'] : '';
                    echo '<input type="hidden" name="nom_passager' . $index . '" value="' . $nom . '">';
                    echo '<input type="hidden" name="prenom_passager' . $index . '" value="' . $prenom . '">';
                }

                echo '<button type="submit" name="confirm" value="1">Oui, réserver maintenant</button>
                      </form>';
                echo '<a href="mesTrajets.php">Retour à mes trajets</a>';
            } else {
                echo "Il n'y a pas assez de places disponibles pour cette réservation.";
            }
        } else {
            echo "Données manquantes pour effectuer la réservation.";
        }
    } else {
        echo "Vous devez être connecté pour réserver un trajet.";
    }
}

require_once('templates/footer.php');
?>
