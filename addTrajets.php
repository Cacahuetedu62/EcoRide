<?php
require_once('templates/header.php');

// Vérification de la session utilisateur
if (!isset($_SESSION['utilisateur']) || !isset($_SESSION['utilisateur']['id'])) {
    $_SESSION['error_message'] = "Vous devez être connecté pour accéder à cette page.";
}

$utilisateur_id = $_SESSION['utilisateur']['id'];
$error_messages = [];
$success_message = '';

// Fonction de nettoyage des entrées pour éviter les injections
function cleanInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Traitement du formulaire lorsqu'il est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyage et récupération des données du formulaire
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

    // Si pas d'erreurs, on procède à l'insertion dans la base de données
    if (empty($error_messages)) {
        try {
            $pdo->beginTransaction();

            // Mise à jour du rôle de l'utilisateur si nécessaire
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

<section class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="border rounded-3 p-4 bg-white">
                <h2 class="text-center mb-4 border-bottom pb-2">Proposer un Nouveau Trajet</h2>

                <?php if (!empty($error_messages)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($error_messages as $message): ?>
                            <p class="mb-0"><?= $message ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <?= $success_message ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="trajetForm">
                    <div class="row g-4">
                        <!-- Départ et Arrivée sur la même ligne pour grands écrans -->
                        <div class="col-12 col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h3 class="h5 mb-0">Départ</h3>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="lieu_depart" class="form-label">Lieu de départ</label>
                                        <input type="text" class="form-control" id="lieu_depart" name="lieu_depart" required placeholder="Entrez une ville ou une adresse">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="date_depart" class="form-label">Date</label>
                                            <input type="date" class="form-control" id="date_depart" name="date_depart" min="<?= date('Y-m-d') ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="heure_depart" class="form-label">Heure</label>
                                            <input type="time" class="form-control" id="heure_depart" name="heure_depart" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h3 class="h5 mb-0">Arrivée</h3>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="lieu_arrive" class="form-label">Lieu d'arrivée</label>
                                        <input type="text" class="form-control" id="lieu_arrive" name="lieu_arrive" required placeholder="Entrez une ville ou une adresse">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="date_arrive" class="form-label">Date</label>
                                            <input type="date" class="form-control" id="date_arrive" name="date_arrive" readonly>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="heure_arrive" class="form-label">Heure</label>
                                            <input type="time" class="form-control" id="heure_arrive" name="heure_arrive" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Places, Tarif et Véhicule -->
                        <div class="col-12 col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h3 class="h5 mb-0">Places et Tarif</h3>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="nb_places" class="form-label">Places disponibles</label>
                                        <input type="number" class="form-control" id="nb_places" name="nb_places" min="1" max="8" value="1" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="prix_personnes" class="form-label">Prix par personne (€)</label>
                                        <input type="number" class="form-control" step="0.1" min="0" id="prix_personnes" name="prix_personnes" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h3 class="h5 mb-0">Véhicule</h3>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="voiture" class="form-label">Sélectionnez votre véhicule</label>
                                        <select class="form-select" id="voiture" name="voiture" required>
                                            <option value="">Choisir un véhicule</option>
                                            <?php foreach ($voitures as $voiture): ?>
                                                <option value="<?= htmlspecialchars($voiture['id']) ?>">
                                                    <?= htmlspecialchars($voiture['marque'] . ' ' . $voiture['modele']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Préférences sur toute la largeur -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="h5 mb-0">Préférences</h3>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="preferences" class="form-label">Préférences supplémentaires</label>
                                        <textarea class="form-control" id="preferences" name="preferences" rows="3" 
                                        placeholder="Exemples : Musique souhaitée, arrêts prévus, conversation ou silence, etc."></textarea>
                                    </div>
                                    <p class="text-muted text-center mb-3">
                                        <i class="fas fa-info-circle"></i> Cliquez sur les options ci-dessous si vous les acceptez
                                    </p>
                                    <div class="d-flex justify-content-center gap-4 mt-3">
                                        <div class="text-center">
                                            <input type="checkbox" class="btn-check" id="fumeur" name="fumeur" autocomplete="off">
                                            <label class="btn btn-outline-secondary p-3 rounded-3" for="fumeur">
                                                <i class="fas fa-smoking fa-2x mb-2"></i><br>
                                                Fumeur accepté
                                            </label>
                                        </div>
                                        <div class="text-center">
                                            <input type="checkbox" class="btn-check" id="animaux" name="animaux" autocomplete="off">
                                            <label class="btn btn-outline-secondary p-3 rounded-3" for="animaux">
                                                <i class="fas fa-paw fa-2x mb-2"></i><br>
                                                Animaux acceptés
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button class="btn btn-success btn-lg" type="submit" name="chercher">Publier le trajet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
function calculateRoute() {
    const depart = document.getElementById('lieu_depart').value;
    const arrive = document.getElementById('lieu_arrive').value;
    const heureDepart = document.getElementById('heure_depart').value;
    const dateDepart = document.getElementById('date_depart').value;

    // Validate inputs more strictly
    if (!depart || !arrive || !heureDepart || !dateDepart) {
        console.error('Incomplete route information');
        return;
    }

    // Use a more specific geocoding approach
    Promise.all([
        fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(depart)}, France&format=json&addressdetails=1&limit=1`),
        fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(arrive)}, France&format=json&addressdetails=1&limit=1`)
    ])
    .then(([departResponse, arriveResponse]) => 
        Promise.all([departResponse.json(), arriveResponse.json()])
    )
    .then(([dataDepart, dataArrive]) => {
        if (dataDepart.length === 0 || dataArrive.length === 0) {
            throw new Error('One or both locations not found');
        }

        const coordsDepart = [dataDepart[0].lon, dataDepart[0].lat];
        const coordsArrive = [dataArrive[0].lon, dataArrive[0].lat];

        return fetch(`https://router.project-osrm.org/route/v1/driving/${coordsDepart[0]},${coordsDepart[1]};${coordsArrive[0]},${coordsArrive[1]}?overview=false`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Route calculation failed');
                }
                return response.json();
            });
    })
    .then(data => {
        if (!data.routes || !data.routes[0]) {
            throw new Error('No route found');
        }

        const duration = Math.round(data.routes[0].duration / 60); // minutes
        const distance = Math.round(data.routes[0].distance / 1000); // km

        // Calcul de l'heure d'arrivée
        const departDateTime = new Date(dateDepart + ' ' + heureDepart);
        const arrivalTime = new Date(departDateTime.getTime() + (duration * 60 * 1000));

        // Mise à jour des champs
        document.getElementById('date_arrive').value = arrivalTime.toISOString().split('T')[0];
        document.getElementById('heure_arrive').value =
            ('0' + arrivalTime.getHours()).slice(-2) + ':' +
            ('0' + arrivalTime.getMinutes()).slice(-2);
    })
    .catch(error => {
        console.error('Route calculation error:', error);
        
        // More user-friendly error handling
        let errorMessage = 'Une erreur est survenue lors du calcul du trajet.';
        if (error.message.includes('not found')) {
            errorMessage = 'Un ou plusieurs lieux n\'ont pas été trouvés. Veuillez vérifier votre saisie.';
        } else if (error.message.includes('No route found')) {
            errorMessage = 'Aucun itinéraire n\'a pu être calculé entre ces deux points.';
        }
        
        // Display error to user
        alert(errorMessage);
    });
}

