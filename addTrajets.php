<?php
require_once('templates/header.php');

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

<section class="trajet-form-page">

        <div class="trajet-form-wrapper">
            <h2 class="trajet-title">Proposer un Nouveau Trajet</h2>

            <?php if (!empty($error_messages)): ?>
                <div class="trajet-alert trajet-alert-danger">
                    <?php foreach ($error_messages as $message): ?>
                        <p><?= $message ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="trajet-alert trajet-alert-success">
                    <?= $success_message ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="trajetForm" class="trajet-form">
            <div class="trajet-form-row">
                    <!-- Colonne Départ -->
                    <div class="trajet-card">
                        <h3 class="trajet-card-title m-1">Départ</h3>
                        <div class="trajet-card-body">
                            <label for="lieu_depart">Lieu de départ</label>
                            <input type="text" id="lieu_depart" name="lieu_depart" required
                                   placeholder="Entrez une ville ou une adresse">

                            <div class="trajet-input-group">
                                <div>
                                    <label for="date_depart">Date</label>
                                    <input type="date" id="date_depart" name="date_depart" min="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div>
                                    <label for="heure_depart">Heure</label>
                                    <input type="time" id="heure_depart" name="heure_depart" required>
                                </div>
                            </div>

                            <div id="trajet-info" class="trajet-info"></div>
                        </div>
                    </div>

                    <!-- Colonne Arrivée -->
                    <div class="trajet-card">
                        <h3 class="trajet-card-title m-1">Arrivée</h3>
                        <div class="trajet-card-body">
                            <label for="lieu_arrive">Lieu d'arrivée</label>
                            <input type="text" id="lieu_arrive" name="lieu_arrive" required
                                   placeholder="Entrez une ville ou une adresse">

                            <div class="trajet-input-group">
                                <div>
                                    <label for="date_arrive">Date</label>
                                    <input type="date" id="date_arrive" name="date_arrive" readonly>
                                </div>
                                <div>
                                    <label for="heure_arrive">Heure</label>
                                    <input type="time" id="heure_arrive" name="heure_arrive" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="trajet-form-row">
    <div class="trajet-card">
        <h3 class="trajet-card-title m-1">Places et Tarif</h3>
        <div class="trajet-card-body">
            <div class="trajet-input-group">
                <div>
                    <label for="nb_places">Places disponibles</label>
                    <input type="number" id="nb_places" name="nb_places" min="1" max="8" value="1" required>
                </div>
                <div>
                    <label for="prix_personnes">Prix par personne (€)</label>
                    <input type="number" step="0.1" min="0" id="prix_personnes" name="prix_personnes" required>
                </div>
            </div>
        </div>
    </div>
</div>
                
                <!-- Section véhicule et préférences -->
                <div class="trajet-form-row">
                    <div class="trajet-card">
                        <h3 class="trajet-card-title m-1">Véhicule</h3>
                        <div class="trajet-card-body">
                            <label for="voiture">Sélectionnez votre véhicule</label>
                            <select id="voiture" name="voiture" required>
                                <option value="">Choisir un véhicule</option>
                                <?php foreach ($voitures as $voiture): ?>
                                    <option value="<?= htmlspecialchars($voiture['id']) ?>">
                                        <?= htmlspecialchars($voiture['marque'] . ' ' . $voiture['modele']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="trajet-card">
                        <h3 class="trajet-card-title m-1">Préférences</h3>


                            <label for="preferences">Préférences supplémentaires</label>
                            <textarea id="preferences" name="preferences" rows="3"></textarea>

                            <p>Si vous cochez la case vous accpetez les fumeurs / animaux</p>
                            <div class="trajet-checkbox">
                                <input type="checkbox" id="fumeur" name="fumeur">
                                <label for="fumeur">Fumeur</label>
                            </div>
                            <div class="trajet-checkbox">
                                <input type="checkbox" id="animaux" name="animaux">
                                <label for="animaux">Animaux acceptés</label>
                            </div>
                        </div>
                    </div>                
                    <button class="buttonVert" type="submit" name="chercher">Publier le trajet</button>
                </div>


            </form>
        </div>
    </div>
</section>

<script>
function calculateRoute() {
    const depart = document.getElementById('lieu_depart').value;
    const arrive = document.getElementById('lieu_arrive').value;
    const heureDepart = document.getElementById('heure_depart').value;
    const dateDepart = document.getElementById('date_depart').value;

    if (depart && arrive && heureDepart && dateDepart) {
        // Conversion des adresses en coordonnées
        fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(depart)}, France&format=json&limit=1`)
            .then(response => response.json())
            .then(dataDepart => {
                fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(arrive)}, France&format=json&limit=1`)
                    .then(response => response.json())
                    .then(dataArrive => {
                        if (dataDepart.length === 0 || dataArrive.length === 0) {
                            alert('Une des adresses n\'a pas été trouvée. Veuillez vérifier votre saisie.');
                            return;
                        }

                        const coordsDepart = [dataDepart[0].lon, dataDepart[0].lat];
                        const coordsArrive = [dataArrive[0].lon, dataArrive[0].lat];

                        // Calcul de l'itinéraire
                        fetch(`https://router.project-osrm.org/route/v1/driving/${coordsDepart[0]},${coordsDepart[1]};${coordsArrive[0]},${coordsArrive[1]}?overview=false`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.routes && data.routes[0]) {
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
                                    document.getElementById('trajet-info').innerHTML =
                                        `Distance: ${distance} km<br>Durée estimée: ${duration} minutes`;
                                }
                            })
                            .catch(error => {
                                console.error('Erreur:', error);
                                alert('Erreur lors du calcul du trajet');
                            });
                    });
            });
    }
}

// Fonction d'autocomplétion
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
