<?php
require_once('templates/header.php');

// Génération et vérification du token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Session expirée. Veuillez rafraîchir la page.');
    }

    $errors = [];

    // Nettoyage et récupération des données
    $pseudo = htmlspecialchars(trim($_POST['pseudo']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nom = htmlspecialchars(trim($_POST['nom']));
    $prenom = htmlspecialchars(trim($_POST['prenom']));

    // Validations
    if (empty($nom) || empty($prenom)) {
        $errors[] = "Le nom et le prénom sont obligatoires.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }

    $passwordRegex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,}$/";
    if (!preg_match($passwordRegex, $password)) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.";
    }

    // Vérification de l'unicité de l'email
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetchColumn()) {
        $errors[] = "Cette adresse email est déjà utilisée.";
    }

    // Vérification de l'unicité du pseudo
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE pseudo = :pseudo");
    $stmt->execute(['pseudo' => $pseudo]);
    if ($stmt->fetchColumn()) {
        $errors[] = "Ce pseudo est déjà utilisé.";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO utilisateurs (pseudo, email, password, nom, prenom, credits)
                    VALUES (:pseudo, :email, :password, :nom, :prenom, 20)";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                ':pseudo' => $pseudo,
                ':email' => $email,
                ':password' => $hashed_password,
                ':nom' => $nom,
                ':prenom' => $prenom
            ]);

            if ($success) {
                $utilisateur_id = $pdo->lastInsertId();
                $_SESSION['utilisateur'] = [
                    'id' => $utilisateur_id,
                    'pseudo' => $pseudo,
                    'credits' => 20
                ];

                $pdo->commit();
                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Une erreur est survenue lors de l'inscription.";
        }
    }
}
?>

<div class="signup-container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card signup-card">
                <div class="card-body p-4 p-md-5">
                    <h2 class="signup-title text-center mb-4">Créer un compte</h2>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="signup-error-list">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="signup-promo-box">
                        Faites un geste pour la planète, on s'occupe du reste.<br>
                        <strong>20 crédits offerts</strong> à l'inscription !
                    </div>

                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="row mb-4">
                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <label for="nom" class="signup-label">Nom</label>
                                <input type="text" class="form-control signup-input" id="nom" name="nom" required
                                       value="<?= isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : '' ?>">
                            </div>

                            <div class="col-sm-6">
                                <label for="prenom" class="signup-label">Prénom</label>
                                <input type="text" class="form-control signup-input" id="prenom" name="prenom" required
                                       value="<?= isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : '' ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="pseudo" class="signup-label">Pseudo</label>
                            <input type="text" class="form-control signup-input" id="pseudo" name="pseudo" required
                                   value="<?= isset($_POST['pseudo']) ? htmlspecialchars($_POST['pseudo']) : '' ?>">
                        </div>

                        <div class="mb-4">
                            <label for="email" class="signup-label">Email</label>
                            <input type="email" class="form-control signup-input" id="email" name="email" required
                                   value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                        </div>

                        <div class="mb-4">
                            <label for="password" class="signup-label">Mot de passe</label>
                            <div class="input-group">
                                <input type="password" class="form-control signup-input rounded" id="password" name="password" required>
                                <div class="input-group-append position-absolute end-0 top-50 translate-middle-y me-2">
                                    <i class="bi bi-eye-slash text-muted" style="cursor: pointer;" id="togglePassword"></i>
                                </div>
                            </div>
                            <div class="text-center mt-2 text-muted small">
                                Le mot de passe doit contenir au moins :
                                <div class="text-center">
                                    <span>• 8 caractères</span><br>
                                    <span>• Une majuscule</span><br>
                                    <span>• Une minuscule</span><br>
                                    <span>• Un chiffre</span><br>
                                    <span>• Un caractère spécial (@, #, $, %...)</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="signup-label">Confirmer le mot de passe</label>
                            <div class="input-group">
                                <input type="password" class="form-control signup-input rounded" id="confirm_password" name="confirm_password" required>
                                <div class="input-group-append position-absolute end-0 top-50 translate-middle-y me-2">
                                    <i class="bi bi-eye-slash text-muted" style="cursor: pointer;" id="toggleConfirmPassword"></i>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn signup-submit-btn w-50 p-2 m-2">S'inscrire</button>

                        <p class="text-center mb-0">
                            Déjà inscrit ? <a href="connexion.php" class="signup-login-link">Se connecter</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour basculer la visibilité du mot de passe
    function setupPasswordToggle(inputId, toggleId) {
        const input = document.getElementById(inputId);
        const toggle = document.getElementById(toggleId);

        if (input && toggle) {
            toggle.addEventListener('click', function() {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
            });
        }
    }

    // Configuration des deux champs de mot de passe
    setupPasswordToggle('password', 'togglePassword');
    setupPasswordToggle('confirm_password', 'toggleConfirmPassword');

    // Validation du formulaire
    const form = document.querySelector('form');
    form.addEventListener('submit', function(event) {
        if (!this.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }

        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,}$/;

        if (!passwordRegex.test(password)) {
            event.preventDefault();
            alert('Le mot de passe ne respecte pas les critères de sécurité requis.');
            return;
        }

        if (password !== confirmPassword) {
            event.preventDefault();
            alert('Les mots de passe ne correspondent pas.');
            return;
        }
    });

    // Fonction pour changer l'icône de visibilité du mot de passe
    function togglePasswordVisibility(inputId, toggleId) {
        const input = document.getElementById(inputId);
        const toggleIcon = document.getElementById(toggleId).querySelector('i');

        document.getElementById(toggleId).addEventListener('click', function() {
            if (input.type === 'password') {
                input.type = 'text';
                toggleIcon.classList.replace('bi-eye-slash', 'bi-eye');
            } else {
                input.type = 'password';
                toggleIcon.classList.replace('bi-eye', 'bi-eye-slash');
            }
        });
    }

    togglePasswordVisibility('password', 'togglePassword');
    togglePasswordVisibility('confirm_password', 'toggleConfirmPassword');
});
</script>

<?php require_once('templates/footer.php'); ?>
