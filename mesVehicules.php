<?php
require_once('templates/header.php');

// Vérification de la session utilisateur
if (!isset($_SESSION['utilisateur']) || !isset($_SESSION['utilisateur']['id'])) {
    // Rediriger ou afficher un message si l'utilisateur n'est pas connecté
}

// Génération et vérification du token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Obtenir l'ID de l'utilisateur depuis la session
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
            // Supprimer le véhicule
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

        // Validation des données
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
                try {
                    // Ajouter le véhicule
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

// Récupération des véhicules de l'utilisateur
$sql = "SELECT * FROM voitures WHERE utilisateur_id = :utilisateur_id ORDER BY date_premiere_immatriculation DESC";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
$stmt->execute();
$voitures = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="d-flex justify-content-center my-4">
    <div class="container-max-width">
        <h2 class="text-center mb-4">Mes véhicules</h2>

        <!-- Messages de succès/erreur -->
        <?php if (isset($success) && !empty($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if (isset($erreur) && !empty($erreur)): ?>
    <div class="alert alert-danger"><?php echo $erreur; ?></div>
<?php endif; ?>

        <!-- Affichage des véhicules -->
        <div class="row">
            <?php foreach ($voitures as $voiture): ?>
                <div class="col-12 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm hover-shadow">
                        <div class="card-body">
                            <h1 class="card-title border-bottom pb-2 mb-3"><?php echo htmlspecialchars($voiture['marque'] . ' ' . $voiture['modele']); ?></h1>
                            <div class="vehicle-details">
                                <p class="mb-2"><span class="fw-bold">Immatriculation:</span> <?php echo htmlspecialchars($voiture['immatriculation']); ?></p>
                                <p class="mb-2"><span class="fw-bold">Énergie:</span> <?php echo htmlspecialchars($voiture['energie']); ?></p>
                                <p class="mb-2"><span class="fw-bold">Couleur:</span> <?php echo htmlspecialchars($voiture['couleur']); ?></p>
                                <p class="mb-2"><span class="fw-bold">Nombre de places:</span> <?php echo htmlspecialchars($voiture['nb_places']); ?></p>
                                <p class="mb-2"><span class="fw-bold">Date de première immatriculation:</span> <?php echo date('d/m/Y', strtotime($voiture['date_premiere_immatriculation'])); ?></p>
                            </div>
                            <form method="POST" class="delete-form mt-3" data-vehicle="<?php echo htmlspecialchars($voiture['marque'] . ' ' . $voiture['modele']); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="id" value="<?php echo $voiture['id']; ?>">
                                <button type="button" class="btn btn-danger btn-lg">Supprimer</button>
                               <input type="submit" name="supprimer_vehicule" class="d-none">
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button id="ajouterVehiculeBtn" class="buttonVert w-100 w-md-auto">+ Ajouter un véhicule</button>
    </div>

    <!-- Formulaire d'ajout de véhicule -->
    <div id="ajouterForm" class="card shadow-sm p-4 mb-4 d-none">
        <h3 class="h4 mb-4 border-bottom pb-2">Ajouter un nouveau véhicule</h3>
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
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du formulaire d'ajout
    const ajouterBtn = document.getElementById('ajouterVehiculeBtn');
    const ajouterForm = document.getElementById('ajouterForm');
    const cancelBtn = document.getElementById('cancelBtn');

    ajouterBtn.addEventListener('click', function() {
        ajouterForm.classList.remove('d-none'); // Enlever la classe d-none au lieu de changer style.display
        ajouterBtn.classList.add('d-none'); // Cacher le bouton avec d-none
    });

    cancelBtn.addEventListener('click', function() {
        ajouterForm.classList.add('d-none'); // Remettre la classe d-none
        ajouterBtn.classList.remove('d-none'); // Réafficher le bouton
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
