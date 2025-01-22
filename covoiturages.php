<?php
    require_once('templates/header.php');
    require_once('lib/config.php');
    require_once('lib/pdo.php');

// Validate and sanitize user inputs
function validateInput($input, $type = 'string') {
    // Trim whitespace
    $input = trim($input);

    switch ($type) {
        case 'string':
            // Allow letters, spaces, and hyphens
            return preg_match('/^[A-Za-zÀ-ÿ\s-]+$/', $input) ? $input : null;

        case 'date':
            // Ensure date is not in the past
            return (strtotime($input) >= strtotime('today')) ? $input : null;

        case 'passengers':
            // Ensure positive integer
            return (filter_var($input, FILTER_VALIDATE_INT) && $input > 0) ? $input : null;

        default:
            return null;
    }
}

// Récupérer les paramètres de recherche de la session
$searchParams = isset($_SESSION['search_params']) ? $_SESSION['search_params'] : [];

// Initialiser les filtres
$filtres = [
    'ecologique' => isset($_POST['ecologique']) ? 1 : 0,
    'prix_min' => isset($_POST['prix_min']) ? validateInput($_POST['prix_min'], 'float') : null,
    'duree_max' => isset($_POST['duree']) ? validateInput($_POST['duree'], 'float') : null,
    'note_min' => isset($_POST['note_min']) ? validateInput($_POST['note_min'], 'int') : null
];

// Construire la requête SQL
$sql = "SELECT DISTINCT t.id, t.*, u.pseudo, u.photo, u.note_moyenne, v.energie, v.nb_places AS voiture_places, 
               t.prix_personnes,
               TIMESTAMPDIFF(HOUR, CONCAT(t.date_depart, ' ', t.heure_depart), 
                             CONCAT(t.date_arrive, ' ', t.heure_arrive)) AS duree
        FROM trajets t
        JOIN trajet_utilisateur tu ON t.id = tu.trajet_id
        JOIN utilisateurs u ON tu.utilisateur_id = u.id
        LEFT JOIN avis a ON a.trajet_id = t.id
        LEFT JOIN voitures v ON v.utilisateur_id = u.id
        WHERE t.lieu_depart LIKE :ville_depart
        AND t.lieu_arrive LIKE :ville_arrive
        AND t.date_depart = :date_depart
        AND t.nb_places >= :nb_passagers";

$params = [
    ':ville_depart' => "%{$searchParams['ville_depart']}%",
    ':ville_arrive' => "%{$searchParams['ville_arrive']}%",
    ':date_depart' => $searchParams['date_depart'],
    ':nb_passagers' => $searchParams['nb_passagers']
];

if ($filtres['ecologique']) {
    $sql .= " AND v.energie IN ('électrique', 'hybride')";
}
if ($filtres['prix_min'] !== null) {
    $sql .= " AND t.prix_personnes >= :prix_min";
    $params[':prix_min'] = $filtres['prix_min'];
}
if ($filtres['duree_max'] !== null) {
    $sql .= " AND TIMESTAMPDIFF(HOUR, CONCAT(t.date_depart, ' ', t.heure_depart), 
                                CONCAT(t.date_arrive, ' ', t.heure_arrive)) <= :duree_max";
    $params[':duree_max'] = $filtres['duree_max'];
}
if ($filtres['note_min'] !== null) {
    $sql .= " AND ROUND(u.note_moyenne, 1) >= :note_min";
    $params[':note_min'] = $filtres['note_min'];
}

