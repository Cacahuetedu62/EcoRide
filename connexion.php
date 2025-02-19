<?php
session_start(); // Ajout de session_start() au début
require_once('lib/config.php');
require_once('lib/pdo.php');
require_once('lib/config.prod.php');

// Fonction pour se connecter à la base de données
function getConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $config = require 'lib/config.prod.php';
    try {
        $pdo = new PDO(
            "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4",
            $config['db']['user'],
            $config['db']['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        logMessage('Erreur de connexion : ' . $e->getMessage());
        die('Erreur de connexion à la base de données.');
    }
}

// Fonction pour ajouter des logs avec plus de détails
function logMessage($message) {
    $logFile = __DIR__ . '/logs/logs.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    
    // Vérification du dossier logs
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Variables pour stocker les messages
$erreur = '';
$success = '';

// Vérification de la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $motdepasse = $_POST['motdepasse'] ?? '';

    if (empty($pseudo) || empty($motdepasse)) {
        $erreur = 'Veuillez remplir tous les champs';
        logMessage('Tentative de connexion avec champs vides');
    } else {
        try {
            // Connexion à la base de données
            $pdo = getConnection();

            // Requête préparée pour vérifier les identifiants
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = :pseudo");
            $stmt->execute(['pseudo' => $pseudo]);
            $utilisateur = $stmt->fetch();

            logMessage("Tentative de connexion pour l'utilisateur: $pseudo");

            if ($utilisateur && password_verify($motdepasse, $utilisateur['motdepasse'])) {
                // Authentification réussie
                $_SESSION['user_id'] = $utilisateur['id'];
                $_SESSION['pseudo'] = $utilisateur['pseudo'];
                logMessage("Connexion réussie pour l'utilisateur: $pseudo");
                
                header('Location: accueil.php');
                exit();
            } else {
                $erreur = 'Identifiants incorrects';
                logMessage("Échec de connexion pour l'utilisateur: $pseudo");
            }
        } catch (PDOException $e) {
            logMessage('Erreur PDO : ' . $e->getMessage());
            $erreur = 'Une erreur est survenue lors de la connexion';
        }
    }
}
?>

<!-- Affichage des messages d'erreur ou de succès -->
<?php if (!empty($erreur)): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($erreur); ?>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<section class="loginRegister d-flex justify-content-center m-5">
    <div class="loginRegister-container p-2">
        <h2 class="text-center">Se connecter</h2>
        <form method="POST" class="login-form">
            <label for="pseudo">Pseudo :</label>
            <input type="text" name="pseudo" id="pseudo" required 
                   value="<?php echo isset($_POST['pseudo']) ? htmlspecialchars($_POST['pseudo']) : ''; ?>">

            <label for="password">Mot de passe :</label>
            <input type="password" name="motdepasse" id="password" required>

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
