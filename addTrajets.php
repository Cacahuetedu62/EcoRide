<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

// Vérification de la session
if (!isset($_SESSION['utilisateur']) || !isset($_SESSION['utilisateur']['id'])) {
    $_SESSION['error_message'] = "Vous devez être connecté pour accéder à cette page.";
}

$utilisateur_id = $_SESSION['utilisateur']['id'];
$error_messages = [];
$success_message = '';

// Fonction de nettoyage des entrées
function cleanInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyage et récupération des données
    $lieu_depart = cleanInput($_POST['lieu_depart'] ?? '');
    $date_depart = cleanInput($_POST['date_depart'] ?? '');
    $heure_depart = cleanInput($_POST['heure_depart'] ?? '');
    $lieu_arrive = cleanInput($_POST['lieu_arrive'] ?? '');
    $date_arrive = cleanInput($_POST['date_arrive'] ?? '');
    $heure_arrive = cleanInput($_POST['heure_arrive'] ?? '');
    $nb_places = filter_var($_POST['nb_places'] ?? 0, FILTER_VALIDATE_INT);
    $prix_personnes = filter_var($_POST['prix_personnes'] ?? 0, FILTER_VALIDATE_FLOAT);
    $preferences = cleanInput($_POST['preferences'] ?? '');
    $fumeur = isset($_POST['fumeur']) ? 1 : 0;
    $animaux = isset($_POST['animaux']) ? 1 : 0;
    $voiture_id = filter_var($_POST['voiture'] ?? 0, FILTER_VALIDATE_INT);

    // Validation côté serveur
    if (empty($lieu_depart) || empty($lieu_arrive)) {
        $error_messages[] = "Les lieux de départ et d'arrivée sont obligatoires.";
    }

    if (strtotime($date_depart) < strtotime('today')) {
        $error_messages[] = "La date de départ ne peut pas être dans le passé.";
    }

    if (strtotime($date_arrive . ' ' . $heure_arrive) <= strtotime($date_depart . ' ' . $heure_depart)) {
        $error_messages[] = "La date et l'heure d'arrivée doivent être après celles du départ.";
    }

    if ($nb_places < 1 || $nb_places > 8) {
        $error_messages[] = "Le nombre de places doit être entre 1 et 8.";
    }

    if ($prix_personnes <= 0) {
        $error_messages[] = "Le prix doit être supérieur à 0.";
    }

    // Si pas d'erreurs, on procède à l'insertion
    if (empty($error_messages)) {
        try {
            $pdo->beginTransaction();

            // Mise à jour du rôle si nécessaire
            $stmt_role = $pdo->prepare("UPDATE utilisateurs SET role = CASE
                WHEN role = 'passager' THEN 'passager-chauffeur'
                WHEN role IS NULL THEN 'chauffeur'
                ELSE role END
                WHERE id = ?");
            $stmt_role->execute([$utilisateur_id]);

            // Insertion du trajet
            $stmt = $pdo->prepare("INSERT INTO trajets (lieu_depart, date_depart, heure_depart,
                lieu_arrive, date_arrive, heure_arrive, nb_places, prix_personnes,
                preferences, fumeur, animaux, statut) VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'disponible')");

            $stmt->execute([
                $lieu_depart, $date_depart, $heure_depart,
                $lieu_arrive, $date_arrive, $heure_arrive,
                $nb_places, $prix_personnes, $preferences,
                $fumeur, $animaux
            ]);

            $trajet_id = $pdo->lastInsertId();

            // Association utilisateur-trajet
            $stmt_assoc = $pdo->prepare("INSERT INTO trajet_utilisateur (utilisateur_id, trajet_id)
                VALUES (?, ?)");
            $stmt_assoc->execute([$utilisateur_id, $trajet_id]);

            $pdo->commit();
            $success_message = "Trajet créé avec succès !";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error_messages[] = "Une erreur est survenue lors de la création du trajet.";
            error_log($e->getMessage());
        }
    }
}

// Récupération des voitures de l'utilisateur
try {
    $stmt = $pdo->prepare("SELECT id, marque, modele FROM voitures WHERE utilisateur_id = ?");
    $stmt->execute([$utilisateur_id]);
    $voitures = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_messages[] = "Erreur lors de la récupération des véhicules.";
    error_log($e->getMessage());
}
?>

