<?php
// manage.php
require_once('templates/header.php');
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";

// Traitement du formulaire de création de compte employé
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_employee'])) {
    // Logique d'ajout d'employé (identique à celle déjà présente dans ton code)
    // ...
}

// Traitement de la suspension/désuspension d'un utilisateur
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['suspend_user']) || isset($_POST['unsuspend_user']))) {
    // Logique de suspension/désuspension (identique à celle déjà présente dans ton code)
    // ...
}
?>

<h1>Gestion des Utilisateurs et Employés</h1>

<section>
    <div class="employee-form-container m-5">
        <h2>Créer un compte employé</h2>
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" action="manage.php">
            <input type="hidden" name="create_employee" value="1">
            <label for="pseudo">Pseudo:</label>
            <input type="text" id="pseudo" name="pseudo" required><br>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required><br>

            <label for="password">Mot de passe:</label>
            <input type="password" id="password" name="password" required><br>

            <label for="confirm_password">Confirmer le mot de passe:</label>
            <input type="password" id="confirm_password" name="confirm_password" required><br>

            <label for="nom">Nom:</label>
            <input type="text" id="nom" name="nom" required><br>

            <label for="prenom">Prénom:</label>
            <input type="text" id="prenom" name="prenom" required><br>

            <button type="submit">Créer</button>
        </form>
    </div>

    <div class="user-management-container m-5">
        <h2>Gestion des utilisateurs</h2>
        <!-- Formulaire pour suspension et désuspension des utilisateurs -->
        <form method="POST" action="manage.php">
            <label for="user_id">ID Utilisateur:</label>
            <input type="number" name="user_id" required><br>
            <button type="submit" name="suspend_user">Suspendre</button>
            <button type="submit" name="unsuspend_user">Désuspendre</button>
        </form>
    </div>
</section>
