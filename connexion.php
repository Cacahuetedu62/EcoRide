<?php
// Inclusion des fichiers nécessaires
require_once('lib/pdo.php');
require_once('lib/config.php');

// Fonction pour logger les tentatives de connexion
function logLoginAttempt($pdo, $pseudo, $success) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("INSERT INTO login_attempts (pseudo, ip_address, success, attempt_date) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$pseudo, $ip, $success]);
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Session expirée. Veuillez rafraîchir la page.');
    }

    if (isset($_POST['pseudo']) && isset($_POST['password'])) {
        // Nettoyage des entrées
        $pseudo = htmlspecialchars(trim($_POST['pseudo']));
        $password = $_POST['password'];

        // Recherche de l'utilisateur dans la base de données
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = :pseudo");
        $stmt->execute(['pseudo' => $pseudo]);
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($utilisateur) {
            // Vérification si le compte est suspendu
            if ($utilisateur['suspendu'] == 1) {
                logLoginAttempt($pdo, $pseudo, false);
                $error = "Votre compte a été suspendu. Veuillez contacter le support.";
            } 
            // Vérification du mot de passe
            elseif (password_verify($password, $utilisateur['password'])) {
                // Connexion réussie
                logLoginAttempt($pdo, $pseudo, true);
                $_SESSION['utilisateur'] = [
                    'id' => $utilisateur['id'],
                    'pseudo' => $utilisateur['pseudo'],
                    'credits' => $utilisateur['credits'],
                    'type_acces' => $utilisateur['type_acces']
                ];
                
                // Régénération de l'ID de session pour la sécurité
                session_regenerate_id(true);
                
                header('Location: index.php');
                exit;
            } else {
                logLoginAttempt($pdo, $pseudo, false);
                $error = "Identifiants incorrects.";
            }
        } else {
            logLoginAttempt($pdo, $pseudo, false);
            $error = "Identifiants incorrects.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}

require_once('templates/header.php');
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="card-title text-center mb-4">Connexion</h2>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="login-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <div class="mb-3">
                            <label for="pseudo" class="form-label">Pseudo</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="pseudo" 
                                   name="pseudo" 
                                   required 
                                   maxlength="50" 
                                   value="<?= isset($_POST['pseudo']) ? htmlspecialchars($_POST['pseudo']) : '' ?>">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       required>
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        id="togglePassword" 
                                        aria-label="Afficher/masquer le mot de passe">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 mb-3">Se connecter</button>
                        
                        <p class="text-center mb-0">
                            Pas encore de compte ? 
                            <a href="inscription.php" class="text-decoration-none">S'inscrire</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.login-form');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    // Gestion de l'affichage/masquage du mot de passe
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    }

    // Validation du formulaire
    if (form) {
        form.addEventListener('submit', function(event) {
            const pseudo = document.getElementById('pseudo').value.trim();
            const password = passwordInput.value;
            
            if (!pseudo || !password) {
                event.preventDefault();
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger mt-3';
                alert.textContent = 'Veuillez remplir tous les champs';
                form.insertBefore(alert, form.firstChild);
                setTimeout(() => alert.remove(), 3000);
            }
        });
    }
});
</script>

<?php
require_once('templates/footer.php');
?>