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
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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

// Traitement de la suspension d'un utilisateur
if (isset($_POST['suspend_user'])) {
    $user_id = $_POST['user_id'];

    // Mettre à jour la colonne "suspendu" dans la table utilisateurs
    $sql = "UPDATE utilisateurs SET suspendu = 1 WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        // Récupérer l'email de l'utilisateur suspendu
        $sql = "SELECT email FROM utilisateurs WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user_email = $stmt->fetch(PDO::FETCH_ASSOC)['email'];

        // Envoyer un email de notification
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.example.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'your_email@example.com';
            $mail->Password = 'your_email_password';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('your_email@example.com', 'Admin');
            $mail->addAddress($user_email);

            $mail->isHTML(true);
            $mail->Subject = 'Votre compte a été suspendu';
            $mail->Body    = 'Bonjour,<br>Votre compte a été suspendu par l\'administrateur.';
            $mail->AltBody = 'Bonjour,\nVotre compte a été suspendu par l\'administrateur.';

            $mail->send();
            $message = "L'utilisateur a été suspendu et un email de notification a été envoyé.";
        } catch (Exception $e) {
            $message = "Erreur lors de l'envoi de l'email: " . $mail->ErrorInfo;
        }
    } else {
        $message = "Erreur lors de la suspension de l'utilisateur.";
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
        <?php
        // Récupérer la liste des utilisateurs
        $sql = "SELECT id, pseudo, email, suspendu FROM utilisateurs";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pseudo</th>
                    <th>Email</th>
                    <th>Suspendu</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo $user['pseudo']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td><?php echo $user['suspendu'] ? 'Oui' : 'Non'; ?></td>
                        <td>
                            <?php if (!$user['suspendu']): ?>
                                <form method="POST" action="adminDashboard.php" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="suspend_user">Suspendre</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
