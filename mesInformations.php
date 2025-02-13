<?php
require_once('templates/header.php');

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
    header('Location: login.php');
    exit;
}

// Fonctions de validation
function validatePhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) === 10 ? $phone : false;
}

function validatePostalCode($code) {
    $code = preg_replace('/[^0-9]/', '', $code);
    return strlen($code) === 5 ? $code : false;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Fonction pour valider une image
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

    // Validation du téléphone
    if (!empty($_POST['telephone'])) {
        $telephone = validatePhone($_POST['telephone']);
        if (!$telephone) {
            $errors[] = "Le numéro de téléphone doit contenir 10 chiffres.";
        }
    }

    // Validation du code postal
    if (!empty($_POST['code_postal'])) {
        $code_postal = validatePostalCode($_POST['code_postal']);
        if (!$code_postal) {
            $errors[] = "Le code postal doit contenir 5 chiffres.";
        }
    }

    // Validation de l'email
    if (!empty($_POST['email'])) {
        if (!validateEmail($_POST['email'])) {
            $errors[] = "L'adresse email n'est pas valide.";
        }
    }

    // Validation de la date de naissance
    if (!empty($_POST['date_naissance'])) {
        if (!validateDate($_POST['date_naissance'])) {
            $errors[] = "La date de naissance n'est pas valide.";
        }
    }

    // Validation des champs obligatoires
    $required_fields = ['pseudo', 'nom', 'prenom', 'email'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $errors[] = "Le champ $field est obligatoire.";
        }
    }

    // Validation et traitement de la photo
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $validation = validateImage($_FILES['photo']);
        if ($validation === true) {
            $photo_name = uniqid() . '_' . basename($_FILES['photo']['name']);
            $photo_destination = $photos_path . DIRECTORY_SEPARATOR . $photo_name;
            $photo_db_path = 'uploads/photos/' . $photo_name;

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
                    $errors[] = "Erreur lors de l'upload de la photo : " . $e->getMessage();
                    error_log("Erreur upload photo: " . $e->getMessage());
                }
            }
        } else {
            $errors[] = $validation;
        }
    }

    // Mise à jour des informations si pas d'erreur
    if (empty($errors)) {
        try {
            $params = [
                'pseudo' => htmlspecialchars($_POST['pseudo']),
                'nom' => htmlspecialchars($_POST['nom']),
                'prenom' => htmlspecialchars($_POST['prenom']),
                'email' => filter_var($_POST['email'], FILTER_SANITIZE_EMAIL),
                'telephone' => !empty($_POST['telephone']) ? validatePhone($_POST['telephone']) : null,
                'date_naissance' => !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : null,
                'adresse' => !empty($_POST['adresse']) ? htmlspecialchars($_POST['adresse']) : null,
                'code_postal' => !empty($_POST['code_postal']) ? validatePostalCode($_POST['code_postal']) : null,
                'ville' => !empty($_POST['ville']) ? htmlspecialchars($_POST['ville']) : null,
                'id' => $utilisateur_id
            ];

            $sql = "UPDATE utilisateurs SET
                pseudo = :pseudo,
                nom = :nom,
                prenom = :prenom,
                email = :email,
                telephone = :telephone,
                date_naissance = :date_naissance,
                adresse = :adresse,
                code_postal = :code_postal,
                ville = :ville";

            if ($photo) {
                $sql .= ", photo = :photo";
                $params['photo'] = $photo;
            }

            $sql .= " WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $success_message = "Vos informations ont été mises à jour avec succès.";

            // Rafraîchir les informations utilisateur
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
            $stmt->execute(['id' => $utilisateur_id]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la mise à jour des informations : " . $e->getMessage();
            error_log("Erreur SQL: " . $e->getMessage());
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card m-4">
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

                <form id="profileForm" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
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
                                       value="<?= htmlspecialchars($utilisateur['pseudo']) ?>" 
                                       required
                                       disabled>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nom">Nom</label>
                                    <input type="text" class="form-control" id="nom" name="nom"
                                           value="<?= htmlspecialchars($utilisateur['nom']) ?>" 
                                           required
                                           disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="prenom">Prénom</label>
                                    <input type="text" class="form-control" id="prenom" name="prenom"
                                           value="<?= htmlspecialchars($utilisateur['prenom']) ?>" 
                                           required
                                           disabled>
                                </div>
                            </div>

                            <div class="mb-3">
    <label for="email">Email</label>
    <input type="email" class="form-control" id="email" name="email"
           value="<?= htmlspecialchars($utilisateur['email']) ?>" 
           pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
           required
           disabled
           autocomplete="email">
</div>


                            <div class="mb-3">
                                <label for="telephone">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone"
                                       value="<?= !empty($utilisateur['telephone']) ? htmlspecialchars($utilisateur['telephone']) : '' ?>" 
                                       pattern="[0-9]{10}"
                                       title="Le numéro doit contenir 10 chiffres"
                                       disabled>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="date_naissance">Date de naissance</label>
                                <input type="date" class="form-control" id="date_naissance" name="date_naissance"
                                       value="<?= !empty($utilisateur['date_naissance']) ? htmlspecialchars($utilisateur['date_naissance']) : '' ?>" 
                                       disabled>
                            </div>

                            <div class="mb-3">
                                <label for="adresse">Adresse</label>
                                <input type="text" class="form-control" id="adresse" name="adresse"
                                       value="<?= !empty($utilisateur['adresse']) ? htmlspecialchars($utilisateur['adresse']) : '' ?>" 
                                       disabled>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="code_postal">Code Postal</label>
                                    <input type="text" class="form-control" id="code_postal" name="code_postal" 
                                           maxlength="5" 
                                           pattern="[0-9]{5}"
                                           title="Le code postal doit contenir 5 chiffres"
                                           value="<?= !empty($utilisateur['code_postal']) ? htmlspecialchars($utilisateur['code_postal']) : '' ?>" 
                                           disabled>
                                </div>

                                <div class="col-md-8 mb-3">
                                    <label for="ville">Ville</label>
                                    <input type="text" class="form-control" id="ville" name="ville"
                                           list="villes-suggestions"
                                           value="<?= !empty($utilisateur['ville']) ? htmlspecialchars($utilisateur['ville']) : '' ?>" 
                                           disabled>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="button" class="buttonVert" id="edit-btn" onclick="editForm()">
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

<script>
function validateForm() {
    const telephone = document.getElementById('telephone');
    const email = document.getElementById('email');
    const codePostal = document.getElementById('code_postal');
    
    // Validation du téléphone
    if (telephone.value) {
        const phoneRegex = /^[0-9]{10}$/;
        if (!phoneRegex.test(telephone.value)) {
            alert("Le numéro de téléphone doit contenir 10 chiffres.");
            telephone.focus();
            return false;
        }
    }

    // Validation de l'email
    if (email.value) {
        const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailRegex.test(email.value)) {
            alert("L'adresse email n'est pas valide.");
            email.focus();
            return false;
        }
    }

    // Validation du code postal
    if (codePostal.value) {
        const cpRegex = /^[0-9]{5}$/;
        if (!cpRegex.test(codePostal.value)) {
            alert("Le code postal doit contenir 5 chiffres.");
            codePostal.focus();
            return false;
        }
    }

    return true;
}

// Setup de l'autocomplétion du code postal
function setupPostalAutocomplete() {
    const codePostalInput = document.getElementById('code_postal');
    const villeInput = document.getElementById('ville');
    let timeout = null;

    codePostalInput.addEventListener('input', function() {
        clearTimeout(timeout);
        const query = this.value;

        // Vérifier que le code postal a 5 chiffres
        if (query.length !== 5 || !/^\d+$/.test(query)) {
            return;
        }

        timeout = setTimeout(() => {
            fetch(`https://geo.api.gouv.fr/communes?codePostal=${encodeURIComponent(query)}&fields=nom,code,codesPostaux`)
                .then(response => response.json())
                .then(data => {
                    // Créer ou récupérer la datalist
                    let datalist = document.getElementById('villes-suggestions');
                    if (!datalist) {
                        datalist = document.createElement('datalist');
                        datalist.id = 'villes-suggestions';
                        document.body.appendChild(datalist);
                    }

                    // Lier la datalist à l'input ville
                    villeInput.setAttribute('list', 'villes-suggestions');

                    // Remplir la datalist avec les villes
                    datalist.innerHTML = data
                        .map(item => `<option value="${item.nom}">`)
                        .join('');

                    // Si une seule ville est trouvée, la sélectionner automatiquement
                    if (data.length === 1) {
                        villeInput.value = data[0].nom;
                    } else {
                        // Vider le champ ville si le code postal change
                        villeInput.value = '';
                    }

                    // Activer le champ ville
                    villeInput.disabled = false;
                })
                .catch(error => {
                    console.error('Erreur d\'autocomplétion:', error);
                });
        }, 300);
    });
}

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

    // Initialiser l'autocomplétion du code postal
    setupPostalAutocomplete();
    
    // Ajouter la validation en temps réel du code postal
    const codePostalInput = document.getElementById('code_postal');
    codePostalInput.addEventListener('input', function() {
        validatePostalCode(this);
    });
});

function validatePostalCode(input) {
    const value = input.value;
    if (value.length > 5) {
        input.value = value.slice(0, 5);
    }
    // Ne garder que les chiffres
    input.value = input.value.replace(/\D/g, '');
}

function editForm() {
    // Activer tous les champs
    document.querySelectorAll('#profileForm input, #profileForm select').forEach(input => {
        if (input.id !== 'ville') { // Ne pas activer le champ ville tout de suite
            input.disabled = false;
        }
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