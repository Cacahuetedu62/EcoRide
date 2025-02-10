<?php
require_once('lib/session_config.php');
session_start();

require_once('lib/config.php');
require_once('lib/pdo.php');

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=db;dbname=ecoride;charset=utf8mb4", 'ecoride_user', 'secure_password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tableaux à vérifier
    $tables = [
        'utilisateurs',
        'voitures',
        'trajets',
        'trajet_utilisateur',
        'avis'
    ];

    // Vérification du nombre d'enregistrements dans chaque table
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "Table $table : $count enregistrements<br>";
        } catch (PDOException $e) {
            echo "Erreur pour la table $table : " . $e->getMessage() . "<br>";
        }
    }
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}

// Affichage des paramètres de connexion et test de la connexion
try {
    echo "Paramètres de connexion : \n";
    echo "Hôte : " . DB_HOST . "\n";
    echo "Base de données : " . DB_NAME . "\n";
    echo "Utilisateur : " . DB_USER . "\n";
    echo "Port : " . DB_PORT . "\n";

    // Test de la connexion en récupérant un utilisateur
    $stmt = $pdo->query("SELECT * FROM utilisateurs LIMIT 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
} catch (PDOException $e) {
    echo "Erreur détaillée : " . $e->getMessage();
}

// Traitement du formulaire de connexion
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

            // Vérification du mot de passe
            if (password_verify($password, $utilisateur['password'])) {
                session_start();
                $_SESSION['utilisateur'] = [
                    'id' => $utilisateur['id'],
                    'pseudo' => $utilisateur['pseudo'],
                    'credits' => $utilisateur['credits'],
                    'type_acces' => $utilisateur['type_acces']
                ];

                // Redirection vers la page d'accueil après la connexion
                header('Location: index.php');
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

<a href="index.php">Retour à l'accueil</a>

<section class="loginRegister d-flex justify-content-center mt-5">
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
