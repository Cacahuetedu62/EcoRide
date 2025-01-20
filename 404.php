<?php
require_once('templates/header.php');
?>

<main>
    <h1>Oops, nous avons pris un mauvais virage !</h1>
    <div>Eh bien, il semble que vous ayez emprunté un trajet sans GPS... Pas de panique, vous pouvez retourner à l'accueil.</div>
    <a href="index.php" class="btn btn-primary mt-3">Retourner à l'accueil</a>
    <div class="mt-3">
        <h2>Vous pourriez être intéressé par :</h2>
        <ul>
            <li><a href="covoiturages.php">Les covoiturages</a></li>
            <li><a href="contact.php">Contact</a></li>
        </ul>
    </div>
</main>

<?php
require_once('templates/footer.php');
?>
