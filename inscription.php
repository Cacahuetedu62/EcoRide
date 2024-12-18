<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');
?>

<main>

<section class="loginRegister d-flex justify-content-center mt-5">
    <div class="loginRegister-container">
        <h2 class="text-center">S'inscrire</h2>
        <p class="text-center">Nous sommes ravis de vous accueillir sur notre plateforme de covoiturage électrique. Merci de rejoindre notre communauté pour un futur plus durable !</p>
        <form action="inscription.php" method="post">
            
            <!-- Pseudo -->
            <div class="form-group p-2">
                <label for="pseudo">Pseudo</label>
                <input type="text" id="pseudo" name="pseudo" class="form-control" required placeholder="Entrez votre pseudo">
            </div>


            <!-- Email -->
            <div class="form-group p-2">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required placeholder="Entrez votre email">
            </div>

            <div class="form-group p-2">
    <p>Votre mot de passe doit contenir au moins 8 caractères, dont : <br> - Une majuscule, <br>
    - Une minuscule, <br>
    - Un chiffre et <br>
    - Un caractère spécial (par exemple, @, #, $, %).</p>
</div>

<!-- Mot de passe -->
<div class="form-group p-2">
    <label for="password">Mot de passe</label>
    <input type="password" id="password" name="password" class="form-control" required placeholder="Entrez votre mot de passe">
</div>

<!-- Confirmation du mot de passe -->
<div class="form-group p-2">
    <label for="confirm_password">Confirmer le mot de passe</label>
    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required placeholder="Confirmer votre mot de passe">
</div>

<!-- Message d'erreur (initialement caché) -->
<div id="error-message" class="form-group p-2 text-danger" style="display: none;">
    <p>Le mot de passe n'est pas sécurisé ou les mots de passe ne correspondent pas. Veuillez réessayer.</p>
</div>

            <!-- Bouton de soumission -->
            <button type="submit" class="buttonVert m-2">S'inscrire</button>
        </form>
    </div>
</section>

<script>
    // Fonction de validation du mot de passe
    document.querySelector("form").addEventListener("submit", function(event) {
        var password = document.getElementById("password").value;
        var confirmPassword = document.getElementById("confirm_password").value;

        // Expression régulière pour vérifier la sécurité du mot de passe (8 caractères, au moins 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial)
        var passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,}$/;

        // Vérification de la sécurité du mot de passe
        if (!passwordRegex.test(password)) {
            document.getElementById("error-message").style.display = "block";
            event.preventDefault();  // Empêcher l'envoi du formulaire
            return;
        }

        // Vérification que les mots de passe correspondent
        if (password !== confirmPassword) {
            document.getElementById("error-message").style.display = "block";
            event.preventDefault();  // Empêcher l'envoi du formulaire
            return;
        }

        // Si tout est correct, on cache le message d'erreur
        document.getElementById("error-message").style.display = "none";
    });
</script>


</main>


<?php
require_once('templates/footer.php');
?>