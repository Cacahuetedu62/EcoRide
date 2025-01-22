<?php
session_start();

// Générer le token CSRF s'il n'existe pas
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once('lib/pdo.php');
require_once('lib/config.php');

// Variable pour stocker le message d'erreur
$error = null;
$redirect = false;

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Session expirée. Veuillez rafraîchir la page.';
    } else if (isset($_POST['pseudo']) && isset($_POST['password'])) {
        $pseudo = htmlspecialchars(trim($_POST['pseudo']));
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = :pseudo");
        $stmt->execute(['pseudo' => $pseudo]);
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($utilisateur) {
            if ($utilisateur['suspendu'] == 1) {
                $error = "Votre compte a été suspendu. Veuillez contacter le support.";
            } elseif (password_verify($password, $utilisateur['password'])) {
                $_SESSION['utilisateur'] = [
                    'id' => $utilisateur['id'],
                    'pseudo' => $utilisateur['pseudo'],
                    'credits' => $utilisateur['credits'],
                    'type_acces' => $utilisateur['type_acces']
                ];
                
                session_regenerate_id(true);
                header('Location: index.php');
                exit;
            } else {
                $error = "Identifiants incorrects.";
            }
        } else {
            $error = "Identifiants incorrects.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}

// Maintenant seulement, on inclut le header et le contenu HTML
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

                    <form method="POST" class="login-form" action="connexion.php" novalidate>
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