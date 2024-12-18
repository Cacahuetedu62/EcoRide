<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');
?>

<main>

<section class="loginRegister d-flex justify-content-center mt-5">
<div class="loginRegister-container">
<h2 class="text-center">Se connecter</h2>
        <form action="connexion.php" method="post">
            <!-- Email -->
            <div class="form-group p-2">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required placeholder="Entrez votre email">
            </div>

            <!-- Mot de passe -->
            <div class="form-group p-2">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="Entrez votre mot de passe">
            </div>

            <!-- Bouton de connexion -->
            <button type="submit" class="buttonVert m-2" >Se connecter</button>
        </form>

        <div class="text-center">
            <p>Pas de compte ? <a href="inscription.php">Créer un compte</a></p>
        </div>
    </div>
<section>


</main>


<?php
require_once('templates/footer.php');
?>