function setupAutocomplete(inputId) {
    const input = document.getElementById(inputId);
    let timeout = null;

    input.addEventListener('input', function() {
        clearTimeout(timeout);
        const query = this.value;

        if (query.length < 3) return; // Wait for at least 3 characters

        timeout = setTimeout(() => {
            fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}, France&format=json&addressdetails=1&limit=5`)
                .then(response => response.json())
                .then(data => {
                    // Create datalist if it doesn't exist
                    let datalist = document.getElementById(inputId + '-list');
                    if (!datalist) {
                        datalist = document.createElement('datalist');
                        datalist.id = inputId + '-list';
                        input.parentNode.appendChild(datalist);
                        input.setAttribute('list', inputId + '-list');
                    }

                    // Populate datalist with more specific location information
                    datalist.innerHTML = data
                        .map(item => {
                            // Combine city, postcode, and potentially other details
                            const displayValue = [
                                item.address.city || item.address.town || item.address.village,
                                item.address.postcode,
                                item.address.state
                            ].filter(Boolean).join(', ');

                            return `<option value="${displayValue}">`;
                        })
                        .join('');
                })
                .catch(error => {
                    console.error('Autocomplete error:', error);
                });
        }, 300);
    });

    // Trigger route calculation on change
    input.addEventListener('change', calculateRoute);
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter les datalists pour l'autocomplétion
    setupAutocomplete('lieu_depart');
    setupAutocomplete('lieu_arrive');

    // Ajouter les écouteurs pour le calcul de route
    document.getElementById('date_depart').addEventListener('change', calculateRoute);
    document.getElementById('heure_depart').addEventListener('change', calculateRoute);
});
</script>

<?php
require_once('templates/footer.php');
?>
