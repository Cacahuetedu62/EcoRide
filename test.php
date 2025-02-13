<?php
require_once('templates/header.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

    <div class="audit-container">
        <?php
        // Connexion à la base de données
        try {
            $host = 'localhost';
            $dbname = 'ecoride';
            $utilisateur = 'root';
            $pass = 'MattLi2024!!';
            $port = 3306;

            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $utilisateur, $pass);
            echo "<div class='audit-header'>✅ Connexion à la base de données réussie !</div>";
        } catch (PDOException $e) {
            die("<div class='audit-error'><h2>❌ Erreur de connexion :</h2> <p>" . $e->getMessage() . "</p></div>");
        }

        echo "<div class='row'>
            <div class='col-12'>
                <h3>🔍 Vérification des données et de l'intégrité des tables</h3>
                <p>Voici un résumé des vérifications effectuées sur la base de données. Chaque catégorie indique si les vérifications ont réussi ou non.</p>
            </div>
        </div>";

        echo "<div class='row m-4'>";

        // Vérification des tables
        echo "<div class='col-12 mb-3'>";
        echo "<div class='card audit-status audit-success'>";
        echo "<div class='card-body'>";
        echo "<h4>📋 Tables disponibles :</h4>";
        $tableList = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tableList as $table) {
            echo "<p>$table</p>";
        }
        echo "</div></div>";
        echo "</div>";

        // Vérification de l'intégrité des données
        echo "<div class='col-12 mb-3'>";
        echo "<div class='card audit-status audit-success'>";
        echo "<div class='card-body'>";
        echo "<h4>🛠 Vérification de l'intégrité des données :</h4>";
        $query = "SELECT r.id FROM reservations r LEFT JOIN utilisateurs u ON r.utilisateur_id = u.id WHERE u.id IS NULL";
        $invalidReservations = $pdo->query($query)->fetchAll();
        if (!empty($invalidReservations)) {
            echo "<p class='audit-error'>❌ Attention : Réservations invalides détectées.</p>";
        } else {
            echo "<p class='audit-success'>✅ Toutes les réservations sont valides.</p>";
        }
        echo "</div></div>";
        echo "</div>";

        // Vérification des colonnes obligatoires
        echo "<div class='col-12 mb-3'>";
        echo "<div class='card audit-status audit-success'>";
        echo "<div class='card-body'>";
        echo "<h4>🛠 Vérification des colonnes obligatoires :</h4>";
        $tableColumns = [
            'utilisateurs' => ['id', 'nom', 'email'],
            'trajets' => ['id', 'lieu_depart', 'lieu_arrive', 'date_depart'],
            'reservations' => ['id', 'utilisateur_id', 'trajet_id']
        ];

        $allColumnsOk = true;
        foreach ($tableColumns as $table => $columns) {
            $query = "SHOW COLUMNS FROM $table";
            $existingColumns = $pdo->query($query)->fetchAll(PDO::FETCH_COLUMN);

            foreach ($columns as $column) {
                if (!in_array($column, $existingColumns)) {
                    echo "<p class='audit-error'>❌ La colonne <b>$column</b> est absente de la table <b>$table</b>.</p>";
                    $allColumnsOk = false;
                }
            }
        }

        if ($allColumnsOk) {
            echo "<p class='audit-success'>✅ Toutes les colonnes sont présentes.</p>";
        }
        echo "</div></div>";
        echo "</div>";

        // Vérification des clés étrangères
        echo "<div class='col-12 mb-3'>";
        echo "<div class='card audit-status audit-success'>";
        echo "<div class='card-body'>";
        echo "<h4>🔗 Vérification des clés étrangères :</h4>";
        $foreignKeyChecks = [
            'reservations' => ['utilisateur_id' => 'utilisateurs', 'trajet_id' => 'trajets']
        ];

        $allKeysOk = true;
        foreach ($foreignKeyChecks as $table => $checks) {
            foreach ($checks as $column => $referencedTable) {
                $query = "SELECT $table.id FROM $table LEFT JOIN $referencedTable ON $table.$column = $referencedTable.id WHERE $referencedTable.id IS NULL";
                $orphans = $pdo->query($query)->fetchAll();

                if (!empty($orphans)) {
                    echo "<p class='audit-error'>❌ Clés étrangères orphelines détectées dans <b>$table</b> (colonne <b>$column</b>).</p>";
                    $allKeysOk = false;
                }
            }
        }

        if ($allKeysOk) {
            echo "<p class='audit-success'>✅ Toutes les clés étrangères sont valides.</p>";
        }
        echo "</div></div>";
        echo "</div>";

        // Vérification des valeurs NULL interdites
        echo "<div class='col-12 mb-3'>";
        echo "<div class='card audit-status audit-success'>";
        echo "<div class='card-body'>";
        echo "<h4>🚨 Vérification des valeurs NULL interdites :</h4>";
        $notNullChecks = [
            'utilisateurs' => ['nom', 'email'],
            'trajets' => ['lieu_depart', 'lieu_arrive', 'date_depart']
        ];

        $allNotNullOk = true;
        foreach ($notNullChecks as $table => $columns) {
            foreach ($columns as $column) {
                $query = "SELECT COUNT(*) FROM $table WHERE $column IS NULL";
                $stmt = $pdo->prepare($query);
                $stmt->execute();
                $count = $stmt->fetchColumn();

                if ($count > 0) {
                    echo "<p class='audit-error'>❌ Il y a <b>$count</b> enregistrements NULL dans la colonne <b>$column</b> de <b>$table</b>.</p>";
                    $allNotNullOk = false;
                }
            }
        }

        if ($allNotNullOk) {
            echo "<p class='audit-success'>✅ Aucune valeur NULL n'est présente dans les colonnes.</p>";
        }
        echo "</div></div>";
        echo "</div>";

        // Vérification des emails invalides
        echo "<div class='col-12 mb-3'>";
        echo "<div class='card audit-status audit-success'>";
        echo "<div class='card-body'>";
        echo "<h4>📧 Vérification des emails invalides :</h4>";
        $query = "SELECT id, email FROM utilisateurs WHERE email NOT LIKE '%@%.%'";
        $invalidEmails = $pdo->query($query)->fetchAll();

        if (!empty($invalidEmails)) {
            echo "<p class='audit-error'>❌ Certains emails sont invalides :</p><ul>";
            foreach ($invalidEmails as $row) {
                echo "<li>ID: {$row['id']}, Email: {$row['email']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p class='audit-success'>✅ Tous les emails sont valides.</p>";
        }
        echo "</div></div>";
        echo "</div>";

        try {
            // Vérification des index
            echo "<div class='col-12 mb-3'>";
            echo "<div class='card audit-status audit-success'>";
            echo "<div class='card-body'>";
            echo "<h3>🔍 Vérification des index sur les colonnes fréquemment utilisées :</h3>";

            $indexChecks = [
                'utilisateurs' => ['id', 'email'],
                'trajets' => ['id', 'lieu_depart', 'prix_personnes'],
                'reservations' => ['id', 'utilisateur_id', 'trajet_id']
            ];

            foreach ($indexChecks as $table => $columns) {
                foreach ($columns as $column) {
                    $stmt = $pdo->prepare("SHOW INDEX FROM $table WHERE Column_name = :column");
                    $stmt->execute(['column' => $column]);
                    $indexes = $stmt->fetchAll();

                    if (empty($indexes)) {
                        echo "<p class='audit-error'>❌ Pas d'index trouvé sur la colonne <b>$column</b> de la table <b>$table</b>.</p>";
                        echo "<p class='audit-suggestion'>💡 Suggestion: <code>CREATE INDEX idx_{$column} ON {$table}({$column});</code></p>";
                    } else {
                        echo "<p class='audit-success'>✅ Index trouvé sur la colonne <b>$column</b> de la table <b>$table</b>.</p>";
                    }
                }
            }
            echo "</div></div>";
            echo "</div>";

            // Analyse de la taille des tables
            echo "<div class='col-12 mb-3'>";
            echo "<div class='card audit-status audit-success'>";
            echo "<div class='card-body'>";
            echo "<h3>📊 Analyse de la taille des tables :</h3>";

            $stmt = $pdo->query("SELECT
                TABLE_NAME as table_name,
                ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) as size_MB
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = 'ecoride'
                AND TABLE_TYPE = 'BASE TABLE'");

            $tableSizes = $stmt->fetchAll();

            foreach ($tableSizes as $tableSize) {
                echo "<p>📌 La table <b>{$tableSize['table_name']}</b> occupe <b>{$tableSize['size_MB']} MB</b>.</p>";
            }
            echo "</div></div>";
            echo "</div>";

            // Information sur les trajets passés
            echo "<div class='col-12 mb-3'>";
            echo "<div class='card audit-status audit-success'>";
            echo "<div class='card-body'>";
            echo "<h3>📅 Information sur les trajets :</h3>";

            $stmt = $pdo->query("SELECT
                COUNT(*) as count,
                MIN(date_depart) as oldest_date,
                MAX(date_depart) as newest_date,
                COUNT(CASE WHEN date_depart < DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN 1 END) as old_trips
                FROM trajets
                WHERE date_depart < NOW()");

            $dateInfo = $stmt->fetch();

            if ($dateInfo['count'] > 0) {
                echo "<p class='audit-info'>ℹ️ Statistiques des trajets passés :</p>";
                echo "<ul>";
                echo "<li>Nombre total de trajets passés : <b>{$dateInfo['count']}</b></li>";
                echo "<li>Trajet le plus ancien : <b>{$dateInfo['oldest_date']}</b></li>";
                echo "<li>Dernier trajet terminé : <b>{$dateInfo['newest_date']}</b></li>";
                echo "</ul>";

                if ($dateInfo['old_trips'] > 0) {
                    echo "<p class='audit-warning'>💡 Suggestion d'archivage :</p>";
                    echo "<ul>";
                    echo "<li>{$dateInfo['old_trips']} trajets ont plus d'un mois</li>";
                    echo "<li>Considérez de les déplacer vers une table d'archive pour optimiser les performances</li>";
                    echo "</ul>";
                }
            }
            echo "</div></div>";
            echo "</div>";

            // Vérification des doublons d'emails
            echo "<div class='col-12 mb-3'>";
            echo "<div class='card audit-status audit-success'>";
            echo "<div class='card-body'>";
            echo "<h3>📧 Vérification des doublons d'emails dans la table 'utilisateurs' :</h3>";

            $stmt = $pdo->query("SELECT email, COUNT(*) as count
                FROM utilisateurs
                GROUP BY email
                HAVING COUNT(*) > 1");

            $duplicateEmails = $stmt->fetchAll();

            if (!empty($duplicateEmails)) {
                echo "<p class='audit-error'>❌ Des doublons d'emails ont été trouvés :</p><ul>";
                foreach ($duplicateEmails as $row) {
                    echo "<li>Email: {$row['email']} - Doublons: {$row['count']}</li>";
                }
                echo "</ul>";
                echo "<p class='audit-suggestion'>💡 Suggestion: <code>ALTER TABLE utilisateurs ADD CONSTRAINT unique_email UNIQUE (email);</code></p>";
            } else {
                echo "<p class='audit-success'>✅ Aucun doublon d'email trouvé.</p>";
            }
            echo "</div></div>";
            echo "</div>";

            // Suggestions de contraintes CHECK
            echo "<div class='col-12 mb-3'>";
            echo "<div class='card audit-status audit-success'>";
            echo "<div class='card-body'>";
            echo "<h3>🔒 Suggestions de contraintes CHECK :</h3>";

            echo "<p>Ces contraintes permettraient de garantir la validité des données :</p>";
            echo "<ul>";
            echo "<li>ALTER TABLE trajets ADD CONSTRAINT chk_prix CHECK (prix_personnes >= 0);<br>";
            echo "<small>→ Empêche les prix négatifs</small></li>";

            echo "<li>ALTER TABLE trajets ADD CONSTRAINT chk_places CHECK (nb_places >= 0);<br>";
            echo "<small>→ Assure que le nombre de places ne soit pas négatif</small></li>";

            echo "<li>ALTER TABLE reservations ADD CONSTRAINT chk_nb_personnes CHECK (nb_personnes > 0);<br>";
            echo "<small>→ Assure qu'une réservation concerne au moins une personne</small></li>";

            echo "<li>ALTER TABLE trajets ADD CONSTRAINT chk_dates CHECK (date_arrive >= date_depart);<br>";
            echo "<small>→ Assure que la date d'arrivée soit après la date de départ</small></li>";
            echo "</ul>";
            echo "</div></div>";
            echo "</div>";

            // Validation des données numériques
            echo "<div class='col-12 mb-3'>";
            echo "<div class='card audit-status audit-success'>";
            echo "<div class='card-body'>";
            echo "<h3>🔐 Validation des données numériques :</h3>";

            // Vérification des valeurs négatives
            $stmt = $pdo->query("SELECT id, prix_personnes, nb_places
                FROM trajets
                WHERE prix_personnes < 0");

            $invalidTrajets = $stmt->fetchAll();

            if (!empty($invalidTrajets)) {
                echo "<p class='audit-error'>❌ Trajets avec des prix négatifs :</p><ul>";
                foreach ($invalidTrajets as $trajet) {
                    echo "<li>ID: {$trajet['id']} - Prix: {$trajet['prix_personnes']}</li>";
                }
                echo "</ul>";
            } else {
                echo "<p class='audit-success'>✅ Tous les prix sont valides.</p>";
            }

            // Vérification des réservations
            $stmt = $pdo->query("SELECT id, nb_personnes
                FROM reservations
                WHERE nb_personnes <= 0");

            $invalidReservations = $stmt->fetchAll();

            if (!empty($invalidReservations)) {
                echo "<p class='audit-error'>❌ Réservations invalides :</p><ul>";
                foreach ($invalidReservations as $reservation) {
                    echo "<li>ID: {$reservation['id']} - Personnes: {$reservation['nb_personnes']}</li>";
                }
                echo "</ul>";
            } else {
                echo "<p class='audit-success'>✅ Toutes les réservations sont valides.</p>";
            }
            echo "</div></div>";
            echo "</div>";

        } catch (PDOException $e) {
            echo "<div class='audit-error'><h4>❌ Erreur SQL :</h4>";
            echo "<p><b>Message</b>: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><b>Code</b>: " . $e->getCode() . "</p>";
            echo "</div>";
        } catch (Exception $e) {
            echo "<div class='audit-error'><h4>❌ Erreur générale :</h4>";
            echo "<p><b>Message</b>: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><b>Code</b>: " . $e->getCode() . "</p>";
            echo "</div>";
        }
   

// Les scores de performance
$performance_scores = [95, 95, 98, 98, 93, 93, 93, 90, 94, 94, 88, 85, 96, 98, 98, 99, 88, 95, 94, 94, 91, 99, 94, 95];
$moyenne_performance = round(array_sum($performance_scores) / count($performance_scores));

// Les scores d'accessibilité
$accessibilite_scores = [100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 96, 100, 100, 100, 100, 96, 100, 100, 100, 100, 100];
$moyenne_accessibilite = round(array_sum($accessibilite_scores) / count($accessibilite_scores));

// Les scores de bonnes pratiques
$bonnes_pratiques_scores = [96, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100];
$moyenne_bonnes_pratiques = round(array_sum($bonnes_pratiques_scores) / count($bonnes_pratiques_scores));

// Les scores de référencement
$referencement_scores = [100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100];
$moyenne_referencement = round(array_sum($referencement_scores) / count($referencement_scores));
?>

<div class="col-12 mb-3">
    <div class="card audit-status audit-success">
        <div class="card-body">
            <h4>Audit Lighthouse :</h4>
            <p>Voici les résultats moyens de l'audit Lighthouse de votre site.</p>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Critères</th>
                        <th>Score Moyen</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>🚀 Performance</td>
                        <td><?= $moyenne_performance ?></td>
                    </tr>
                    <tr>
                        <td>🌐 Accessibilité</td>
                        <td><?= $moyenne_accessibilite ?></td>
                    </tr>
                    <tr>
                        <td>🛠 Bonnes pratiques</td>
                        <td><?= $moyenne_bonnes_pratiques ?></td>
                    </tr>
                    <tr>
                        <td>🔍 Référencement naturel</td>
                        <td><?= $moyenne_referencement ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-4">
               <h5>📈 Axes d'amélioration identifiés :</h5>
               <div class="alert alert-info">
                   <div class="recommendations">
                       <strong>Optimisation des images — Économie potentielle : 57 Kio</strong>
                       <ul class="list-unstyled ms-3 mb-3">
                           <li>Convertir les images en format WebP</li>
                           <li>Redimensionner correctement les images selon leur utilisation</li>
                       </ul>

                       <strong>Optimisation du code — Économie potentielle : 239 Kio de CSS</strong>
                       <ul class="list-unstyled ms-3 mb-3">
                           <li>Activer la compression de texte (gain potentiel : 57 Kio)</li>
                           <li>Réduire les ressources CSS inutilisées</li>
                           <li>Réduire les ressources JavaScript inutilisées (gain potentiel : 1497 Kio)</li>
                       </ul>

                       <strong>Performance</strong>
                       <ul class="list-unstyled ms-3 mb-3">
                           <li>Éliminer les ressources bloquant le rendu (gain : 260 ms)</li>
                           <li>Mettre en place des règles de cache efficaces pour les éléments statiques</li>
                           <li>Éviter l'utilisation d'ancien code JavaScript dans les navigateurs récents</li>
                       </ul>

                       <strong>Accessibilité</strong>
                       <ul class="list-unstyled ms-3 mb-3">
                           <li>Améliorer le contraste des badges pour une meilleure lisibilité</li>
                       </ul>
                   </div>
               </div>
           </div>
       </div>
   </div>
</div>

<div class="col-12 mb-3">
   <div class="card audit-status audit-success">
       <div class="card-body">
           <h4>✅ Tests Manuels Effectués :</h4>
           <p>Résultats des tests fonctionnels sur les différents rôles utilisateurs.</p>

           <div class="mt-4">
               <div class="alert alert-success">
                   <div class="test-results">
                       <strong>PASSAGER</strong>
                       <ul class="list-unstyled ms-3 mb-3">
                           <li>Trajet annulé par passager ✓</li>
                           <li>Chercher un trajet qui n'est pas formaté pareil ✓</li>
                           <li>Voit le trajet = en_cours ✓</li>
                           <li>Reçoit le mail du trajet fini pour donner son avis ✓</li>
                           <li>Donne son avis sur la page avis.php ✓</li>
                           <li>Son avis est disponible dans la page commentaires.php ✓</li>
                           <li>Menu en fonction du statut ✓</li>
                       </ul>

                       <strong>CONDUCTEUR</strong>
                       <ul class="list-unstyled ms-3 mb-3">
                           <li>Ne peut pas annuler de trajet non réservés ✓</li>
                           <li>Trajet réservé peut être annulé ✓</li>
                           <li>Annulation du trajet = trajet supprimé ✓</li>
                           <li>Lancer le trajet = en_cours + historique mis à jour ✓</li>
                           <li>Terminé trajet = Trajet dans historique ✓</li>
                           <li>Propose un trajet ✓</li>
                           <li>Menu en fonction du statut ✓</li>
                       </ul>

                       <strong>EMPLOYÉ</strong>
                       <ul class="list-unstyled ms-3 mb-3">
                           <li>Reçoit les commentaires dans son dashboard ✓</li>
                           <li>Statut commentaires 'en attente' 'validé' 'rejeté' ✓</li>
                           <li>Validation du commentaire :</li>
                           <li class="ms-3">- Insertion dans la table "avis" ✓</li>
                           <li class="ms-3">- Les crédits sont déduits du passager ✓</li>
                           <li class="ms-3">- Les crédits sont ajoutés au conducteur ✓</li>
                           <li class="ms-3">- Les crédits sont ajoutés à la base Mono (commission) ✓</li>
                           <li>Menu en fonction du statut ✓</li>
                           <li>Rejet du commentaire :</li>
                           <li class="ms-3">- Mail reçu ✓</li>
                           <li class="ms-3">- Personne ne reçoit de crédit avant la résolution ✓</li>
                       </ul>

                       <strong>UTILISATEUR (Tous confondus)</strong>
                       <ul class="list-unstyled ms-3 mb-3">
                           <li>Changer sa photo de profil + ses informations ✓</li>
                           <li>Informations.php, le code postal donne la ville ✓</li>
                           <li>Ajouter / Supprimer un véhicule ✓</li>
                           <li>Ajout de 20 crédits automatiques à l'inscription ✓</li>
                       </ul>

                       <strong>ADMIN</strong>
                       <ul class="list-unstyled ms-3 mb-3">
                           <li>Crédit ajouté par MonGo ✓</li>
                           <li>Graphiques crédit à jour ✓</li>
                           <li>Graphique trajets effectués ✓</li>
                       </ul>
                   </div>
               </div>
           </div>
       </div>
   </div>
</div>