// Exécuter la requête SQL
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filtrer les trajets disponibles
    $trajets_disponibles = array_filter($trajets, function($trajet) {
        return $trajet['nb_places'] >= 1;
    });
} catch (PDOException $e) {
    // Gestion des erreurs de base de données
    echo '<div class="alert alert-danger">Erreur de recherche : ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit();
}
?>

    <div class="container">
        <section class="d-flex m-3 justify-content-center">
            <div class="colonne-formulaire">
                <form method="POST" action="">
                    <div>
                        <h4>Chercher un trajet</h4>
                        <label for="ville_depart" class="form-label">Ville de départ</label>
                        <input type="text" class="form-control" id="ville_depart" name="ville_depart" 
                               placeholder="Ville de départ" required 
                               value="<?php echo htmlspecialchars($searchParams['ville_depart'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div>
                        <label for="ville_arrive" class="form-label">Ville d'arrivée</label>
                        <input type="text" class="form-control" id="ville_arrive" name="ville_arrive" 
                               placeholder="Ville d'arrivée" required 
                               value="<?php echo htmlspecialchars($searchParams['ville_arrive'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div>
                        <label for="date_depart" class="form-label">Date de départ</label>
                        <input type="date" class="form-control" id="date_depart" name="date_depart" 
                               required value="<?php echo htmlspecialchars($searchParams['date_depart'], ENT_QUOTES, 'UTF-8'); ?>"
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div>
                        <label for="nb_passagers" class="form-label">Nombre de passagers</label>
                        <input type="number" class="form-control" id="nb_passagers" name="nb_passagers" 
                               placeholder="Nombre de passagers" required 
                               value="<?php echo intval($searchParams['nb_passagers']); ?>">
                    </div>

                    <div>
                        <button class="buttonVert m-3" type="submit" name="chercher">Chercher</button>
                    </div>
                </form>
            </div>

            <div class="colonne-affiner">
                <?php if (!empty($searchParams)): ?>
                <div>
                    <h4>Vous pouvez affiner vos recherches</h4>
                    <div>Souhaitez-vous un trajet effectué avec une voiture électrique/hybride ?</div>
                    <div>
                        <input type="checkbox" id="ecologique" name="ecologique" value="1" 
                               <?php echo $filtres['ecologique'] ? 'checked' : ''; ?>> Oui
                    </div>

                    <div>
                        <label for="prix_min" class="form-label">Prix minimum</label>
                        <input type="number" class="form-control" id="prix_min" name="prix_min" 
                               placeholder="Prix minimum en €" 
                               value="<?php echo $filtres['prix_min'] !== null ? htmlspecialchars($filtres['prix_min'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                    </div>

                    <div>
                        <label for="duree" class="form-label">Durée du trajet</label>
                        <input type="text" class="form-control" id="duree" name="duree" 
                               placeholder="Durée du trajet (en heures)" 
                               value="<?php echo $filtres['duree_max'] !== null ? htmlspecialchars($filtres['duree_max'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                    </div>

                    <div>
                        <label for="note_min" class="form-label">Note minimale pour le chauffeur</label>
                        <input type="number" class="form-control" id="note_min" name="note_min" 
                               placeholder="Note minimale (1-5)" min="1" max="5" 
                               value="<?php echo $filtres['note_min'] !== null ? htmlspecialchars($filtres['note_min'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                    </div>
                    
                    <div>
                        <button class="buttonVert m-3" type="submit" name="filtrer">Appliquer les filtres</button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if (count($trajets_disponibles) > 0): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <h4>Résultats de recherche</h4>
                    <?php foreach ($trajets_disponibles as $trajet): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h5>Départ</h5>
                                        <p><strong>Ville:</strong> <?= htmlspecialchars($trajet['lieu_depart']) ?></p>
                                        <p><strong>Date:</strong> <?= $trajet['date_depart'] ?></p>
                                        <p><strong>Heure:</strong> <?= $trajet['heure_depart'] ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <h5>Arrivée</h5>
                                        <p><strong>Ville:</strong> <?= htmlspecialchars($trajet['lieu_arrive']) ?></p>
                                        <p><strong>Date:</strong> <?= $trajet['date_arrive'] ?></p>
                                        <p><strong>Heure:</strong> <?= $trajet['heure_arrive'] ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <h5>Informations</h5>
                                        <p><strong>Trajet #<?= $trajet['id'] ?></strong></p>
                                        <p><strong>Chauffeur:</strong> 
    <img src="<?= htmlspecialchars($trajet['photo'] ?? 'default.png', ENT_QUOTES) ?>" 
         alt="Photo du chauffeur" class="rounded-circle" width="40" height="40"> 
    <?= htmlspecialchars($trajet['pseudo'] ?? 'Chauffeur inconnu', ENT_QUOTES) ?>
</p>
                                        <p><strong>Note:</strong> <?php for ($i = 0; $i < round($trajet['note_moyenne']); $i++) { echo "⭐"; } ?></p>
                                        <p><strong>Places:</strong> <?= $trajet['nb_places'] ?></p>
                                        <p><strong>Prix:</strong> <?= $trajet['prix_personnes'] ?> € par personne</p>
                                        <p>
                                            <?php
                                            $ecologique = ($trajet['energie'] === 'électrique' || $trajet['energie'] === 'hybride');
                                            if ($ecologique) {
                                                echo "🌱 Trajet écologique";
                                            } else {
                                                echo "⛽ Trajet non écologique";
                                            }
                                            ?>
                                        </p>
                                        <a href="reservations.php?id=<?= htmlspecialchars($trajet['id']) ?>" class="btn btn-primary">Réserver</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="toast-container p-3 position-fixed top-50 start-50 translate-middle z-index-5">
                <div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header">
                        <strong class="me-auto">Résultat de la recherche</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        Aucun trajet trouvé pour les dates sélectionnées. Peut-être que des trajets sont disponibles à d'autres dates. Essayez de modifier vos dates de départ.
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Vérifie si le toast existe sur la page et l'affiche
    var toastElement = document.querySelector('.toast');
    if (toastElement) {
        var toast = new bootstrap.Toast(toastElement);
        toast.show(); // Afficher le toast
    }

    // Validation du formulaire en temps réel
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const fields = ['ville_depart', 'ville_arrive', 'date_depart', 'nb_passagers'];

        form.addEventListener('submit', function(event) {
            let isValid = true;

            fields.forEach(function(fieldId) {
                const field = document.getElementById(fieldId);
                
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                event.preventDefault();
                alert('Veuillez remplir tous les champs correctement.');
            }
        });

        // Validation en temps réel
        fields.forEach(function(fieldId) {
            const field = document.getElementById(fieldId);
            field.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('is-invalid');
                }
            });
        });
    });
    </script>
</body>
</html>

<?php
require_once('templates/footer.php');
?>