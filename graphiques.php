<?php
// graphiques.php
require_once('templates/header.php');
require_once __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;

// Fonction de nettoyage des données
function cleanData($data) {
   if (is_array($data)) {
       return array_map('cleanData', $data);
   }
   return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Fonction de validation des nombres
function validateNumber($number, $type = 'int') {
   if ($type === 'int') {
       return filter_var($number, FILTER_VALIDATE_INT) !== false ? (int)$number : 0;
   }
   return filter_var($number, FILTER_VALIDATE_FLOAT) !== false ? (float)$number : 0.0;
}

// Connexion MongoDB
try {
   $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
   $creditsPlateforme = $mongoClient->creditsPlateforme;
   $creditsCollection = $creditsPlateforme->credits;
} catch (Exception $e) {
   error_log("Erreur de connexion MongoDB: " . $e->getMessage());
   $creditsPlateforme = null;
   $creditsCollection = null;
}

// Récupérer les crédits depuis MongoDB
$credits_par_jour = [];
$total_credits = 0;

try {
   if ($creditsCollection) {
       $pipeline = [
           [
               '$group' => [
                   '_id' => ['$dateToString' => ['format' => '%Y-%m-%d', 'date' => ['$dateFromString' => ['dateString' => '$date']]]],
                   'total' => ['$sum' => '$montant']
               ]
           ],
           ['$sort' => ['_id' => 1]]
       ];

       $result = $creditsCollection->aggregate($pipeline);

       foreach ($result as $day) {
           $credits_par_jour[] = [
               'date' => cleanData($day->_id),
               'credits' => validateNumber($day->total, 'float')
           ];
           $total_credits += validateNumber($day->total, 'float');
       }
   }
} catch (Exception $e) {
   error_log("Erreur lors de la récupération des crédits: " . $e->getMessage());
}

// Récupérer les trajets
$trajets_par_jour = [];
$total_trajets_valides = 0;

try {
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
       $trajets_par_jour[] = [
           'date' => cleanData($row['date']),
           'trajets' => validateNumber($row['total'])
       ];
       $total_trajets_valides += validateNumber($row['total']);
   }
} catch (Exception $e) {
   error_log("Erreur lors de la récupération des trajets: " . $e->getMessage());
}

?>

<div class="btn-container">
   <a href="adminDashboard.php" class="btn-retour">⬅ Retour au tableau de bord</a>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script>
    Chart.register(ChartDataLabels);
</script>

<h1>Statistiques des Crédits et Covoiturages</h1>

<body class="stats-page">
<section>
   <div class="stats-container">
       <h2>Nombre de Covoiturages Réalisés</h2>
       <div class="stats-info-container">
           <div class="stats-info-text">Nombre total de trajets effectués depuis le début</div>
           <div class="stats-info-number"><?php echo number_format($total_trajets_valides, 0, ',', ' '); ?></div>
       </div>
       <canvas id="covoituragesChart"></canvas>
   </div>
</section>

<script>
// Fonction de validation des données pour les graphiques
function validateChartData(data) {
   return data.filter(item => {
       return item && 
              typeof item.date === 'string' && 
              !isNaN(new Date(item.date)) &&
              ((typeof item.trajets === 'number' && !isNaN(item.trajets)) ||
               (typeof item.credits === 'number' && !isNaN(item.credits)));
   });
}

// Fonction de création sécurisée des graphiques
function createChart(context, config) {
   try {
       if (!context || !context.canvas) {
           throw new Error('Contexte du canvas invalide');
       }
       return new Chart(context, config);
   } catch (error) {
       console.error('Erreur lors de la création du graphique:', error);
       const errorDiv = document.createElement('div');
       errorDiv.className = 'chart-error';
       errorDiv.textContent = 'Erreur lors du chargement du graphique';
       context.canvas.parentNode.appendChild(errorDiv);
       return null;
   }
}

// Configuration et création du graphique des trajets
const ctx2 = document.getElementById('covoituragesChart')?.getContext('2d');
const trajetsData = validateChartData(<?php echo json_encode($trajets_par_jour, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);

if (ctx2) {
    createChart(ctx2, {
        type: 'line',
        data: {
            labels: trajetsData.map(item => item.date),
            datasets: [{
                data: trajetsData.map(item => item.trajets),
                borderColor: 'rgb(54, 162, 235)',
                tension: 0.1,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 500,  // Changé à 500
                    ticks: {
                        stepSize: 50  // Graduation tous les 50
                    }
                },
                x: {}
            },
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: false
                },
                datalabels: {
                    color: '#000',
                    anchor: 'end',
                    align: 'top',
                    offset: 4,
                    font: {
                        weight: 'bold'
                    },
                    formatter: function(value) {
                        return value.toLocaleString() + ' trajets';
                    }
                }
            }
        }
    });
}
</script>

<section>
   <div class="stats-container">
       <h2>Statistiques des Crédits</h2>
       <div class="stats-info-container">
           <div class="stats-info-text">Total des crédits gagnés depuis le début</div>
           <div class="stats-info-number"><?php echo number_format($total_credits, 0, ',', ' '); ?> crédits</div>
       </div>
       <canvas id="creditsChart"></canvas>
   </div>
</section>
</body>

<script>
// Configuration et création du graphique des crédits
const ctx = document.getElementById('creditsChart')?.getContext('2d');
const creditsData = validateChartData(<?php echo json_encode($credits_par_jour, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);

if (ctx) {
    createChart(ctx, {
        type: 'line',
        data: {
            labels: creditsData.map(item => item.date),
            datasets: [{
                data: creditsData.map(item => item.credits),
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 500,  // Changé à 500
                    ticks: {
                        stepSize: 50  // Graduation tous les 50
                    }
                },
                x: {}
            },
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: false
                },
                datalabels: {
                    color: '#000',
                    anchor: 'end',
                    align: 'top',
                    offset: 4,
                    font: {
                        weight: 'bold'
                    },
                    formatter: function(value) {
                        return value.toLocaleString() + ' crédits';
                    }
                }
            }
        }
    });
}
</script>