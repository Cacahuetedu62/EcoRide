<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');
require_once __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Vérification de la session administrateur
if (!isset($_SESSION['utilisateur']) || !isset($_SESSION['utilisateur']['type_acces'])
    || $_SESSION['utilisateur']['type_acces'] != 'administrateur') {
    // Rediriger ou afficher un message d'erreur si l'utilisateur n'est pas un administrateur
}

// Connexion MongoDB
try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $creditsPlateforme = $mongoClient->creditsPlateforme;
    $creditsCollection = $creditsPlateforme->credits;
} catch (Exception $e) {
    error_log("MongoDB Connection Error: " . $e->getMessage());
}

// Récupérer les crédits par jour pour les 30 derniers jours
$credits_par_jour = [];
$total_credits = 0; // Variable pour stocker le total des crédits
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
        $total_credits += $day->total; // Mettre à jour le total des crédits
    }
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des crédits: " . $e->getMessage());
}

$message = "";

// Traitement du formulaire de création de compte employé
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_employee'])) {
    $pseudo = isset($_POST['pseudo']) ? htmlspecialchars($_POST['pseudo']) : '';
    $email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $nom = isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : '';
    $prenom = isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : '';

    // Vérification si les champs nom et prénom sont remplis
    if (empty($nom) || empty($prenom)) {
        $message = "Le nom et le prénom sont obligatoires.";
    } elseif ($password !== $confirm_password) {
        $message = "Les mots de passe ne correspondent pas.";
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,}$/", $password)) {
        $message = "Le mot de passe n'est pas sécurisé.";
    } else {
        // Hacher le mot de passe
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Vérifier si l'email existe déjà dans la base de données
        $sql = "SELECT * FROM utilisateurs WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $existing_email = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_email) {
            $message = "Un utilisateur avec cet e-mail existe déjà. Veuillez choisir un autre e-mail.";
        } else {
            // Vérifier si le pseudo existe déjà dans la base de données
            $sql = "SELECT * FROM utilisateurs WHERE pseudo = :pseudo";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':pseudo', $pseudo, PDO::PARAM_STR);
            $stmt->execute();
            $existing_pseudo = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_pseudo) {
                $message = "Un utilisateur avec ce pseudo existe déjà. Veuillez choisir un autre pseudo.";
            } else {
                // Insérer l'employé dans la base de données
                $sql = "INSERT INTO utilisateurs (pseudo, email, password, nom, prenom, type_acces) VALUES (:pseudo, :email, :password, :nom, :prenom, 'employe')";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':pseudo', $pseudo, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
                $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
                $stmt->bindParam(':prenom', $prenom, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    $message = "L'employé a été créé avec succès.";
                } else {
                    $message = "Erreur lors de l'insertion de l'employé.";
                }
            }
        }
    }
}

// Traitement de la suspension/désuspension d'un utilisateur
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['suspend_user']) || isset($_POST['unsuspend_user']))) {
    $user_id = $_POST['user_id'];
    $action = isset($_POST['suspend_user']) ? 'suspend' : 'unsuspend';

    // Mettre à jour la colonne "suspendu" dans la table utilisateurs
    $sql = "UPDATE utilisateurs SET suspendu = :suspendu WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':suspendu', $action == 'suspend' ? 1 : 0, PDO::PARAM_INT);

    if ($stmt->execute()) {
        // Récupérer l'email de l'utilisateur suspendu/désuspendu
        $sql = "SELECT email FROM utilisateurs WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user_email = $stmt->fetch(PDO::FETCH_ASSOC)['email'];

        // Envoyer un email de notification
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'testing.projets.siteweb@gmail.com'; // Adresse email d'expéditeur
            $mail->Password = 'sljw jlop qtyy mqae'; // Mot de passe d'application Gmail
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Destinataire
            $mail->setFrom('rogez.aurore01@gmail.com', 'EcoRide');
            $mail->addAddress('rogez.aurore01@gmail.com'); // Envoi à ton adresse email pour les tests

            $mail->isHTML(true);
            $mail->Subject = $action == 'suspend' ? 'Votre compte a été suspendu' : 'Votre compte a été désuspendu';
            $mail->Body    = $action == 'suspend' ? "<html>
<head>
    <title>Notification de Suspension de Compte</title>
    <meta charset='UTF-8'>
</head>
<body>
    <p>Bonjour,</p>
    
    <p>Nous vous informons que votre compte a été suspendu pour les raisons suivantes :</p>
    <ul>
        <li><strong>Violation des Conditions d'Utilisation :
        </strong> Votre utilisation du service ne respecte pas nos conditions.</li>
        <li><strong>Comportement Inapproprié :</strong> Des comportements inappropriés ont été signalés lors de vos interactions sur la plateforme.</li>
        <li><strong>Paiement Non Réglé :</strong> Votre compte présente un solde impayé qui nécessite une régularisation.</li>
    </ul>

    <p>Pour toute question ou clarification concernant cette suspension, n'hésitez pas à contacter notre service client à l'adresse suivante : contact@ecoride.fr</p>

    <p>Nous vous remercions de votre compréhension.</p>

    <p>Cordialement,<br>L'équipe d'Ecoride</p>
</body>
</html>" 
: 
"<html>
<head>
    <title>Notification de Réactivation de Compte</title>
    <meta charset='UTF-8'>
</head>
<body>
    <p>Bonjour,</p>
    
    <p>Nous avons le plaisir de vous informer que votre compte a été réactivé avec succès ! Vous pouvez à nouveau accéder à tous nos services.</p>

    <p>Nous vous remercions de votre patience et de votre compréhension durant cette période. Nous sommes heureux de vous retrouver parmi nous.</p>

    <p>Nous vous souhaitons une excellente expérience avec Ecoride !</p>

    <p>Cordialement,<br>L'équipe d'Ecoride</p>
</body>
</html>";

            $mail->send();
            $message = "L'utilisateur a été " . ($action == 'suspend' ? 'suspendu' : 'désuspendu') . " et un email de notification a été envoyé.";
        } catch (Exception $e) {
            $message = "Erreur lors de l'envoi de l'email: " . $mail->ErrorInfo;
        }
    } else {
        $message = "Erreur lors de la " . ($action == 'suspend' ? 'suspension' : 'désuspension') . " de l'utilisateur.";
    }
}
?>
<h1>Tableau de bord Administrateur</h1>

