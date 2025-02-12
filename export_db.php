<?php
require_once('templates/header.php');

// Requête pour récupérer toutes les tables de la base de données
$tablesQuery = "SHOW TABLES";
$tablesStmt = $pdo->query($tablesQuery);
$tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

// Initialisation de la structure de la base de données
$databaseStructure = [];

// Parcourir chaque table pour récupérer les colonnes et les clés étrangères
foreach ($tables as $table) {
    // Récupérer les colonnes de la table
    $columnsQuery = "SHOW COLUMNS FROM `$table`";
    $columnsStmt = $pdo->query($columnsQuery);
    $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les clés étrangères de la table
    $foreignKeysQuery = "
        SELECT
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE
            TABLE_SCHEMA = '" . DB_NAME . "' AND
            TABLE_NAME = '$table' AND
            REFERENCED_TABLE_NAME IS NOT NULL
    ";
    $foreignKeysStmt = $pdo->query($foreignKeysQuery);
    $foreignKeys = $foreignKeysStmt->fetchAll(PDO::FETCH_ASSOC);

    // Ajouter les informations à la structure de la base de données
    $databaseStructure[$table] = [
        'columns' => $columns,
        'foreign_keys' => $foreignKeys
    ];
}
?>

<div class="d-flex flex-column align-items-center">
    <div class="card w-50">
        <div class="text-center">
            <img class="image-doc w-50" src="images/documents-6183373_1280.png" alt="classeurs">
        </div>
    </div>
    <div class="w-100 text-center">
        <h1 class="card m-1 w-50 p-4 mx-auto">Structure de la base de données</h1>
        <div class="card m-1 w-50 p-4 mx-auto">
            <p class="text-center">
                Ce script PHP a été conçu pour fournir une vue d'ensemble de la structure de notre base de données MySQL,
                affichée dans un format HTML simple avec Bootstrap pour une meilleure lisibilité.
            </p>
            <ul class="list-group">
                <li class="list-group-item">
                    <strong>Connexion à la base de données :</strong> Le script se connecte à la base de données en utilisant les identifiants définis dans le fichier <code>config.php</code>.
                </li>
                <li class="list-group-item">
                    <strong>Récupération des tables :</strong> La requête <code>SHOW TABLES</code> récupère toutes les tables de la base de données.
                </li>
                <li class="list-group-item">
                    <strong>Récupération des colonnes :</strong> La requête <code>SHOW COLUMNS FROM \$table</code> récupère les colonnes de chaque table.
                </li>
                <li class="list-group-item">
                    <strong>Récupération des clés étrangères :</strong> La requête sur <code>INFORMATION_SCHEMA.KEY_COLUMN_USAGE</code> récupère les clés étrangères de chaque table.
                </li>
            </ul>
        </div>

        <?php foreach ($databaseStructure as $table => $structure): ?>
            <div class="card m-1 w-50 p-4 mx-auto">
                <div class="card-header">
                    <h2 class="mb-0"><?= htmlspecialchars($table) ?></h2>
                </div>
                <div class="card-body">
                    <h3>Colonnes</h3>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Type</th>
                                <th>Null</th>
                                <th>Clé</th>
                                <th>Extra</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($structure['columns'] as $column): ?>
                                <tr>
                                    <td><?= htmlspecialchars($column['Field']) ?></td>
                                    <td><?= htmlspecialchars($column['Type']) ?></td>
                                    <td><?= htmlspecialchars($column['Null']) ?></td>
                                    <td><?= htmlspecialchars($column['Key']) ?></td>
                                    <td><?= htmlspecialchars($column['Extra']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <h3>Clés étrangères</h3>
                    <?php if (!empty($structure['foreign_keys'])): ?>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Nom de la contrainte</th>
                                    <th>Colonne</th>
                                    <th>Table référencée</th>
                                    <th>Colonne référencée</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($structure['foreign_keys'] as $foreignKey): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($foreignKey['CONSTRAINT_NAME']) ?></td>
                                        <td><?= htmlspecialchars($foreignKey['COLUMN_NAME']) ?></td>
                                        <td><?= htmlspecialchars($foreignKey['REFERENCED_TABLE_NAME']) ?></td>
                                        <td><?= htmlspecialchars($foreignKey['REFERENCED_COLUMN_NAME']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Aucune clé étrangère.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
