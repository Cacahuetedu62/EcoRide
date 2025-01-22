<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

// Activer le mode de débogage pour PDO
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Chemins absolus pour les dossiers
$base_path = __DIR__;
$upload_path = $base_path . DIRECTORY_SEPARATOR . 'uploads';
$photos_path = $upload_path . DIRECTORY_SEPARATOR . 'photos';

// Création des dossiers avec les bonnes permissions
if (!file_exists($upload_path)) {
    mkdir($upload_path, 0755, true);
}

if (!file_exists($photos_path)) {
    mkdir($photos_path, 0755, true);
}

// Vérification de la session
if (!isset($_SESSION['utilisateur']) || !isset($_SESSION['utilisateur']['id'])) {
    header('Location: connexion.php');
    exit;
}

function validateImage($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if ($file['size'] > $max_size) {
        return "L'image ne doit pas dépasser 5MB.";
    }

    if (!in_array($file['type'], $allowed_types)) {
        return "Format d'image non supporté. Utilisez JPG, PNG ou GIF.";
    }

    return true;
}

// Récupération des informations utilisateur
$utilisateur_id = $_SESSION['utilisateur']['id'];
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
$stmt->execute(['id' => $utilisateur_id]);
$utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

// Gestion du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    $photo = null;

    // Validation des champs obligatoires
    $required_fields = ['pseudo', 'nom', 'prenom', 'email', 'telephone', 'date_naissance', 'adresse', 'code_postal', 'ville', 'role'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $errors[] = "Le champ $field est obligatoire.";
        }
    }

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $validation = validateImage($_FILES['photo']);
        if ($validation === true) {
            $photo_name = uniqid() . '_' . basename($_FILES['photo']['name']);
            $photo_destination = $photos_path . DIRECTORY_SEPARATOR . $photo_name;
            $photo_db_path = 'uploads/photos/' . $photo_name;

            // Vérification supplémentaire pour s'assurer que le dossier existe et est accessible
            if (!file_exists($photos_path)) {
                $errors[] = "Le dossier de destination n'existe pas.";
            } elseif (!is_writable($photos_path)) {
                $errors[] = "Le dossier de destination n'est pas accessible en écriture.";
            } else {
                try {
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $photo_destination)) {
                        $photo = $photo_db_path;
                        error_log("Upload réussi ! Photo enregistrée en : " . $photo_destination);
                    } else {
                        throw new Exception("Échec de l'upload");
                    }
                } catch (Exception $e) {
                    $errors[] = "Détails de l'erreur d'upload :";
                    $errors[] = "- Message : " . $e->getMessage();
                    $errors[] = "- Fichier source : " . $_FILES['photo']['tmp_name'];
                    $errors[] = "- Destination : " . $photo_destination;
                    $errors[] = "- Permissions du dossier : " . substr(sprintf('%o', fileperms($photos_path)), -4);
                    $errors[] = "- Le dossier existe ? : " . (file_exists($photos_path) ? 'Oui' : 'Non');
                    $errors[] = "- Droits d'écriture ? : " . (is_writable($photos_path) ? 'Oui' : 'Non');

                    // Debug supplémentaire
                    error_log("Erreur upload photo: " . print_r($_FILES, true));
                    error_log("Destination: " . $photo_destination);
                    error_log("Permissions dossier: " . substr(sprintf('%o', fileperms($photos_path)), -4));
                }
            }
        } else {
            $errors[] = $validation;
        }
    }

    // Mise à jour des informations si pas d'erreur
    if (empty($errors)) {
        try {
            // Préparer la requête SQL
            $sql = "UPDATE utilisateurs SET
                    pseudo = :pseudo,
                    nom = :nom,
                    prenom = :prenom,
                    email = :email,
                    telephone = :telephone,
                    date_naissance = :date_naissance,
                    adresse = :adresse,
                    code_postal = :code_postal,
                    ville = :ville,
                    role = :role";

            // Ajouter la photo à la requête si elle a été mise à jour
            if ($photo) {
                $sql .= ", photo = :photo";
            }

            $sql .= " WHERE id = :id";

            // Préparer les paramètres
            $params = [
                'pseudo' => htmlspecialchars($_POST['pseudo']),
                'nom' => htmlspecialchars($_POST['nom']),
                'prenom' => htmlspecialchars($_POST['prenom']),
                'email' => filter_var($_POST['email'], FILTER_SANITIZE_EMAIL),
                'telephone' => htmlspecialchars($_POST['telephone']),
                'date_naissance' => $_POST['date_naissance'],
                'adresse' => htmlspecialchars($_POST['adresse']),
                'code_postal' => htmlspecialchars($_POST['code_postal']),
                'ville' => htmlspecialchars($_POST['ville']),
                'role' => htmlspecialchars($_POST['role']),
                'photo' => $photo ?? $utilisateur['photo'], // Utiliser la photo existante si aucune nouvelle photo n'est téléchargée
                'id' => $utilisateur_id
            ];

            // Ajouter la photo aux paramètres si elle existe
            if ($photo) {
                $params['photo'] = $photo;
            }

            // Debug des paramètres
            foreach ($params as $key => $value) {
                error_log("Paramètre $key : " . var_export($value, true));
            }

            // Exécuter la requête
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $success_message = "Vos informations ont été mises à jour avec succès.";
            
            // Rafraîchir les informations utilisateur
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
            $stmt->execute(['id' => $utilisateur_id]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // Gestion détaillée des erreurs
            $errors[] = "Erreur lors de la mise à jour des informations : " . $e->getMessage();
            error_log("Erreur SQL: " . $e->getMessage());
            error_log("Requête SQL: " . $sql);
            error_log("Paramètres: " . print_r($params, true));
            
            // Afficher les détails de l'erreur
            $errorInfo = $stmt->errorInfo();
            error_log("Détails de l'erreur : " . print_r($errorInfo, true));
        }
    }
}
?>

