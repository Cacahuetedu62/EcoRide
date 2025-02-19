<?php
require_once('lib/config.php');
require_once('lib/pdo.php');
require_once('lib/config.prod.php');


// Fonction pour se connecter à la base de données
function getConnection() {
    $config = require 'lib/config.prod.php'; // Charge les paramètres de configuration

    $host = $config['db']['host']; // Hôte de la base de données
    $dbname = $config['db']['name']; // Nom de la base de données
    $username = $config['db']['user']; // Nom d'utilisateur de la base de données
    $password = $config['db']['pass']; // Mot de passe de la base de données

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        logMessage('Erreur de connexion : ' . $e->getMessage());
        die('Erreur de connexion à la base de données.');
    }
}

// Fonction pour ajouter des logs
function logMessage($message) {
    $logFile = 'logs/logs.txt'; // Chemin du fichier de log
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Vérification de la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = $_POST['pseudo'];
    $motdepasse = $_POST['motdepasse'];

    // Connexion à la base de données
    $pdo = getConnection();

    // Requête préparée pour vérifier les identifiants
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = :pseudo");
    $stmt->execute(['pseudo' => $pseudo]);
    $utilisateur = $stmt->fetch();

    if ($utilisateur && password_verify($motdepasse, $utilisateur['motdepasse'])) {
        // Authentification réussie
        $_SESSION['user_id'] = $utilisateur['id'];
        $_SESSION['pseudo'] = $utilisateur['pseudo'];
        logMessage('Connexion réussie pour l\'utilisateur : ' . $pseudo);
        header('Location: accueil.php'); // Redirige vers la page d'accueil
        exit();
    } else {
        // Authentification échouée
        logMessage('Tentative de connexion échouée pour l\'utilisateur : ' . $pseudo);
        $erreur = 'Identifiants incorrects. Veuillez réessayer.';
    }
}
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
