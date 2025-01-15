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

// Récupérer les crédits par jour pour les 30 derniers jours
$credits_par_jour = [];
$total_credits = 0;
$date_debut = new DateTime('-30 days');
$date_fin = new DateTime();

try {
    $pipeline = [
        [
            '$match' => [
                'date' => [
                    '$gte' => $date_debut->format('Y-m-d H:i:s'),
                    '$lte' => $date_fin->format('Y-m-d H:i:s')
                ]
            ]
        ],
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

// Récupérer le nombre de trajets réalisés par jour
$trajets_par_jour = [];


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
    // Formatage des dates pour ne récupérer que la date sans l'heure
    $trajets_par_jour[] = [
        'date' => $row['date'], // La date est déjà au format 'YYYY-MM-DD'
        'trajets' => (int) $row['total']
    ];
}
} catch (Exception $e) {
error_log("Erreur lors de la récupération des trajets: " . $e->getMessage());
}

// Afficher les données du tableau $trajets_par_jour[]
var_dump($trajets_par_jour);

?>

<h1>Statistiques des Crédits et Covoiturages</h1>
<section>
    <div class="stats-container">
        <h2>Nombre de Covoiturages Réalisés</h2>
        <canvas id="covoituragesChart"></canvas>
    </div>
</section>

<script>
const ctx2 = document.getElementById('covoituragesChart').getContext('2d');
const trajetsData = <?php echo json_encode($trajets_par_jour); ?>;

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
                text: 'Nombre de trajets effectués par jour sur les 30 derniers jours'
            },
            datalabels: {
                display: true,
                color: 'black',
                anchor: 'end',
                align: 'end',
                formatter: function(value, context) {
                    return value;
                }
            }
        }
    }
});
</script>

<section>
    <div class="stats-container">
        <h2>Statistiques des Crédits</h2>
        <div class="total-credits">
            <h3>Total des crédits gagnés depuis le <?php echo $date_debut->format('Y-m-d'); ?> : <?php echo $total_credits; ?> crédits</h3>
        </div>
        <canvas id="creditsChart"></canvas>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
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
                text: 'Évolution des crédits de la plateforme sur les 30 derniers jours'
            },
            datalabels: {
                display: true,
                color: 'black',
                anchor: 'end',
                align: 'end',
                formatter: function(value, context) {
                    return value;
                }
            }
        }
    }
});
</script>
