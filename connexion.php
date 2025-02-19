<?php
session_start();
require_once('lib/config.php');
require_once('lib/pdo.php');
require_once('lib/config.prod.php');

// Fonction pour se connecter à la base de données
function getConnection() {
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

function logMessage($message) {
    $logFile = __DIR__ . '/logs/logs.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

$erreur = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $motdepasse = $_POST['motdepasse'] ?? '';

    if (empty($pseudo) || empty($motdepasse)) {
        $erreur = 'Veuillez remplir tous les champs';
        logMessage('Tentative de connexion avec champs vides');
    } else {
        try {
            $pdo = getConnection();
            
            logMessage("Tentative de connexion pour l'utilisateur: $pseudo");
            
            // Modification de la requête pour récupérer toutes les informations nécessaires
            $stmt = $pdo->prepare("SELECT id, pseudo, password, credits FROM utilisateurs WHERE pseudo = :pseudo");
            $stmt->execute(['pseudo' => $pseudo]);
            $utilisateur = $stmt->fetch();
            
            if ($utilisateur) {
                logMessage("Utilisateur trouvé dans la base de données");
                
                if (password_verify($motdepasse, $utilisateur['password'])) {
                    // Stockage des informations dans la session
                    $_SESSION['utilisateur'] = [
                        'id' => $utilisateur['id'],
                        'pseudo' => $utilisateur['pseudo'],
                        'credits' => $utilisateur['credits'] ?? 0
                    ];
                    
                    logMessage("Connexion réussie pour l'utilisateur: {$utilisateur['pseudo']}");
                    logMessage("Session créée avec ID: " . session_id());
                    
                    // Redirection vers index.php au lieu de accueil.php
                    header('Location: index.php');
                    exit();
                } else {
                    $erreur = 'Identifiants incorrects';
                    logMessage("Échec de la vérification du mot de passe pour l'utilisateur: $pseudo");
                }
            } else {
                $erreur = 'Identifiants incorrects';
                logMessage("Aucun utilisateur trouvé avec le pseudo: $pseudo");
            }
        } catch (PDOException $e) {
            logMessage('Erreur PDO : ' . $e->getMessage());
            $erreur = 'Une erreur est survenue lors de la connexion';
        }
    }
}
?>

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
