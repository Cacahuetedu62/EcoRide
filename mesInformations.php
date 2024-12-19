<?php
require_once('lib/pdo.php');
require_once('lib/config.php');

$utilisateur_id = 21;

// Requête pour récupérer les informations de l'utilisateur
$sql = "SELECT * FROM utilisateurs WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $utilisateur_id, PDO::PARAM_INT);
$stmt->execute();

// Récupérer les données
$utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

if ($utilisateur) {
    // Les informations de l'utilisateur sont récupérées
} else {
    echo "Aucun utilisateur trouvé.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sécuriser les données envoyées
    $pseudo = htmlspecialchars($_POST['pseudo']);
    $nom = htmlspecialchars($_POST['nom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $adresse = htmlspecialchars($_POST['adresse']);
    $code_postal = htmlspecialchars($_POST['code_postal']);
    $ville = htmlspecialchars($_POST['ville']);
    $credits = (int) $_POST['credits'];
    $role = htmlspecialchars($_POST['role']);

    // Ajouter des crédits si spécifié
    $ajouter_credits = isset($_POST['ajouter_credits']) ? (int) $_POST['ajouter_credits'] : 0;

    if ($ajouter_credits > 0) {
        $credits += $ajouter_credits; // Ajouter les crédits au solde existant
    }

    // Vérification du champ photo (si une photo a été téléchargée)
    $photo = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $photo_dir = 'uploads/photos/'; // Dossier où les photos seront stockées
        $photo_file = $photo_dir . basename($_FILES['photo']['name']);
        $photo_tmp = $_FILES['photo']['tmp_name'];
        
        // Vérifier que le fichier est une image
        $image_info = getimagesize($photo_tmp);
        if ($image_info !== false) {
            if (move_uploaded_file($photo_tmp, $photo_file)) {
                $photo = $photo_file; // Stocker le chemin de la photo téléchargée
            } else {
                echo "Erreur lors du téléchargement de la photo.";
            }
        } else {
            echo "Le fichier téléchargé n'est pas une image.";
        }
    }

    // Validation des données
    if (empty($pseudo) || empty($nom) || empty($prenom) || empty($email) || empty($role)) {
        echo "Tous les champs doivent être remplis.";
    } elseif (!$email) {
        echo "L'adresse e-mail n'est pas valide.";
    } else {
        // Mise à jour des informations dans la base de données
        $sql = "UPDATE utilisateurs SET pseudo = ?, nom = ?, prenom = ?, email = ?, adresse = ?, code_postal = ?, ville = ?, credits = ?, role = ?, photo = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $pseudo);
        $stmt->bindParam(2, $nom);
        $stmt->bindParam(3, $prenom);
        $stmt->bindParam(4, $email);
        $stmt->bindParam(5, $adresse);
        $stmt->bindParam(6, $code_postal);
        $stmt->bindParam(7, $ville);
        $stmt->bindParam(8, $credits);
        $stmt->bindParam(9, $role);
        $stmt->bindParam(10, $photo);
        $stmt->bindParam(11, $utilisateur_id);

        if ($stmt->execute()) {
            echo "Informations mises à jour avec succès.";
        } else {
            echo "Erreur lors de la mise à jour des informations.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <meta name="description" content="EcoRide, quand économie rime avec écologie ! le covoiturage éléctrique, découvrez le covoiturage électrique pour des trajets plus verts et économiques.">
    <title>EcoRide | Covoiturage écologique et éléctrique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
</head>

<main class="form-page">
    <section class="custom-section2">
        <div class="custom-form-container">
            <h4>Mes informations</h4>
            <form action="mesInformations.php" method="post" id="form-info" enctype="multipart/form-data">

                <!-- Pseudo -->
                <label for="pseudo">Pseudo</label>
                <input type="text" id="pseudo" name="pseudo" value="<?php echo htmlspecialchars($utilisateur['pseudo']); ?>" disabled>

                <!-- Nom -->
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($utilisateur['nom']); ?>" disabled>

                <!-- Prénom -->
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($utilisateur['prenom']); ?>" disabled>

                <!-- Téléphone -->
                <label for="telephone">Téléphone</label>
                <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($utilisateur['telephone']); ?>" pattern="^(\+33|0)[1-9](?:[ .-]?[0-9]{2}){4}$" placeholder="Ex : 0612345678" disabled>

                <!-- Date de naissance -->
                <label for="date_naissance">Date de naissance</label>
                <input type="date" id="date_naissance" name="date_naissance" value="<?php echo htmlspecialchars($utilisateur['date_naissance']); ?>" disabled>


                <!-- Adresse mail -->
                <label for="email">Adresse mail</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($utilisateur['email']); ?>" disabled>

                <!-- Crédits -->
                <label for="credits">Mes crédits</label>
                <input type="text" id="credits" name="credits" value="<?php echo htmlspecialchars($utilisateur['credits']); ?>" disabled>

                <!-- Ajouter des crédits -->
                <label for="ajouter_credits">Ajouter des crédits</label>
                <input type="number" id="ajouter_credits" name="ajouter_credits" min="1" step="1" value="" placeholder="Entrez le nombre de crédits à ajouter" disabled>

                <!-- Photo de profil -->
                <label for="photo">Ajouter une photo de profil</label>
                <input type="file" id="photo" name="photo" accept="image/*">

                <!-- Adresse -->
                <label for="adresse">Adresse</label>
                <input type="text" id="adresse" name="adresse" value="<?php echo $utilisateur['adresse']; ?>" disabled>

                <!-- Code Postal -->
                <label for="code_postal">Code Postal</label>
                <input type="text" id="code_postal" name="code_postal" value="<?php echo htmlspecialchars($utilisateur['code_postal']); ?>" disabled>

                <!-- Ville -->
                <label for="ville">Ville</label>
                <input type="text" id="ville" name="ville" value="<?php echo htmlspecialchars($utilisateur['ville']); ?>" disabled>

                <!-- Rôle (avec liste déroulante) -->
                <label for="role">Je suis</label>
                <select id="role" name="role" disabled>
                    <option value="passager" <?php echo ($utilisateur['role'] == 'passager') ? 'selected' : ''; ?>>Passager</option>
                    <option value="chauffeur" <?php echo ($utilisateur['role'] == 'chauffeur') ? 'selected' : ''; ?>>Chauffeur</option>
                    <option value="passager_chauffeur" <?php echo ($utilisateur['role'] == 'passager_chauffeur') ? 'selected' : ''; ?>>Passager/Chauffeur</option>
                </select>
                <p>Si vous êtes chauffeur ou passager/chuaffeur nous vous invitons à renseigner votre véhicule dans l'onglet "Mes véhicules" sur votre gauche</p>

                <!-- Bouton "Modifier mes informations" -->
                <button class="buttonVert m-2" type="button" id="edit-btn" onclick="editForm()">Modifier mes informations</button>

                <!-- Bouton "Valider" -->
                <button type="submit" id="submit-btn" style="display:none;">Valider les informations modifiées</button>
            </form>
        </div>
    </section>
</main>

<script>
    // Fonction pour rendre les champs modifiables
    function editForm() {
        // Rendre les champs modifiables
        document.getElementById('pseudo').disabled = false;
        document.getElementById('nom').disabled = false;
        document.getElementById('prenom').disabled = false;
        document.getElementById('email').disabled = false;
        document.getElementById('credits').disabled = false;
        document.getElementById('ajouter_credits').disabled = false;
        document.getElementById('adresse').disabled = false;
        document.getElementById('code_postal').disabled = false;
        document.getElementById('ville').disabled = false;
        document.getElementById('role').disabled = false;
        document.getElementById('telephone').disabled = false; 
        document.getElementById('date_naissance').disabled = false; 

        // Masquer le bouton "Modifier" et afficher le bouton "Valider"
        document.getElementById('edit-btn').style.display = 'none';
        document.getElementById('submit-btn').style.display = 'inline-block';
    }
</script>