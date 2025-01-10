<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    $utilisateur_id = $_SESSION['utilisateur']['id'];
    echo "L'utilisateur connecté a l'ID : " . $utilisateur_id;
} else {
    echo "Utilisateur non connecté.";
    exit; // Arrêter l'exécution du script si l'utilisateur n'est pas connecté
}

// Traitement de la suppression de véhicule
if (isset($_POST['supprimer_vehicule'])) {
    $id = $_POST['id'];
    $sql = "DELETE FROM voitures WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Traitement de l'ajout de véhicule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_vehicule'])) {
    $modele = $_POST['modele'];
    $immatriculation = $_POST['immatriculation'];
    $marque = $_POST['marque'];
    $energie = $_POST['energie'];
    $couleur = $_POST['couleur'];
    $nb_places = $_POST['nb_places'];
    $date_premiere_immatriculation = $_POST['date_premiere_immatriculation'];

    // Initialisation de la variable $erreur
    $erreur = '';

    // Validation côté serveur
    if (!preg_match('/^[A-Z]{2}-\d{3}-[A-Z]{2}$/', $immatriculation)) {
        $erreur = "Le format de l'immatriculation est incorrect.";
    } elseif ($nb_places < 1 || $nb_places > 7) {
        $erreur = "Le nombre de places doit être compris entre 1 et 7.";
    } else {
        // Vérifier si l'immatriculation existe déjà
        $sql = "SELECT COUNT(*) FROM voitures WHERE immatriculation = :immatriculation";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':immatriculation', $immatriculation, PDO::PARAM_STR);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $erreur = "L'immatriculation existe déjà.";
        } else {
            $sql = "INSERT INTO voitures (modele, immatriculation, marque, energie, couleur, nb_places, date_premiere_immatriculation, utilisateur_id) VALUES (:modele, :immatriculation, :marque, :energie, :couleur, :nb_places, :date_premiere_immatriculation, :utilisateur_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':modele', $modele, PDO::PARAM_STR);
            $stmt->bindParam(':immatriculation', $immatriculation, PDO::PARAM_STR);
            $stmt->bindParam(':marque', $marque, PDO::PARAM_STR);
            $stmt->bindParam(':energie', $energie, PDO::PARAM_STR);
            $stmt->bindParam(':couleur', $couleur, PDO::PARAM_STR);
            $stmt->bindParam(':nb_places', $nb_places, PDO::PARAM_INT);
            $stmt->bindParam(':date_premiere_immatriculation', $date_premiere_immatriculation, PDO::PARAM_STR);
            $stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
            $stmt->execute();
        }
    }
}

// Récupérer les informations du véhicule de l'utilisateur
$sql = "SELECT * FROM voitures WHERE utilisateur_id = :utilisateur_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
$stmt->execute();
$voitures = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="form-page">
    <section class="custom-section2">
        <div class="custom-form-container">
            <h4>Mes véhicules</h4>

            <!-- Affichage des véhicules -->
            <div class="vehicule">
                <?php foreach ($voitures as $voiture): ?>
                    <div class="form-group">
                        <p><strong>Modèle:</strong> <?php echo htmlspecialchars($voiture['modele']); ?></p>
                        <p><strong>Immatriculation:</strong> <?php echo htmlspecialchars($voiture['immatriculation']); ?></p>
                        <p><strong>Marque:</strong> <?php echo htmlspecialchars($voiture['marque']); ?></p>
                        <p><strong>Énergie:</strong> <?php echo htmlspecialchars($voiture['energie']); ?></p>
                        <p><strong>Couleur:</strong> <?php echo htmlspecialchars($voiture['couleur']); ?></p>
                        <p><strong>Nombre de places:</strong> <?php echo htmlspecialchars($voiture['nb_places']); ?></p>
                        <p><strong>Date de première immatriculation:</strong> <?php echo htmlspecialchars($voiture['date_premiere_immatriculation']); ?></p>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $voiture['id']; ?>">
                            <button type="submit" name="supprimer_vehicule" class="buttonRouge m-2">Supprimer</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <button id="ajouterVehiculeBtn" class="buttonVert m-2">+ Ajouter un véhicule</button>

            <!-- Formulaire d'ajout de véhicule -->
            <div id="ajouterForm" style="display:none;">
                <form method="POST">
                    <div class="form-group">
                        <label for="modele">Modèle:</label>
                        <input type="text" id="modele" name="modele" required>
                    </div>
                    <div class="form-group">
                        <label for="immatriculation">Immatriculation:</label>
                        <input type="text" id="immatriculation" name="immatriculation" pattern="^[A-Z]{2}-\d{3}-[A-Z]{2}$" title="Le format doit être XX-XXX-XX" required>
                    </div>
                    <div class="form-group">
                        <label for="marque">Marque:</label>
                        <input type="text" id="marque" name="marque" required>
                    </div>
                    <div class="form-group">
                        <label for="energie">Énergie:</label>
                        <select id="energie" name="energie" required>
                            <option value="Essence">Essence</option>
                            <option value="Diesel">Diesel</option>
                            <option value="Électrique">Électrique</option>
                            <option value="Hybride">Hybride</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="couleur">Couleur:</label>
                        <input type="text" id="couleur" name="couleur" required>
                    </div>
                    <div class="form-group">
                        <label for="nb_places">Nombre de places:</label>
                        <input type="number" id="nb_places" name="nb_places" min="1" max="7" required>
                    </div>
                    <div class="form-group">
                        <label for="date_premiere_immatriculation">Date de première immatriculation:</label>
                        <input type="date" id="date_premiere_immatriculation" name="date_premiere_immatriculation" required>
                    </div>
                    <button type="submit" name="ajouter_vehicule" class="buttonVert m-2">Ajouter</button>
                </form>
                <?php if (isset($erreur)): ?>
                    <p style="color:red;"><?php echo $erreur; ?></p>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<script>
document.getElementById('ajouterVehiculeBtn').addEventListener('click', function() {
    document.getElementById('ajouterForm').style.display = 'block';
    this.style.display = 'none';
});
</script>
