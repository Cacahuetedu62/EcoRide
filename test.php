
<?php


// Créez un fichier test_db.php dans votre projet
try {
    // Paramètres de connexion
    $host = 'db';
    $dbname = 'ecoride';
    $user = 'ecoride_user';
    $pass = 'secure_password';
    $port = 3306;

    // Tentative de connexion
    $dsn = "mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4";
    
    // Affichage des informations de connexion
    echo "Tentative de connexion avec :<br>";
    echo "Hôte : $host<br>";
    echo "Base de données : $dbname<br>";
    echo "Utilisateur : $user<br>";
    echo "Port : $port<br>";

    // Connexion PDO
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Vérification des bases de données
    $databases = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Bases de données disponibles : " . implode(', ', $databases) . "<br>";

    // Sélection de la base de données
    $pdo->exec("USE ecoride");

    // Vérification des tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables disponibles : " . implode(', ', $tables) . "<br>";

} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage() . "<br>";
    // Afficher la trace de l'erreur
    print_r($e->getTrace());
}



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
