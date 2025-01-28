<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

if (!isset($_SESSION['utilisateur']) || !isset($_SESSION['utilisateur']['id'])) {
}

// Génération et vérification du token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$utilisateur_id = filter_var($_SESSION['utilisateur']['id'], FILTER_SANITIZE_NUMBER_INT);

// Traitement de la suppression de véhicule avec vérification CSRF
if (isset($_POST['supprimer_vehicule']) && isset($_POST['csrf_token'])) {
    if ($_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        
        // Vérifier que le véhicule appartient bien à l'utilisateur
        $check_sql = "SELECT id FROM voitures WHERE id = :id AND utilisateur_id = :utilisateur_id";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $check_stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $sql = "DELETE FROM voitures WHERE id = :id AND utilisateur_id = :utilisateur_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
            $stmt->execute();
        }
    }
}

// Traitement de l'ajout de véhicule avec vérification CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_vehicule']) && isset($_POST['csrf_token'])) {
    if ($_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $modele = filter_var($_POST['modele'], FILTER_SANITIZE_SPECIAL_CHARS);
        $immatriculation = filter_var($_POST['immatriculation'], FILTER_SANITIZE_SPECIAL_CHARS);
        $marque = filter_var($_POST['marque'], FILTER_SANITIZE_SPECIAL_CHARS);
        $energie = filter_var($_POST['energie'], FILTER_SANITIZE_SPECIAL_CHARS);
        $couleur = filter_var($_POST['couleur'], FILTER_SANITIZE_SPECIAL_CHARS);
        $nb_places = filter_var($_POST['nb_places'], FILTER_SANITIZE_NUMBER_INT);
        $date_premiere_immatriculation = filter_var($_POST['date_premiere_immatriculation'], FILTER_SANITIZE_SPECIAL_CHARS);

        $erreur = '';

        if (!preg_match('/^[A-Z]{2}-\d{3}-[A-Z]{2}$/', $immatriculation)) {
            $erreur = "Le format de l'immatriculation est incorrect.";
        } elseif ($nb_places < 1 || $nb_places > 7) {
            $erreur = "Le nombre de places doit être compris entre 1 et 7.";
        } else {
            $sql = "SELECT COUNT(*) FROM voitures WHERE immatriculation = :immatriculation";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':immatriculation', $immatriculation, PDO::PARAM_STR);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $erreur = "L'immatriculation existe déjà.";
            } else {
                try {
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
                    $success = "Véhicule ajouté avec succès!";
                } catch (PDOException $e) {
                    $erreur = "Une erreur est survenue lors de l'ajout du véhicule.";
                }
            }
        }
    }
}

