<?php
// Activer l'affichage des erreurs PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session avant tout output HTML
session_start();

require_once('lib/config.php');
require_once('lib/pdo.php');
require_once('lib/config.prod.php');

function logMessage($message) {
    $logFile = __DIR__ . '/logs/logs.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    error_log($logEntry, 3, $logFile);
}

function getConnection() {
    try {
        $config = require 'lib/config.prod.php';
        $pdo = new PDO(
            "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4",
            $config['db']['user'],
            $config['db']['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log('Erreur PDO : ' . $e->getMessage());
        throw $e;
    }
}

$erreur = '';
$debug_messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pseudo = trim($_POST['pseudo'] ?? '');
        $motdepasse = $_POST['motdepasse'] ?? '';
        
        $debug_messages[] = "Tentative de connexion pour : " . htmlspecialchars($pseudo);
        
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = :pseudo");
        $stmt->execute(['pseudo' => $pseudo]);
        $utilisateur = $stmt->fetch();
        
        if ($utilisateur && password_verify($motdepasse, $utilisateur['password'])) {
            $_SESSION['utilisateur'] = [
                'id' => $utilisateur['id'],
                'pseudo' => $utilisateur['pseudo'],
                'credits' => $utilisateur['credits'] ?? 0
            ];
            $debug_messages[] = "Connexion réussie";
            header('Location: index.php');
            exit();
        } else {
            $erreur = 'Identifiants incorrects';
            $debug_messages[] = "Échec de l'authentification";
        }
    } catch (Exception $e) {
        $erreur = 'Une erreur est survenue lors de la connexion';
        $debug_messages[] = "Erreur : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - EcoRide</title>
    <!-- Ajoutez ici vos liens CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php if (!empty($debug_messages) && isset($_GET['debug'])): ?>
        <div class="debug-info" style="background: #f8f9fa; padding: 15px; margin: 15px; border: 1px solid #ddd;">
            <h3>Informations de débogage :</h3>
            <pre><?php print_r($debug_messages); ?></pre>
            <h4>Contenu de $_SESSION :</h4>
            <pre><?php print_r($_SESSION); ?></pre>
        </div>
    <?php endif; ?>

    <div class="container">
        <section class="loginRegister d-flex justify-content-center m-5">
            <div class="loginRegister-container p-2">
                <h2 class="text-center">Se connecter</h2>
                
                <?php if (!empty($erreur)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($erreur); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="login-form">
                    <div class="mb-3">
                        <label for="pseudo" class="form-label">Pseudo :</label>
                        <input type="text" 
                               class="form-control" 
                               name="pseudo" 
                               id="pseudo" 
                               required 
                               value="<?php echo isset($_POST['pseudo']) ? htmlspecialchars($_POST['pseudo']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe :</label>
                        <input type="password" 
                               class="form-control" 
                               name="motdepasse" 
                               id="password" 
                               required>
                    </div>

                    <div class="text-center">
                        <button class="btn buttonVert m-3" type="submit">Se connecter</button>
                    </div>
                </form>

                <div class="text-center">
                    <p>Pas de compte ? <a href="inscription.php">Créer un compte</a></p>
                </div>
            </div>
        </section>
    </div>


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
