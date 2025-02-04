
<?php
// Spécifiez le chemin de votre fichier log
$logFile = "logs.txt";

// Vérifiez si le fichier existe
if (file_exists($logFile)) {
    // Lire le contenu du fichier log
    $logs = file_get_contents($logFile);
} else {
    $logs = "Le fichier log n'a pas été trouvé.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs du site</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        pre {
            background-color: #f4f4f4;
            padding: 20px;
            border: 1px solid #ccc;
            white-space: pre-wrap; 
            word-wrap: break-word;
        }
    </style>
</head>
<body>

    <h1>Logs du site</h1>

    <h2>Voici les derniers logs :</h2>
    
    <!-- Affichage des logs dans un bloc <pre> pour préserver la mise en forme -->
    <pre><?php echo htmlspecialchars($logs); ?></pre>

</body>
</html>