// Récupération des véhicules
$sql = "SELECT * FROM voitures WHERE utilisateur_id = :utilisateur_id ORDER BY date_premiere_immatriculation DESC";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
$stmt->execute();
$voitures = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container my-4">
    <h2 class="text-center mb-4">Mes véhicules</h2>

    <!-- Messages de succès/erreur -->
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($erreur)): ?>
        <div class="alert alert-danger"><?php echo $erreur; ?></div>
    <?php endif; ?>

    <!-- Affichage des véhicules -->
    <div class="row">
        <?php foreach ($voitures as $voiture): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($voiture['marque'] . ' ' . $voiture['modele']); ?></h5>
                        <div class="vehicle-details">
                            <p><strong>Immatriculation:</strong> <?php echo htmlspecialchars($voiture['immatriculation']); ?></p>
                            <p><strong>Énergie:</strong> <?php echo htmlspecialchars($voiture['energie']); ?></p>
                            <p><strong>Couleur:</strong> <?php echo htmlspecialchars($voiture['couleur']); ?></p>
                            <p><strong>Nombre de places:</strong> <?php echo htmlspecialchars($voiture['nb_places']); ?></p>
                            <p><strong>Date de première immatriculation:</strong> <?php echo date('d/m/Y', strtotime($voiture['date_premiere_immatriculation'])); ?></p>
                        </div>
                        <form method="POST" class="delete-form" data-vehicle="<?php echo htmlspecialchars($voiture['marque'] . ' ' . $voiture['modele']); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="id" value="<?php echo $voiture['id']; ?>">
                            <button type="button" class="btn btn-danger delete-btn">Supprimer</button>
                            <input type="submit" name="supprimer_vehicule" style="display: none;">
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <button id="ajouterVehiculeBtn" class="btn btn-primary mb-4">+ Ajouter un véhicule</button>

    <!-- Formulaire d'ajout de véhicule -->
    <div id="ajouterForm" style="display:none;" class="card p-4 mb-4">
        <h3 class="mb-3">Ajouter un nouveau véhicule</h3>
        <form method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="marque" class="form-label">Marque:</label>
                    <input type="text" class="form-control" id="marque" name="marque" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="modele" class="form-label">Modèle:</label>
                    <input type="text" class="form-control" id="modele" name="modele" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="immatriculation" class="form-label">Immatriculation:</label>
                    <input type="text" class="form-control" id="immatriculation" name="immatriculation" 
                           pattern="^[A-Z]{2}-\d{3}-[A-Z]{2}$" 
                           title="Format requis: XX-000-XX" required>
                    <div class="form-text">Format: XX-000-XX (ex: AB-123-CD)</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="energie" class="form-label">Énergie:</label>
                    <select class="form-select" id="energie" name="energie" required>
                        <option value="">Choisir...</option>
                        <option value="Essence">Essence</option>
                        <option value="Diesel">Diesel</option>
                        <option value="Électrique">Électrique</option>
                        <option value="Hybride">Hybride</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="couleur" class="form-label">Couleur:</label>
                    <input type="text" class="form-control" id="couleur" name="couleur" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="nb_places" class="form-label">Nombre de places:</label>
                    <input type="number" class="form-control" id="nb_places" name="nb_places" 
                           min="1" max="7" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="date_premiere_immatriculation" class="form-label">Date de première immatriculation:</label>
                    <input type="date" class="form-control" id="date_premiere_immatriculation" 
                           name="date_premiere_immatriculation" required>
                </div>
            </div>

            <div class="text-center mt-3">
                <button type="submit" name="ajouter_vehicule" class="btn btn-success">Ajouter le véhicule</button>
                <button type="button" class="btn btn-secondary" id="cancelBtn">Annuler</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du formulaire d'ajout
    const ajouterBtn = document.getElementById('ajouterVehiculeBtn');
    const ajouterForm = document.getElementById('ajouterForm');
    const cancelBtn = document.getElementById('cancelBtn');

    ajouterBtn.addEventListener('click', function() {
        ajouterForm.style.display = 'block';
        ajouterBtn.style.display = 'none';
    });

    cancelBtn.addEventListener('click', function() {
        ajouterForm.style.display = 'none';
        ajouterBtn.style.display = 'block';
        ajouterForm.querySelector('form').reset();
    });

    // Double confirmation pour la suppression
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            const form = this.closest('form');
            const vehicleName = form.dataset.vehicle;
            
            if (confirm(`Êtes-vous sûr de vouloir supprimer le véhicule ${vehicleName} ?`)) {
                if (confirm('Cette action est irréversible. Confirmer la suppression ?')) {
                    form.querySelector('input[type="submit"]').click();
                }
            }
        });
    });

    // Validation du format d'immatriculation en temps réel
    const immatriculationInput = document.getElementById('immatriculation');
    immatriculationInput.addEventListener('input', function(e) {
        let value = e.target.value.toUpperCase();
        value = value.replace(/[^A-Z0-9-]/g, '');
        
        if (value.length <= 9) {
            if (value.length === 2) value += '-';
            if (value.length === 6) value += '-';
        }
        
        e.target.value = value;
    });
});
</script>

<?php require_once('templates/footer.php'); ?>