<section>
    <div class="stats-container">
        <h2>Statistiques des Crédits</h2>
        <div class="total-credits">
            <h3>Total des crédits gagnés depuis le <?php echo $date_debut->format('Y-m-d'); ?> : <?php echo $total_credits; ?> crédits</h3>
        </div>
        <canvas id="creditsChart"></canvas>
    </div>
</section>

<section>
    <div class="admin-container">
        <div class="employee-form-container">
            <h2>Créer un compte employé</h2>
            <?php if (!empty($message)): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="POST" action="adminDashboard.php">
                <input type="hidden" name="create_employee" value="1">
                <label for="pseudo">Pseudo:</label>
                <input type="text" id="pseudo" name="pseudo" required><br>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required><br>

                <label for="password">Mot de passe:</label>
                <input type="password" id="password" name="password" required><br>
                <div class="form-group p-2">
                    <p>Votre mot de passe doit contenir au moins 8 caractères, dont : <br> - Une majuscule, <br>
                    - Une minuscule, <br>
                    - Un chiffre et <br>
                    - Un caractère spécial (par exemple, @, #, $, %).</p>
                </div>

                <label for="confirm_password">Confirmer le mot de passe:</label>
                <input type="password" id="confirm_password" name="confirm_password" required><br>

                <label for="nom">Nom:</label>
                <input type="text" id="nom" name="nom" required><br>

                <label for="prenom">Prénom:</label>
                <input type="text" id="prenom" name="prenom" required><br>

                <button type="submit">Créer</button>
            </form>
        </div>

        <div class="user-management-container">
            <h2>Gestion des utilisateurs</h2>
            <div class="search-container">
                <form method="GET" action="adminDashboard.php">
                    <input type="email" name="search" placeholder="Rechercher par email..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" required>
                    <button type="submit">Rechercher</button>
                </form>
            </div>
            <?php
            // Vérifier si une recherche a été effectuée
            if (isset($_GET['search'])) {
                $search = $_GET['search'];

                // Récupérer l'utilisateur correspondant à l'email recherché
                $sql = "SELECT id, pseudo, email, suspendu FROM utilisateurs WHERE email = :email";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':email', $search, PDO::PARAM_STR);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // Afficher les informations de l'utilisateur et les options de suspension/désuspension
                    ?>
                    <div class="user-details">
                        <h3>Détails de l'utilisateur</h3>
                        <p><strong>ID:</strong> <?php echo $user['id']; ?></p>
                        <p><strong>Pseudo:</strong> <?php echo $user['pseudo']; ?></p>
                        <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
                        <p><strong>Suspendu:</strong> <?php echo $user['suspendu'] ? 'Oui' : 'Non'; ?></p>
                        <form method="POST" action="adminDashboard.php" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <?php if (!$user['suspendu']): ?>
                                <button type="submit" name="suspend_user">Suspendre</button>
                            <?php else: ?>
                                <button type="submit" name="unsuspend_user">Désuspendre</button>
                            <?php endif; ?>
                        </form>
                    </div>
                    <?php
                } else {
                    echo "<p>Aucun utilisateur trouvé avec cet email.</p>";
                }
            }
            ?>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
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
</div>

<?php
require_once('templates/footer.php');
?>
