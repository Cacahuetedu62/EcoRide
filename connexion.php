<?php
// Activer les rapports d'erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('lib/pdo.php');
require_once('lib/config.php');

// Démarrer la session avant toute logique de connexion
session_start();

// Vérifier si une URL de redirection existe dans la requête
if (isset($_GET['redirect'])) {
    $_SESSION['redirect_after_login'] = urldecode($_GET['redirect']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['pseudo']) && isset($_POST['password'])) {
        $pseudo = $_POST['pseudo'];
        $password = $_POST['password'];

        // Recherche de l'utilisateur dans la base de données
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = :pseudo");
        $stmt->execute(['pseudo' => $pseudo]);
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($utilisateur) {
            // Vérification si le compte est suspendu
            if ($utilisateur['suspendu'] == 1) {
                echo "<div class='alert alert-danger'>Votre compte a été suspendu. Veuillez contacter le support.</div>";
                exit;
            }

            if (password_verify($password, $utilisateur['password'])) {
                // Régénérer l'ID de session pour plus de sécurité
                session_regenerate_id(true);
            
                $_SESSION['utilisateur'] = [
                    'id' => $utilisateur['id'],
                    'pseudo' => $utilisateur['pseudo'],
                    'credits' => $utilisateur['credits'],
                    'type_acces' => $utilisateur['type_acces']
                ];

                // Redirection intelligente
                $redirect_url = isset($_SESSION['redirect_after_login']) 
                    ? $_SESSION['redirect_after_login'] 
                    : 'index.php';
                
                unset($_SESSION['redirect_after_login']); // Nettoyer après utilisation
                
                header("Location: $redirect_url");
                exit;
            } else {
                echo "<div class='alert alert-danger'>Identifiants incorrects! Veuillez réessayer.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Identifiants incorrects! Veuillez réessayer.</div>";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}

// Inclusion du header et du contenu HTML
require_once('templates/header.php');
?>

<section class="loginRegister d-flex justify-content-center m-5">
    <div class="loginRegister-container p-2">
        <h2 class="text-center">Se connecter</h2>
        <form method="POST" class="login-form">
            <label for="pseudo">Pseudo :</label>
            <input type="text" name="pseudo" id="pseudo" required>

            <label for="password">Mot de passe :</label>
            <input type="password" name="password" id="password" required>

            <button class="buttonVert m-3" type="submit">Se connecter</button>
        </form>
        <div class="text-center">
            <p>Pas de compte ? <a href="inscription.php">Créer un compte</a></p>
        </div>
    </div>
</section>

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