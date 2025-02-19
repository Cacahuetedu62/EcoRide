<?php
// Activer l'affichage des erreurs PHP pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure les fichiers de configuration une seule fois pour éviter les doublons
require_once('lib/config.php'); // Configuration de base
require_once('lib/pdo.php'); // Configuration de la connexion PDO
require_once('lib/config.prod.php'); // Configuration de production

// Fonction pour enregistrer les messages dans un fichier de log
function logMessage($message) {
    $logFile = __DIR__ . '/logs/logs.txt'; // Chemin vers le fichier de log
    $timestamp = date('Y-m-d H:i:s'); // Horodatage pour le log
    $logEntry = "[$timestamp] $message" . PHP_EOL; // Entrée de log formatée
    error_log($logEntry, 3, $logFile); // Enregistrer le message dans le fichier de log
}

// Fonction pour établir une connexion à la base de données
function getConnection() {
    try {
        $config = require 'lib/config.prod.php'; // Charger la configuration de la base de données
        // Créer une instance de PDO pour la connexion à la base de données
        $pdo = new PDO(
            "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4",
            $config['db']['user'],
            $config['db']['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION] // Activer le mode d'erreur pour les exceptions
        );
        return $pdo; // Retourner l'objet PDO pour l'utiliser
    } catch (PDOException $e) {
        error_log('Erreur PDO : ' . $e->getMessage()); // Enregistrer l'erreur dans le log
        throw $e; // Lancer l'exception
    }
}

$erreur = ''; // Variable pour stocker les messages d'erreur
$debug_messages = []; // Tableau pour stocker les messages de débogage

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupérer les données du formulaire et les nettoyer
        $pseudo = trim($_POST['pseudo'] ?? '');
        $motdepasse = $_POST['motdepasse'] ?? '';
        
        $debug_messages[] = "Tentative de connexion pour : " . htmlspecialchars($pseudo); // Enregistrer la tentative de connexion
        
        $pdo = getConnection(); // Établir la connexion à la base de données
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = :pseudo"); // Préparer la requête pour récupérer l'utilisateur
        $stmt->execute(['pseudo' => $pseudo]); // Exécuter la requête avec le pseudo
        $utilisateur = $stmt->fetch(); // Récupérer les données de l'utilisateur
        
        // Vérifier si l'utilisateur existe et si le mot de passe est correct
        if ($utilisateur && password_verify($motdepasse, $utilisateur['password'])) {
            // Enregistrer les informations de l'utilisateur dans la session
            $_SESSION['utilisateur'] = [
                'id' => $utilisateur['id'],
                'pseudo' => $utilisateur['pseudo'],
                'credits' => $utilisateur['credits'] ?? 0
            ];
            $debug_messages[] = "Connexion réussie"; // Enregistrer la réussite de la connexion
            header('Location: index.php'); // Rediriger vers la page d'accueil
            exit(); 
        } else {
            $erreur = 'Identifiants incorrects'; // Message d'erreur si les identifiants sont incorrects
            $debug_messages[] = "Échec de l'authentification"; // Enregistrer l'échec de la connexion
        }
    } catch (Exception $e) {
        $erreur = 'Une erreur est survenue lors de la connexion'; // Message d'erreur générique
        $debug_messages[] = "Erreur : " . $e->getMessage(); // Enregistrer l'erreur
    }
}
?>

<?php if (!empty($debug_messages) && isset($_GET['debug'])): ?>
    <div class="debug-info" style="background: #f8f9fa; padding: 15px; margin: 15px; border: 1px solid #ddd;">
        <h3>Informations de débogage :</h3>
        <pre><?php print_r($debug_messages); ?></pre> <!-- Afficher les messages de débogage -->
        <h4>Contenu de $_SESSION :</h4>
        <pre><?php print_r($_SESSION); ?></pre> <!-- Afficher le contenu de la session -->
    </div>
<?php endif; ?>

<div class="container">
    <section class="loginRegister d-flex justify-content-center m-5">
        <div class="loginRegister-container p-2">
            <h2 class="text-center">Se connecter</h2>
            
            <?php if (!empty($erreur)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($erreur); ?> <!-- Afficher le message d'erreur si présent -->
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
                           value="<?php echo isset($_POST['pseudo']) ? htmlspecialchars($_POST['pseudo']) : ''; ?>"> <!-- Pré-remplir le champ pseudo si déjà renseigné -->
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe :</label>
                    <input type="password" 
                           class="form-control" 
                           name="motdepasse" 
                           id="password" 
                           required> <!-- Champ pour le mot de passe -->
                </div>

                <div class="text-center">
                    <button class="btn buttonVert m-3" type="submit">Se connecter</button> <!-- Bouton de soumission -->
                </div>
            </form>

            <div class="text-center">
                <p>Pas de compte ? <a href="inscription.php">Créer un compte</a></p> <!-- Lien vers la page d'inscription -->
            </div>
        </div>
    </section>
</div>

<script>
// Script pour vérifier que les champs sont remplis avant la soumission du formulaire
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.login-form');
    const passwordInput = document.getElementById('password');

    if (form) {
        form.addEventListener('submit', function(event) {
            const pseudo = document.getElementById('pseudo').value.trim(); // Récupérer le pseudo
            const password = passwordInput.value; // Récupérer le mot de passe
            
            // Vérifier que les champs ne sont pas vides
            if (!pseudo || !password) {
                event.preventDefault(); // Empêcher l'envoi du formulaire
                const alert = document.createElement('div'); // Créer une alerte
                alert.className = 'alert alert-danger mt-3';
                alert.textContent = 'Veuillez remplir tous les champs'; // Message d'erreur
                form.insertBefore(alert, form.firstChild); // Afficher l'alerte au-dessus du formulaire
                setTimeout(() => alert.remove(), 3000); // Supprimer l'alerte après 3 secondes
            }
        });
    }
});
</script>

<?php require_once('templates/footer.php'); ?> <!-- Inclure le pied de page -->
