<?php
// graphiques.php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');
require_once __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;

// Connexion MongoDB
$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$creditsPlateforme = $mongoClient->creditsPlateforme;
$creditsCollection = $creditsPlateforme->credits;

// Récupérer les crédits depuis MongoDB (tous les crédits enregistrés)
$credits_par_jour = [];
$total_credits = 0;

try {
    $pipeline = [
        [
            '$group' => [
                '_id' => ['$dateToString' => ['format' => '%Y-%m-%d', 'date' => ['$dateFromString' => ['dateString' => '$date']]]],
                'total' => ['$sum' => '$montant']
            ]
        ],
        [
            '$sort' => ['_id' => 1]
        ]
    ];

    $result = $creditsCollection->aggregate($pipeline);

    foreach ($result as $day) {
        $credits_par_jour[] = [
            'date' => $day->_id,
            'credits' => $day->total
        ];
        $total_credits += $day->total;
    }
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des crédits: " . $e->getMessage());
}

// Récupérer le nombre total de trajets validés depuis le début
$trajets_par_jour = [];
$total_trajets_valides = 0;

try {
    // Requête SQL pour récupérer les trajets validés (statut 'validé')
    $stmt = $pdo->prepare("
        SELECT DATE(date_validation) AS date, COUNT(*) AS total
        FROM avis
        WHERE statut = 'validé'
        AND date_validation IS NOT NULL
        GROUP BY DATE(date_validation)
        ORDER BY DATE(date_validation) ASC;
    ");
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Ajout des trajets validés au tableau
        $trajets_par_jour[] = [
            'date' => $row['date'], // La date est déjà au format 'YYYY-MM-DD'
            'trajets' => (int) $row['total']
        ];

        // Calcul du total des trajets validés
        $total_trajets_valides += (int) $row['total'];
    }
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des trajets: " . $e->getMessage());
}

?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<h1>Statistiques des Crédits et Covoiturages</h1>

<section>
    <div class="stats-container">
        <h2>Nombre de Covoiturages Réalisés</h2>
        <div id="trajets-summary">
            <p id="total-trajets">Nombre total de trajets effectués depuis le début: <strong><?php echo $total_trajets_valides; ?></strong></p>
        </div>
        <canvas id="covoituragesChart"></canvas>
    </div>
</section>

<script>
// Trajets Data (pour afficher le graphique des trajets)
const ctx2 = document.getElementById('covoituragesChart').getContext('2d');
const trajetsData = <?php echo json_encode($trajets_par_jour); ?>;
console.log(trajetsData); // Vérification du contenu des données

new Chart(ctx2, {
    type: 'line',
    data: {
        labels: trajetsData.map(item => item.date),
        datasets: [{
            label: 'Covoiturages réalisés par jour',
            data: trajetsData.map(item => item.trajets),
            borderColor: 'rgb(54, 162, 235)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Nombre de trajets'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Date'
                }
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Nombre de trajets effectués par jour'
            }
        }
    }
});
</script>

<section>
    <div class="stats-container">
        <h2>Statistiques des Crédits</h2>
        <div class="total-credits">
            <h3>Total des crédits gagnés depuis le début : <?php echo $total_credits; ?> crédits</h3>
        </div>
        <canvas id="creditsChart"></canvas>
    </div>
</section>

<script>
// Crédits Data (pour afficher le graphique des crédits)
const ctx = document.getElementById('creditsChart').getContext('2d');
const creditsData = <?php echo json_encode($credits_par_jour); ?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels: creditsData.map(item => item.date),
        datasets: [{
            label: 'Crédits gagnés par jour',
            data: creditsData.map(item => item.credits),
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Nombre de crédits'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Date'
                }
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Évolution des crédits de la plateforme'
            }
        }
    }
});
</script>