<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="card-title text-center mb-4">Mon Profil</h2>

                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $success_message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form id="profileForm" method="POST" enctype="multipart/form-data">
                        <div class="row mb-4">
                            <div class="col-md-4 text-center">
                                <div class="profile-photo-container mb-3">
                                <img src="<?= $utilisateur['photo'] ?? 'images/default.png.webp' ?>"
     alt="Photo de profil"
     class="profile-photo"
     id="photoPreview">
                                </div>
                                <div class="mb-3">
                                    <label for="photo" class="btn btn-outline-primary">
                                        Changer la photo
                                    </label>
                                    <input type="file" class="d-none" id="photo" name="photo" accept="image/*">
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div class="form-group mb-3">
                                    <label for="pseudo">Pseudo</label>
                                    <input type="text" class="form-control" id="pseudo" name="pseudo"
                                           value="<?= htmlspecialchars($utilisateur['pseudo']) ?>" disabled>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nom">Nom</label>
                                        <input type="text" class="form-control" id="nom" name="nom"
                                               value="<?= htmlspecialchars($utilisateur['nom']) ?>" disabled>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="prenom">Prénom</label>
                                        <input type="text" class="form-control" id="prenom" name="prenom"
                                               value="<?= htmlspecialchars($utilisateur['prenom']) ?>" disabled>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?= htmlspecialchars($utilisateur['email']) ?>" disabled>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="telephone">Téléphone</label>
                                        <input type="tel" class="form-control" id="telephone" name="telephone"
                                               value="<?= htmlspecialchars($utilisateur['telephone']) ?>" disabled>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="date_naissance">Date de naissance</label>
                                        <input type="date" class="form-control" id="date_naissance" name="date_naissance"
                                               value="<?= htmlspecialchars($utilisateur['date_naissance']) ?>" disabled>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="adresse">Adresse</label>
                                    <input type="text" class="form-control" id="adresse" name="adresse"
                                           value="<?= htmlspecialchars($utilisateur['adresse']) ?>" disabled>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="code_postal">Code Postal</label>
                                        <input type="text" class="form-control" id="code_postal" name="code_postal"
                                               value="<?= htmlspecialchars($utilisateur['code_postal']) ?>" disabled>
                                    </div>
                                    <div class="col-md-8 mb-3">
                                        <label for="ville">Ville</label>
                                        <input type="text" class="form-control" id="ville" name="ville"
                                               value="<?= htmlspecialchars($utilisateur['ville']) ?>" disabled>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="role">Je suis</label>
                                    <select class="form-control" id="role" name="role" disabled>
                                        <option value="passager" <?= $utilisateur['role'] == 'passager' ? 'selected' : '' ?>>Passager</option>
                                        <option value="chauffeur" <?= $utilisateur['role'] == 'chauffeur' ? 'selected' : '' ?>>Chauffeur</option>
                                        <option value="passager_chauffeur" <?= $utilisateur['role'] == 'passager_chauffeur' ? 'selected' : '' ?>>Passager/Chauffeur</option>
                                    </select>
                                </div>

                                <div class="text-center mt-4">
                                    <button type="button" class="btn btn-primary" id="edit-btn" onclick="editForm()">
                                        Modifier mes informations
                                    </button>
                                    <button type="submit" class="btn btn-success" id="submit-btn" style="display:none;">
                                        Enregistrer les modifications
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="cancel-btn" onclick="cancelEdit()" style="display:none;">
                                        Annuler
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prévisualisation de la photo
    const photoInput = document.getElementById('photo');
    const photoPreview = document.getElementById('photoPreview');

    photoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                photoPreview.src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
});

function editForm() {
    // Activer tous les champs
    document.querySelectorAll('#profileForm input, #profileForm select').forEach(input => {
        input.disabled = false;
    });

    // Afficher/masquer les boutons
    document.getElementById('edit-btn').style.display = 'none';
    document.getElementById('submit-btn').style.display = 'inline-block';
    document.getElementById('cancel-btn').style.display = 'inline-block';

    // Ajouter la classe edit-mode au formulaire
    document.getElementById('profileForm').classList.add('edit-mode');
}

function cancelEdit() {
    // Recharger la page pour annuler les modifications
    window.location.reload();
}
</script>

<?php require_once('templates/footer.php'); ?>
