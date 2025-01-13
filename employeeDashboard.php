<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

// Vérification de la session
if (!isset($_SESSION['utilisateur']) || !isset($_SESSION['utilisateur']['type_acces']) || $_SESSION['utilisateur']['type_acces'] != 'employe') {
    header('Location: login.php');
    exit();
}

// Récupérer les avis à valider
$stmt = $pdo->query("SELECT * FROM avis WHERE status = 'pending'");
$avis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les covoiturages
$stmt = $pdo->query("SELECT * FROM covoiturages");
$covoiturages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $avisId = $_POST['avis_id'];
    $action = $_POST['action'];

    $stmt = $pdo->prepare("UPDATE avis SET status = ? WHERE id = ?");
    $stmt->execute([$action, $avisId]);

    header('Location: employeeDashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord Employé</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Tableau de bord Employé</h1>

    <h2>Avis à valider</h2>
    <ul>
        <?php foreach ($avis as $av): ?>
            <li>
                <?php echo htmlspecialchars($av['contenu']); ?>
                <form method="POST" action="employeeDashboard.php" style="display:inline;">
                    <input type="hidden" name="avis_id" value="<?php echo $av['id']; ?>">
                    <button type="submit" name="action" value="approved">Approuver</button>
                    <button type="submit" name="action" value="rejected">Refuser</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>

    <h2>Covoiturages</h2>
    <table>
        <tr>
            <th>Numéro de trajet</th>
            <th>Pseudo</th>
            <th>Email</th>
            <th>Ville de départ</th>
            <th>Ville d'arrivée</th>
        </tr>
        <?php foreach ($covoiturages as $covoiturage): ?>
            <tr>
                <td><?php echo htmlspecialchars($covoiturage['numero_trajet']); ?></td>
                <td><?php echo htmlspecialchars($covoiturage['pseudo']); ?></td>
                <td><?php echo htmlspecialchars($covoiturage['email']); ?></td>
                <td><?php echo htmlspecialchars($covoiturage['ville_depart']); ?></td>
                <td><?php echo htmlspecialchars($covoiturage['ville_arrivee']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
