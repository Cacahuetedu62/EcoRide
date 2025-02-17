<?php
// Activer les rapports d'erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('lib/pdo.php');
require_once('lib/config.php');

// Vérifier si la session n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ajouter du débogage détaillé
error_log("Début du processus de connexion");
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));

// Vérifier si une URL de redirection existe dans la requête
if (isset($_GET['redirect'])) {
    $_SESSION['redirect_after_login'] = urldecode($_GET['redirect']);
    error_log("URL de redirection définie: " . $_SESSION['redirect_after_login']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Tentative de connexion reçue");
    error_log("POST data: " . print_r($_POST, true));

    if (isset($_POST['pseudo']) && isset($_POST['password'])) {
        $pseudo = trim($_POST['pseudo']);
        $password = $_POST['password'];

        error_log("Tentative de connexion pour l'utilisateur: " . $pseudo);

        try {
            // Recherche de l'utilisateur dans la base de données
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = :pseudo");
            $stmt->execute(['pseudo' => $pseudo]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

            error_log("Résultat de la recherche utilisateur: " . ($utilisateur ? "trouvé" : "non trouvé"));

            if ($utilisateur) {
                // Vérification si le compte est suspendu
                if ($utilisateur['suspendu'] == 1) {
                    error_log("Tentative de connexion à un compte suspendu: " . $pseudo);
                    echo "<div class='alert alert-danger'>Votre compte a été suspendu. Veuillez contacter le support.</div>";
                    exit;
                }

                if (password_verify($password, $utilisateur['password'])) {
                    error_log("Mot de passe vérifié avec succès pour: " . $pseudo);
                    
                    // Régénérer l'ID de session pour plus de sécurité
                    session_regenerate_id(true);
                    error_log("Nouveau Session ID: " . session_id());
                
                    $_SESSION['utilisateur'] = [
                        'id' => $utilisateur['id'],
                        'pseudo' => $utilisateur['pseudo'],
                        'credits' => $utilisateur['credits'],
                        'type_acces' => $utilisateur['type_acces']
                    ];

                    error_log("Session après connexion: " . print_r($_SESSION, true));

                    // Redirection intelligente
                    $redirect_url = isset($_SESSION['redirect_after_login']) 
                        ? $_SESSION['redirect_after_login'] 
                        : 'index.php';
                    
                    unset($_SESSION['redirect_after_login']); // Nettoyer après utilisation
                    
                    error_log("Redirection vers: " . $redirect_url);
                    
                    // Assurez-vous que la sortie est vide avant la redirection
                    ob_clean();
                    header("Location: " . $redirect_url);
                    exit();
                } else {
                    error_log("Échec de la vérification du mot de passe pour: " . $pseudo);
                    $error = "Identifiants incorrects! Veuillez réessayer.";
                }
            } else {
                error_log("Utilisateur non trouvé: " . $pseudo);
                $error = "Identifiants incorrects! Veuillez réessayer.";
            }
        } catch (PDOException $e) {
            error_log("Erreur PDO lors de la connexion: " . $e->getMessage());
            $error = "Une erreur est survenue. Veuillez réessayer plus tard.";
        }
    } else {
        error_log("Formulaire incomplet");
        $error = "Veuillez remplir tous les champs.";
    }
}

// Inclusion du header et du contenu HTML
require_once('templates/header.php');

// Afficher les erreurs s'il y en a
if (isset($error)) {
    echo "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>";
}
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
    const passwordInput = document.getElementById('password');

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