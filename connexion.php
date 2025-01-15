<?php
require_once('lib/pdo.php');
require_once('lib/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['pseudo']) && isset($_POST['password'])) {
        $pseudo = $_POST['pseudo'];
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = :pseudo");
        $stmt->execute(['pseudo' => $pseudo]);
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($utilisateur) {
            if ($utilisateur['suspendu'] == 1) {
                echo "<div class='alert alert-danger'>Votre compte a été suspendu. Veuillez contacter le support.</div>";
                exit;
            }

            if (password_verify($password, $utilisateur['password'])) {
                session_start();
                $_SESSION['utilisateur'] = [
                    'id' => $utilisateur['id'],
                    'pseudo' => $utilisateur['pseudo'],
                    'credits' => $utilisateur['credits'],
                ];

                // Redirige vers la page d'accueil après la connexion avec une animation de transition
                header('Location: index.php');
                exit;
            } else {
                echo "<div class='alert alert-danger'>Identifiants incorrects! Veuillez réessayer.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Identifiants incorrects! Veuillez réessayer.</div>";
        }
    } else {
        echo "<div class='alert alert-warning'>Veuillez remplir tous les champs!</div>";
    }
}

require_once('templates/header.php');
?>

<main>
<section class="loginRegister d-flex justify-content-center mt-5">
    <div class="loginRegister-container p-2">
        <h2 class="text-center">Se connecter</h2>
        <form method="POST" class="login-form">
            <label for="pseudo">Pseudo :</label>
            <input type="text" name="pseudo" id="pseudo" required>

            <label for="password">Mot de passe :</label>
            <input type="password" name="password" id="password" required>

            <button class="btnInscription p-3" type="submit">Se connecter</button>
        </form>
        <div class="text-center">
            <p>Pas de compte ? <a href="inscription.php">Créer un compte</a></p>
        </div>
    </div>
</section>
</main>

<script>
    document.querySelector('.login-form').addEventListener('submit', function(event) {
    let pseudo = document.getElementById('pseudo').value;
    let password = document.getElementById('password').value;
    
    if (!pseudo || !password) {
        event.preventDefault();
        alert('Tous les champs doivent être remplis!');
    }
});
</script>

<?php
require_once('templates/footer.php');
?>