<!-- JavaScript pour la validation côté client -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('trajetForm');

    form.addEventListener('submit', function(e) {
        const errors = [];
        const dateDepart = new Date(document.getElementById('date_depart').value);
        const dateArrive = new Date(document.getElementById('date_arrive').value);
        const now = new Date();

        // Réinitialiser la date d'aujourd'hui à minuit pour comparer uniquement les dates
        now.setHours(0,0,0,0);

        if (dateDepart < now) {
            errors.push("La date de départ ne peut pas être dans le passé");
            e.preventDefault();
        }

        if (dateArrive < dateDepart) {
            errors.push("La date d'arrivée doit être après la date de départ");
            e.preventDefault();
        }

        const prix = parseFloat(document.getElementById('prix_personnes').value);
        const places = parseInt(document.getElementById('nb_places').value);

        if (prix <= 0) {
            errors.push("Le prix doit être supérieur à 0");
            e.preventDefault();
        }

        if (places < 1 || places > 8) {
            errors.push("Le nombre de places doit être entre 1 et 8");
            e.preventDefault();
        }

        if (errors.length > 0) {
            alert(errors.join("\n"));
        }
    });
});
</script>

    <section>
        <div>
            <?php if (!empty($error_messages)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($error_messages as $message): ?>
                        <p><?= $message ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <section class="form-page">
    <div class="custom-section1">
        <div class="form-container">
            <?php if (!empty($error_messages)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($error_messages as $message): ?>
                        <p><?= $message ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?= $success_message ?>
                </div>
            <?php endif; ?>

            <h2>Nouveau trajet</h2>

            <form method="POST" action="" id="trajetForm" class="compact-form">
                <div class="form-row">
                    <!-- Colonne Gauche -->
                    <div class="form-column">
                        <h3>Départ</h3>
                        <div class="form-group">
                            <label for="lieu_depart">Lieu :</label>
                            <input type="text" id="lieu_depart" name="lieu_depart" required>
                        </div>

                        <div class="form-group">
                            <label for="date_depart">Date :</label>
                            <input type="date" id="date_depart" name="date_depart" 
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="heure_depart">Heure :</label>
                            <input type="time" id="heure_depart" name="heure_depart" required>
                        </div>

                        <h3>Informations trajet</h3>
                        <div class="form-group">
                            <label for="nb_places">Places disponibles :</label>
                            <input type="number" id="nb_places" name="nb_places" 
                                   min="1" max="8" value="1" required>
                        </div>

                        <div class="form-group">
                            <label for="prix_personnes">Prix par personne (€) :</label>
                            <input type="number" id="prix_personnes" name="prix_personnes" 
                                   min="1" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label for="voiture">Véhicule :</label>
                            <select id="voiture" name="voiture" required>
                                <option value="">Sélectionnez...</option>
                                <?php foreach ($voitures as $voiture): ?>
                                    <option value="<?= htmlspecialchars($voiture['id']) ?>">
                                        <?= htmlspecialchars($voiture['marque'] . ' ' . $voiture['modele']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Colonne Droite -->
                    <div class="form-column">
                        <h3>Arrivée</h3>
                        <div class="form-group">
                            <label for="lieu_arrive">Lieu :</label>
                            <input type="text" id="lieu_arrive" name="lieu_arrive" required>
                        </div>

                        <div class="form-group">
                            <label for="date_arrive">Date :</label>
                            <input type="date" id="date_arrive" name="date_arrive" 
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="heure_arrive">Heure :</label>
                            <input type="time" id="heure_arrive" name="heure_arrive" required>
                        </div>

                        <h3>Préférences</h3>
                        <div class="radio-groups">
                            <div class="radio-group">
                                <label>Fumeur :</label>
                                <div class="radio-options">
                                    <input type="radio" id="fumeur_oui" name="fumeur" value="1">
                                    <label for="fumeur_oui">Oui</label>
                                    <input type="radio" id="fumeur_non" name="fumeur" value="0" checked>
                                    <label for="fumeur_non">Non</label>
                                </div>
                            </div>

                            <div class="radio-group">
                                <label>Animaux :</label>
                                <div class="radio-options">
                                    <input type="radio" id="animaux_oui" name="animaux" value="1">
                                    <label for="animaux_oui">Oui</label>
                                    <input type="radio" id="animaux_non" name="animaux" value="0" checked>
                                    <label for="animaux_non">Non</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="preferences">Autres préférences :</label>
                            <textarea id="preferences" name="preferences" 
                                    rows="3" placeholder="Musique, discussions..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="history.back()">Annuler</button>
                    <button type="submit" class="btn-primary">Créer le trajet</button>
                </div>
            </form>
        </div>
    </div>

<script>
// Fonctions de validation et d'interaction
function validateDates() {
    const dateDepart = new Date(document.getElementById('date_depart').value);
    const dateArrive = new Date(document.getElementById('date_arrive').value);
    const heureDepart = document.getElementById('heure_depart').value;
    const heureArrive = document.getElementById('heure_arrive').value;
    const errorElement = document.getElementById('date-error');

    if (dateArrive < dateDepart) {
        errorElement.textContent = "La date d'arrivée doit être après la date de départ";
        return false;
    }

    if (dateArrive.getTime() === dateDepart.getTime()) {
        const [hDep, mDep] = heureDepart.split(':');
        const [hArr, mArr] = heureArrive.split(':');

        if (hArr < hDep || (hArr === hDep && mArr <= mDep)) {
            errorElement.textContent = "L'heure d'arrivée doit être après l'heure de départ";
            return false;
        }
    }

    errorElement.textContent = '';
    return true;
}

function incrementPlaces() {
    const input = document.getElementById('nb_places');
    if (input.value < 8) input.value = parseInt(input.value) + 1;
    calculateTotal();
}

function decrementPlaces() {
    const input = document.getElementById('nb_places');
    if (input.value > 1) input.value = parseInt(input.value) - 1;
    calculateTotal();
}

function calculateTotal() {
    const prix = parseFloat(document.getElementById('prix_personnes').value) || 0;
    const places = parseInt(document.getElementById('nb_places').value);
    const total = prix * places;
    document.getElementById('total-price').textContent =
        `Prix total pour ${places} place(s) : ${total.toFixed(2)} €`;
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    calculateTotal();
    document.querySelectorAll('input, select, textarea').forEach(element => {
        element.addEventListener('change', function() {
            this.classList.add('touched');
        });
    });
});
</script>

<?php require_once('templates/footer.php'); ?>
