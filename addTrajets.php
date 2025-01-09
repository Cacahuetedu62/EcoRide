<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    $utilisateur_id = $_SESSION['utilisateur']['id'];
} else {
    echo "Utilisateur non connecté.";
    exit; // Arrêter l'exécution du script si l'utilisateur n'est pas connecté
}

var_dump($utilisateur_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $lieu_depart = $_POST['lieu_depart'] ?? null;
    $date_depart = $_POST['date_depart'] ?? null;
    $heure_depart = $_POST['heure_depart'] ?? null;
    $lieu_arrive = $_POST['lieu_arrive'] ?? null;
    $date_arrive = $_POST['date_arrive'] ?? null;
    $heure_arrive = $_POST['heure_arrive'] ?? null;
    $nb_places = $_POST['nb_places'] ?? null;
    $prix_personnes = $_POST['prix_personnes'] ?? null;
    $preferences = $_POST['preferences'] ?? null;
    $fumeur = isset($_POST['fumeur']) ? 1 : 0;
    $animaux = isset($_POST['animaux']) ? 1 : 0;
    $voiture_id = $_POST['voiture'] ?? null;

    // Validation des champs obligatoires
    if (!$lieu_depart || !$date_depart || !$heure_depart || !$lieu_arrive || !$date_arrive || !$heure_arrive || !$nb_places || !$prix_personnes || !$voiture_id) {
        echo "Tous les champs obligatoires doivent être remplis.";
        exit;
    }

    try {
        // Préparer l'insertion du trajet
        $query = "INSERT INTO trajets (lieu_depart, date_depart, heure_depart, lieu_arrive, date_arrive, heure_arrive, nb_places, prix_personnes, preferences, fumeur, animaux, statut)
                  VALUES (:lieu_depart, :date_depart, :heure_depart, :lieu_arrive, :date_arrive, :heure_arrive, :nb_places, :prix_personnes, :preferences, :fumeur, :animaux, 'disponible')";

        $stmt = $pdo->prepare($query);

        // Lier les paramètres
        $stmt->bindParam(':lieu_depart', $lieu_depart, PDO::PARAM_STR);
        $stmt->bindParam(':date_depart', $date_depart, PDO::PARAM_STR);
        $stmt->bindParam(':heure_depart', $heure_depart, PDO::PARAM_STR);
        $stmt->bindParam(':lieu_arrive', $lieu_arrive, PDO::PARAM_STR);
        $stmt->bindParam(':date_arrive', $date_arrive, PDO::PARAM_STR);
        $stmt->bindParam(':heure_arrive', $heure_arrive, PDO::PARAM_STR);
        $stmt->bindParam(':nb_places', $nb_places, PDO::PARAM_INT);
        $stmt->bindParam(':prix_personnes', $prix_personnes, PDO::PARAM_STR);
        $stmt->bindParam(':preferences', $preferences, PDO::PARAM_STR);
        $stmt->bindParam(':fumeur', $fumeur, PDO::PARAM_INT);
        $stmt->bindParam(':animaux', $animaux, PDO::PARAM_INT);

        if ($stmt->execute()) {
            // Récupérer l'ID du trajet créé
            $trajet_id = $pdo->lastInsertId();

            // Associer l'utilisateur et la voiture au trajet dans la table trajet_utilisateur
            $query_assoc = "INSERT INTO trajet_utilisateur (utilisateur_id, trajet_id)
                            VALUES (:utilisateur_id, :trajet_id)";
            $stmt_assoc = $pdo->prepare($query_assoc);
            $stmt_assoc->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
            $stmt_assoc->bindParam(':trajet_id', $trajet_id, PDO::PARAM_INT);

            if ($stmt_assoc->execute()) {
                echo "Trajet créé et associé à l'utilisateur avec succès !";
            } else {
                echo "Erreur lors de l'association du trajet à l'utilisateur.";
            }
        } else {
            echo "Erreur lors de la création du trajet.";
        }
    } catch (Exception $e) {
        // Gestion des erreurs
        echo "Une erreur est survenue : " . $e->getMessage();
    }
}

// Récupérer les voitures de l'utilisateur
$query = "SELECT id, marque, modele FROM voitures WHERE utilisateur_id = :utilisateur_id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
$stmt->execute();
$voitures = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Formulaire de création de trajet -->
<main class="form-page">
    <section class="custom-section1">
        <div class="custom-form-container">
            <form method="POST" action="">
                <label for="lieu_depart">Lieu de départ :</label>
                <input type="text" name="lieu_depart" required>

                <label for="date_depart">Date de départ :</label>
                <input type="date" name="date_depart" required>

                <label for="heure_depart">Heure de départ :</label>
                <input type="time" name="heure_depart" required>

                <label for="lieu_arrive">Lieu d'arrivée :</label>
                <input type="text" name="lieu_arrive" required>

                <label for="date_arrive">Date d'arrivée :</label>
                <input type="date" name="date_arrive" required>

                <label for="heure_arrive">Heure d'arrivée :</label>
                <input type="time" name="heure_arrive" required>

                <label for="nb_places">Nombre de places :</label>
                <input type="number" name="nb_places" required>

                <label for="prix_personnes">Prix par personne :</label>
                <input type="number" name="prix_personnes" step="0.01" required>

                <label for="preferences">Préférences :</label>
                <textarea name="preferences"></textarea>

                <label for="fumeur">Fumeur :</label>
                <input type="radio" id="fumeur_oui" name="fumeur" value="1" class="btn-check">
                <label class="btn btn-outline-success" for="fumeur_oui">Oui</label>
                <input type="radio" id="fumeur_non" name="fumeur" value="0" class="btn-check" checked>
                <label class="btn btn-outline-danger" for="fumeur_non">Non</label>

                <label for="animaux">Animaux :</label>
                <input type="radio" id="animaux_oui" name="animaux" value="1" class="btn-check">
                <label class="btn btn-outline-success" for="animaux_oui">Oui</label>
                <input type="radio" id="animaux_non" name="animaux" value="0" class="btn-check" checked>
                <label class="btn btn-outline-danger" for="animaux_non">Non</label>

                <label for="voiture">Voiture :</label>
                <select name="voiture" required>
                    <option value="">-- Sélectionnez une voiture --</option>
                    <?php foreach ($voitures as $voiture): ?>
                        <option value="<?= htmlspecialchars($voiture['id']) ?>">
                            <?= htmlspecialchars($voiture['marque'] . ' ' . $voiture['modele']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Créer le trajet</button>
            </form>
        </div>
    </section>
</main>
