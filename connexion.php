<?php
// Activer l'affichage des erreurs PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session et vérifier son statut
session_start();
echo "<!-- Session ID: " . session_id() . " -->";
echo "<!-- Session status: " . session_status() . " -->";

require_once('lib/config.php');
require_once('lib/pdo.php');
require_once('lib/config.prod.php');

// Amélioration de la fonction de log
function logMessage($message) {
    $logFile = __DIR__ . '/logs/logs.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    
    // Vérification du dossier logs avec message d'erreur
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        echo "<!-- Création du dossier logs -->";
        if (!mkdir($logDir, 0777, true)) {
            echo "<!-- Erreur: Impossible de créer le dossier logs -->";
            error_log("Impossible de créer le dossier logs");
        }
    }
    
    // Vérification des permissions d'écriture
    if (!is_writable($logDir)) {
        echo "<!-- Erreur: Le dossier logs n'est pas accessible en écriture -->";
        error_log("Le dossier logs n'est pas accessible en écriture");
    }
    
    // Tentative d'écriture avec gestion d'erreur
    if (file_put_contents($logFile, $logEntry, FILE_APPEND) === false) {
        echo "<!-- Erreur: Impossible d'écrire dans le fichier de log -->";
        error_log("Impossible d'écrire dans le fichier de log");
    }
}

// Test de connexion à la base de données
function getConnection() {
    try {
        $config = require 'lib/config.prod.php';
        echo "<!-- Tentative de connexion à la base de données -->";
        
        $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4";
        echo "<!-- DSN: " . $dsn . " -->";
        
        $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        echo "<!-- Connexion à la base de données réussie -->";
        return $pdo;
    } catch (PDOException $e) {
        echo "<!-- Erreur PDO: " . htmlspecialchars($e->getMessage()) . " -->";
        logMessage('Erreur de connexion : ' . $e->getMessage());
        die('Erreur de connexion à la base de données: ' . $e->getMessage());
    }
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<!-- Début du traitement POST -->";
    
    $pseudo = trim($_POST['pseudo'] ?? '');
    $motdepasse = $_POST['motdepasse'] ?? '';
    
    echo "<!-- Pseudo reçu: " . htmlspecialchars($pseudo) . " -->";
    
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = :pseudo");
        $stmt->execute(['pseudo' => $pseudo]);
        
        echo "<!-- Requête SQL exécutée -->";
        
        $utilisateur = $stmt->fetch();
        
        if ($utilisateur) {
            echo "<!-- Utilisateur trouvé dans la base de données -->";
            
            if (password_verify($motdepasse, $utilisateur['password'])) {
                echo "<!-- Mot de passe vérifié avec succès -->";
                
                $_SESSION['utilisateur'] = [
                    'id' => $utilisateur['id'],
                    'pseudo' => $utilisateur['pseudo'],
                    'credits' => $utilisateur['credits'] ?? 0
                ];
                
                echo "<!-- Session créée -->";
                var_dump($_SESSION);  // Afficher le contenu de la session
                
                header('Location: index.php');
                exit();
            } else {
                echo "<!-- Échec de la vérification du mot de passe -->";
                $erreur = 'Identifiants incorrects';
            }
        } else {
            echo "<!-- Aucun utilisateur trouvé -->";
            $erreur = 'Identifiants incorrects';
        }
    } catch (Exception $e) {
        echo "<!-- Erreur: " . htmlspecialchars($e->getMessage()) . " -->";
        $erreur = 'Une erreur est survenue: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Connexion</title>
</head>
<body>
    <!-- Affichage du contenu de la session -->
    <div style="display: none;">
        <?php var_dump($_SESSION); ?>
    </div>

    <?php if (!empty($erreur)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($erreur); ?>
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
