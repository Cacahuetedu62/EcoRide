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

// Tableau pour stocker les logs
$logs = [];

// Ajouter du débogage détaillé
$logs[] = "=== DÉBUT PROCESSUS CONNEXION ===";
$logs[] = "Session ID initial: " . session_id();
$logs[] = "Session initiale: " . print_r($_SESSION, true);
$logs[] = "REQUEST_URI: " . $_SERVER['REQUEST_URI'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logs[] = "=== TRAITEMENT POST ===";
    $logs[] = "Données POST: " . print_r($_POST, true);

    if (isset($_POST['pseudo']) && isset($_POST['password'])) {
        $logs[] = "Champs de connexion reçus";
        $pseudo = trim($_POST['pseudo']);
        $password = $_POST['password'];

        try {
            // Recherche de l'utilisateur
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = :pseudo");
            $stmt->execute(['pseudo' => $pseudo]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

            $logs[] = "Recherche utilisateur pour: " . $pseudo;
            $logs[] = "Résultat: " . ($utilisateur ? "trouvé" : "non trouvé");

            if ($utilisateur) {
                if ($utilisateur['suspendu'] == 1) {
                    $error = "Votre compte a été suspendu. Veuillez contacter le support.";
                    $logs[] = "Tentative sur compte suspendu: " . $pseudo;
                } elseif (password_verify($password, $utilisateur['password'])) {
                    $logs[] = "=== CONNEXION RÉUSSIE ===";
                    
                    // Régénérer l'ID de session
                    session_regenerate_id(true);
                    $logs[] = "Nouveau Session ID: " . session_id();
                
                    // Stocker les infos utilisateur en session
                    $_SESSION['utilisateur'] = [
                        'id' => $utilisateur['id'],
                        'pseudo' => $utilisateur['pseudo'],
                        'credits' => $utilisateur['credits'],
                        'type_acces' => $utilisateur['type_acces']
                    ];

                    $logs[] = "Session après login: " . print_r($_SESSION, true);

                    // Construire l'URL de redirection
                    $base_url = 'https://' . $_SERVER['HTTP_HOST'];
                    $redirect_url = $base_url . '/index.php';
                    
                    $logs[] = "=== REDIRECTION ===";
                    $logs[] = "URL de redirection: " . $redirect_url;
                    
                    // Vider tout buffer de sortie
                    while (ob_get_level()) {
                        ob_end_clean();
                    }

                    // Redirection avec URL absolue
                    header("Location: " . $redirect_url);
                    exit();
                } else {
                    $error = "Identifiants incorrects! Veuillez réessayer.";
                    $logs[] = "Échec vérification mot de passe: " . $pseudo;
                }
            } else {
                $error = "Identifiants incorrects! Veuillez réessayer.";
                $logs[] = "Utilisateur non trouvé: " . $pseudo;
            }
        } catch (PDOException $e) {
            $error = "Une erreur est survenue. Veuillez réessayer plus tard.";
            $logs[] = "Erreur PDO: " . $e->getMessage();
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
        $logs[] = "Formulaire incomplet";
    }
}


// Inclusion du header et du contenu HTML
require_once('templates/header.php');

// Afficher les erreurs s'il y en a
if (isset($error)) {
    echo "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>";
}

// Afficher les logs
echo "<pre>" . implode("\n", $logs) . "</pre>";
?>

<section class="loginRegister d-flex justify-content-center m-5">
    <div class="loginRegister-container p-2">
        <h2 class="text-center">Se connecter</h2>
        <form method="POST" class="login-form">
            <label for="pseudo">Pseudo :</label>
            <input type="text" name="pseudo" id="pseudo" required 
                   value="<?php echo isset($_POST['pseudo']) ? htmlspecialchars($_POST['pseudo']) : ''; ?>">

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

<?php require_once('templates/footer.php'); ?>