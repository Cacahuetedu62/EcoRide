<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');

// Vérifier si l'utilisateur est connecté et est un administrateur
if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['role']) && $_SESSION['utilisateur']['role'] === 'admin') {
    $utilisateur_id = $_SESSION['utilisateur']['id'];
} else {
    // L'utilisateur n'est pas connecté ou n'est pas un administrateur
    echo "Accès non autorisé.";
    exit;
}

// Récupérer les avis en attente de validation
$sql = "SELECT * FROM avis WHERE statut = 'en attente'";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$avis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Afficher les avis en attente de validation
echo "<h2>Avis en attente de validation :</h2>";
echo "<table border='1'>
        <tr>
            <th>ID</th>
            <th>Commentaires</th>
            <th>Note</th>
            <th>Utilisateur ID</th>
            <th>Trajet ID</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>";

foreach ($avis as $row) {
    echo "<tr>
            <td>{$row['id']}</td>
            <td>{$row['commentaires']}</td>
            <td>{$row['note']}</td>
            <td>{$row['utilisateur_id']}</td>
            <td>{$row['trajet_id']}</td>
            <td>{$row['statut']}</td>
            <td>
                <form method='POST' action='admin_avis.php'>
                    <input type='hidden' name='avis_id' value='{$row['id']}'>
                    <button type='submit' name='action' value='valider'>Valider</button>
                    <button type='submit' name='action' value='rejeter'>Rejeter</button>
                </form>
            </td>
          </tr>";
}

echo "</table>";

// Traiter la validation ou le rejet des avis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['avis_id']) && isset($_POST['action'])) {
        $avis_id = (int)$_POST['avis_id'];
        $action = $_POST['action'];

        if ($action === 'valider') {
            $new_statut = 'validé';
        } elseif ($action === 'rejeter') {
            $new_statut = 'rejeté';
        }

        // Mettre à jour le statut de l'avis
        $sqlUpdate = "UPDATE avis SET statut = :statut WHERE id = :avis_id";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindParam(':statut', $new_statut, PDO::PARAM_STR);
        $stmtUpdate->bindParam(':avis_id', $avis_id, PDO::PARAM_INT);

        try {
            $stmtUpdate->execute();
            echo "Le statut de l'avis a été mis à jour avec succès.";
        } catch (PDOException $e) {
            echo "Erreur lors de la mise à jour du statut de l'avis : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}

require_once('templates/footer.php');
?>
