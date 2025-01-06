<?php
require_once('lib/pdo.php');
require_once('lib/config.php');
require_once('templates/header.php');

echo '<pre>';
var_dump($_POST);
echo '</pre>';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifiez si l'utilisateur est connecté
    if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
        $utilisateur_id = $_SESSION['utilisateur']['id'];
        echo "L'utilisateur connecté a l'ID : " . $utilisateur_id;

        // Récupérer le nombre de passagers
        $nb_personnes = isset($_POST['nb_personnes']) ? intval($_POST['nb_personnes']) : 1;

        // Récupérer les données des passagers
        $noms = isset($_POST['noms']) ? $_POST['noms'] : [];

        // Vérifiez si des passagers supplémentaires ont été envoyés
        if (!empty($noms)) {
            foreach ($noms as $index => $passenger) {
                // Assurez-vous que chaque passager a bien un nom et un prénom
                $nom = isset($passenger['nom']) ? $passenger['nom'] : null;
                $prenom = isset($passenger['prenom']) ? $passenger['prenom'] : null;

                // Déboguer pour voir si les passagers sont récupérés
                echo "Passager $index - Nom: $nom, Prénom: $prenom<br>";
            }
        } else {
            echo "Aucun passager supplémentaire trouvé.";
        }

        // Vous pouvez aussi vérifier d'autres paramètres comme trajet_id
        $trajet_id = isset($_POST['trajet_id']) ? $_POST['trajet_id'] : null;
        echo "Trajet ID: $trajet_id<br>";

        // Vérifiez si les données POST nécessaires sont présentes
        if (isset($_POST['trajet_id']) && isset($_POST['nb_personnes'])) {
            $trajet_id = $_POST['trajet_id'];
            $nb_personnes = $_POST['nb_personnes'];

            // Vérifiez le nombre de places disponibles pour le trajet
            $sql_check_places = "SELECT nb_places, prix_personnes FROM trajets WHERE id = :trajet_id";
            $stmt_check = $pdo->prepare($sql_check_places);
            $stmt_check->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
            $stmt_check->execute();
            $trajet = $stmt_check->fetch();

            if ($trajet && $trajet['nb_places'] >= $nb_personnes) {
                // Préparer la requête d'insertion dans la table reservations
                $sql_reservation = "INSERT INTO reservations (utilisateur_id, trajet_id, nb_personnes) VALUES (:utilisateur_id, :trajet_id, :nb_personnes)";
                $stmt_reservation = $pdo->prepare($sql_reservation);
                $stmt_reservation->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
                $stmt_reservation->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);
                $stmt_reservation->bindParam(':nb_personnes', $nb_personnes, PDO::PARAM_INT);

                // Exécuter la requête d'insertion de réservation
                if ($stmt_reservation->execute()) {
                    // Obtenez l'ID de la réservation nouvellement insérée
                    $reservation_id = $pdo->lastInsertId();

                    // Vérifiez si des passagers supplémentaires sont ajoutés
                    if (isset($_POST['noms']) && is_array($_POST['noms'])) {
                        foreach ($_POST['noms'] as $index => $passenger) {
                            if (isset($passenger['nom']) && isset($passenger['prenom'])) {
                                $nom_passager = htmlspecialchars($passenger['nom']);
                                $prenom_passager = htmlspecialchars($passenger['prenom']);

                                // Insérer chaque passager dans la table passagers
                                $sql_passager = "INSERT INTO passagers (reservation_id, utilisateur_id, nom_passager, prenom_passager)
                                                 VALUES (:reservation_id, :utilisateur_id, :nom_passager, :prenom_passager)";
                                $stmt_passager = $pdo->prepare($sql_passager);
                                $stmt_passager->bindParam(':reservation_id', $reservation_id, PDO::PARAM_INT);
                                $stmt_passager->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
                                $stmt_passager->bindParam(':nom_passager', $nom_passager, PDO::PARAM_STR);
                                $stmt_passager->bindParam(':prenom_passager', $prenom_passager, PDO::PARAM_STR);

                                if (!$stmt_passager->execute()) {
                                    echo "Erreur lors de l'insertion du passager : " . $nom_passager . " " . $prenom_passager;
                                }
                            } else {
                                echo "Données de passager manquantes pour l'index : " . $index;
                            }
                        }
                    } else {
                        echo "Aucun passager supplémentaire trouvé.";
                    }

                    // Mettre à jour le nombre de places disponibles dans la table trajets
                    $sql_update_places = "UPDATE trajets SET nb_places = nb_places - :nb_personnes WHERE id = :trajet_id";
                    $stmt_update = $pdo->prepare($sql_update_places);
                    $stmt_update->bindParam(':nb_personnes', $nb_personnes, PDO::PARAM_INT);
                    $stmt_update->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);

                    if ($stmt_update->execute()) {
                        // Mettre à jour le solde de crédits de l'utilisateur
                        $total_cost = $trajet['prix_personnes'] * $nb_personnes;
                        $sql_update_credits = "UPDATE utilisateurs SET credits = credits - :total_cost WHERE id = :utilisateur_id";
                        $stmt_update_credits = $pdo->prepare($sql_update_credits);
                        $stmt_update_credits->bindParam(':total_cost', $total_cost, PDO::PARAM_INT);
                        $stmt_update_credits->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);

                        if ($stmt_update_credits->execute()) {
                            // Rediriger vers la page "mesTrajets.php"
                            header('Location: mesTrajets.php');
                            exit;
                        } else {
                            echo "Erreur lors de la mise à jour des crédits.";
                        }
                    } else {
                        echo "Erreur lors de la mise à jour des places disponibles.";
                    }
                } else {
                    echo "Erreur lors de la réservation.";
                }
            } else {
                echo "Il n'y a pas assez de places disponibles pour cette réservation.";
            }
        } else {
            echo "Données manquantes pour effectuer la réservation.";
        }
    } else {
        // L'utilisateur n'est pas connecté
        echo "Vous devez être connecté pour réserver un trajet.";
    }
}

require_once('templates/footer.php');
?>
