<?php
require_once('lib/pdo.php');
require_once('lib/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifie si les champs pseudo et password sont définis
    if (isset($_POST['pseudo']) && isset($_POST['password'])) {
        $pseudo = $_POST['pseudo'];
        $password = $_POST['password'];

        // Préparer la requête pour récupérer l'utilisateur dans la base de données
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = :pseudo");
        $stmt->execute(['pseudo' => $pseudo]);
        $utilisateur = $stmt->fetch();

        // Vérifie si l'utilisateur existe et si le mot de passe est correct
        if ($utilisateur && password_verify($password, $utilisateur['password'])) {
            // Si l'utilisateur existe et le mot de passe est correct
            session_start();

            // Enregistrer les informations de l'utilisateur dans la session
            $_SESSION['utilisateur'] = [
                'id' => $utilisateur['id'],
                'pseudo' => $utilisateur['pseudo'],
                'credits' => $utilisateur['credits'],
            ];
            
            // Redirige vers la page d'accueil après la connexion
            header('Location: index.php');
            exit; // On s'assure de sortir après une redirection pour éviter les erreurs
        } else {
            // Si les identifiants sont incorrects
            echo "Identifiants incorrects!";
        }
    } else {
        // Si les champs du formulaire ne sont pas remplis correctement
        echo "Veuillez remplir tous les champs!";
    }
}

require_once('templates/header.php');
?>

<main>
<section class="loginRegister d-flex justify-content-center mt-5">
    <div class="loginRegister-container">
        <h2 class="text-center">Se connecter</h2>
        <form method="POST">
            <label for="pseudo">Pseudo :</label>
            <input type="text" name="pseudo" id="pseudo" required>
            
            <label for="password">Mot de passe :</label>
            <input type="password" name="password" id="password" required>

            <button type="submit">Se connecter</button>
        </form>
        <div class="text-center">
            <p>Pas de compte ? <a href="inscription.php">Créer un compte</a></p>
        </div>
    </div>
</section>
</main>

<?php
require_once('templates/footer.php');
?>
