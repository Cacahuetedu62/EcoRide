<?php
ob_start();
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');


// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupérer les données du formulaire
    $pseudo = htmlspecialchars($_POST['pseudo']);
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nom = htmlspecialchars($_POST['nom']);  // Récupérer le nom
    $prenom = htmlspecialchars($_POST['prenom']);  // Récupérer le prénom

    // Vérification si les champs nom et prénom sont remplis
    if (empty($nom) || empty($prenom)) {
        echo "Le nom et le prénom sont obligatoires.";
        exit;
    }

    // Vérification si les mots de passe correspondent
    if ($password !== $confirm_password) {
        echo "Les mots de passe ne correspondent pas.";
        exit;
    }

    // Vérification de la sécurité du mot de passe (8 caractères, une majuscule, une minuscule, un chiffre, un caractère spécial)
    $passwordRegex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,}$/";
    if (!preg_match($passwordRegex, $password)) {
        echo "Le mot de passe n'est pas sécurisé.";
        exit;
    }

    // Hacher le mot de passe
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Vérifier si l'email existe déjà dans la base de données
    $sql = "SELECT * FROM utilisateurs WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_user) {
        echo "Un utilisateur avec cet e-mail existe déjà.";
        exit;
    }

    // Insérer l'utilisateur dans la base de données avec 20 crédits
    $sql = "INSERT INTO utilisateurs (pseudo, email, password, nom, prenom, credits) VALUES (:pseudo, :email, :password, :nom, :prenom, 20)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':pseudo', $pseudo, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
    $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);  // Ajouter le nom
    $stmt->bindParam(':prenom', $prenom, PDO::PARAM_STR);  // Ajouter le prénom

    if ($stmt->execute()) {
        // Récupérer l'ID de l'utilisateur nouvellement inséré
        $utilisateur_id = $pdo->lastInsertId();
    
        // Récupérer les informations de l'utilisateur
        $sql = "SELECT * FROM utilisateurs WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $utilisateur_id]);
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
    
        // Enregistrer les informations de l'utilisateur dans la session
        $_SESSION['utilisateur'] = [
            'id' => $utilisateur['id'],
            'pseudo' => $utilisateur['pseudo'],
            'credits' => $utilisateur['credits'],
        ];
    
        // Redirection vers la page "mesInformations.php"
        header('Location: mesInformations.php');
        ob_end_flush(); // Envoyer le contenu capturé
        exit;
    } else {
        echo "Une erreur est survenue lors de la création de votre compte.";
    }
}
?>

<main>

<section class="loginRegister d-flex justify-content-center mt-5">
    <div class="loginRegister-container">
        <h2 class="text-center">S'inscrire</h2>
        <p class="text-center">Faites un geste pour la planète, on s'occupe du reste. Profitez de <strong>20 crédits offerts</strong> en souscrivant dès aujourd'hui.</p>
        <form action="inscription.php" method="post">
            
            <!-- Pseudo -->
            <div class="form-group p-2">
                <label for="pseudo">Pseudo</label>
                <input type="text" id="pseudo" name="pseudo" class="form-control" required placeholder="Entrez votre pseudo">
            </div>

            <!-- Nom -->
            <div class="form-group p-2">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" class="form-control" required placeholder="Entrez votre nom">
            </div>

            <!-- Prénom -->
            <div class="form-group p-2">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" class="form-control" required placeholder="Entrez votre prénom">
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
