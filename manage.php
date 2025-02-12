<?php
require_once('templates/header.php');
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Vérification que l'utilisateur est admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['type_acces']) || $_SESSION['type_acces'] !== 'administrateur') {
    // Redirection ou message d'erreur si l'utilisateur n'est pas admin
}

$message = "";

// Fonction d'envoi d'email
function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // Configuration SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'testing.projets.siteweb@gmail.com';
        $mail->Password = 'sljw jlop qtyy mqae';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Configuration de l'expéditeur et du destinataire
        $mail->setFrom('rogez.aurore01@gmail.com', 'EcoRide');
        $mail->addAddress($to);

        // Contenu
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur d'envoi d'email: " . $mail->ErrorInfo);
        return false;
    }
}

class InputValidator {
    public static function sanitizeString($input) {
        return htmlspecialchars(strip_tags(trim($input)));
    }

    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function validatePassword($password) {
        return strlen($password) >= 8
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/[0-9]/', $password);
    }

    public static function validateUserId($id) {
        return filter_var($id, FILTER_VALIDATE_INT) && $id > 0;
    }
}

function logAdminAction($action, $userId, $details) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, user_id, details, ip_address)
            VALUES (:admin_id, :action, :user_id, :details, :ip)
        ");

        return $stmt->execute([
            ':admin_id' => $_SESSION['user_id'] ?? 0,
            ':action' => $action,
            ':user_id' => $userId,
            ':details' => json_encode($details),
            ':ip' => $_SERVER['REMOTE_ADDR']
        ]);
    } catch (PDOException $e) {
        error_log("Erreur lors du log : " . $e->getMessage());
        return false;
    }
}

// Traitement du formulaire de création de compte employé
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_employee'])) {
    $pseudo = InputValidator::sanitizeString($_POST['pseudo']);
    $email = InputValidator::sanitizeString($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nom = InputValidator::sanitizeString($_POST['nom']);
    $prenom = InputValidator::sanitizeString($_POST['prenom']);
    $telephone = InputValidator::sanitizeString($_POST['telephone']);
    $adresse = InputValidator::sanitizeString($_POST['adresse']);
    $code_postal = InputValidator::sanitizeString($_POST['code_postal']);
    $ville = InputValidator::sanitizeString($_POST['ville']);

    if (!InputValidator::validateEmail($email)) {
        $message = "Email invalide";
    } elseif (!InputValidator::validatePassword($password)) {
        $message = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre";
    } elseif ($password !== $confirm_password) {
        $message = "Les mots de passe ne correspondent pas";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? OR pseudo = ?");
            $stmt->execute([$email, $pseudo]);
            if ($stmt->fetchColumn()) {
                $message = "Cet email ou ce pseudo est déjà utilisé";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    INSERT INTO utilisateurs (pseudo, email, password, nom, prenom, telephone, adresse,
                    code_postal, ville, type_acces, role, suspendu, credits)
                    VALUES (:pseudo, :email, :password, :nom, :prenom, :telephone, :adresse,
                    :code_postal, :ville, 'employe', 'passager', 0, 0)
                ");

                $success = $stmt->execute([
                    ':pseudo' => $pseudo,
                    ':email' => $email,
                    ':password' => $hashedPassword,
                    ':nom' => $nom,
                    ':prenom' => $prenom,
                    ':telephone' => $telephone,
                    ':adresse' => $adresse,
                    ':code_postal' => $code_postal,
                    ':ville' => $ville
                ]);

                if ($success) {
                    $emailBody = "
                        <h2>Bienvenue chez EcoRide !</h2>
                        <p>Bonjour $prenom $nom,</p>
                        <p>Votre compte employé a été créé avec succès.</p>
                        <p><strong>Identifiant :</strong> $email</p>
                        <p>Veuillez vous connecter pour changer votre mot de passe.</p>
                        <p>Cordialement,<br>L'équipe EcoRide</p>
                    ";

                    if (sendEmail('rogez.aurore01@gmail.com', 'Création de votre compte employé', $emailBody)) {
                        $message = "Compte employé créé avec succès. Un email a été envoyé.";
                    } else {
                        $message = "Compte créé mais erreur lors de l'envoi de l'email.";
                    }

                    logAdminAction('create_employee', $pdo->lastInsertId(), [
                        'pseudo' => $pseudo,
                        'email' => $email
                    ]);
                } else {
                    $message = "Erreur lors de la création du compte";
                }
            }
        } catch (PDOException $e) {
            error_log("Erreur DB: " . $e->getMessage());
            $message = "Erreur lors de la création du compte";
        }
    }
}

// Traitement de la suspension/désuspension d'un utilisateur
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['suspend_user']) || isset($_POST['unsuspend_user']))) {
    $userId = InputValidator::sanitizeString($_POST['user_id']);

    if (!InputValidator::validateUserId($userId)) {
        $message = "ID utilisateur invalide";
    } else {
        try {
            $checkUser = $pdo->prepare("SELECT id, email, nom, prenom FROM utilisateurs WHERE id = ?");
            $checkUser->execute([$userId]);
            $user = $checkUser->fetch();

            if (!$user) {
                $message = "Utilisateur non trouvé";
            } else {
                $suspendu = isset($_POST['suspend_user']) ? 1 : 0;

                $stmt = $pdo->prepare("
                    UPDATE utilisateurs
                    SET suspendu = :suspendu
                    WHERE id = :id
                ");

                if ($stmt->execute([':suspendu' => $suspendu, ':id' => $userId])) {
                    $action = $suspendu ? 'suspend_user' : 'unsuspend_user';
                    logAdminAction($action, $userId, ['suspendu' => $suspendu]);

                    $status = $suspendu ? 'suspendu' : 'réactivé';
                    $emailBody = "
                        <h2>Modification du statut de votre compte EcoRide</h2>
                        <p>Bonjour {$user['prenom']} {$user['nom']},</p>
                        <p>Nous vous informons que votre compte a été <strong>$status</strong>.</p>
                        " . ($suspendu ?
                        "<p>Si vous pensez qu'il s'agit d'une erreur, veuillez contacter notre support.</p>" :
                        "<p>Vous pouvez maintenant vous reconnecter à votre compte.</p>") . "
                        <p>Cordialement,<br>L'équipe EcoRide</p>
                    ";

                    $subject = $suspendu ? 'Suspension de votre compte EcoRide' : 'Réactivation de votre compte EcoRide';

                    if (sendEmail('rogez.aurore01@gmail.com', $subject, $emailBody)) {
                        $message = "Statut de l'utilisateur $userId mis à jour avec succès. Notification envoyée.";
                    } else {
                        $message = "Statut mis à jour mais erreur lors de l'envoi de la notification.";
                    }
                } else {
                    $message = "Erreur lors de la mise à jour du statut";
                }
            }
        } catch (PDOException $e) {
            error_log("Erreur DB suspension: " . $e->getMessage());
            $message = "Erreur lors de la mise à jour du statut";
        }
    }
}

// Récupérer la liste des utilisateurs pour l'affichage
try {
    $users = $pdo->query("SELECT id, pseudo, email, type_acces, suspendu FROM utilisateurs ORDER BY id")->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des utilisateurs: " . $e->getMessage());
    $users = [];
}
?>

<?php if (!empty($message)): ?>
    <div id="status-message" class="status-message <?php echo strpos(strtolower($message), 'erreur') !== false ? 'error' : 'success'; ?> show">
        <?php echo htmlspecialchars($message); ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messageElement = document.getElementById('status-message');
            if (messageElement) {
                setTimeout(() => {
                    messageElement.classList.remove('show');
                }, 3000);
            }
        });
    </script>
<?php endif; ?>

<div class="btn-container">
    <a href="adminDashboard.php" class="btn-retour">⬅ Retour au tableau de bord</a>
</div>

<h1 class="text-center my-4">Gestion des Utilisateurs et Employés</h1>

<section class="admin-content-section">
    <div class="admin-employee-container m-3">
        <h2 class="admin-section-title">Créer un compte employé</h2>

        <form method="POST" action="manage.php" class="admin-management-form">
   <input type="hidden" name="create_employee" value="1">
   
   <label class="admin-form-label" for="pseudo">Pseudo:</label>
   <input class="admin-form-input" type="text" id="pseudo" name="pseudo" autocomplete="username" required>
   
   <label class="admin-form-label" for="email">Email:</label> 
   <input class="admin-form-input" type="email" id="email" name="email" autocomplete="email" required>
   
   <label class="admin-form-label" for="password">Mot de passe:</label>
   <input class="admin-form-input" type="password" id="password" name="password" autocomplete="new-password" required>
   
   <label class="admin-form-label" for="confirm_password">Confirmer le mot de passe:</label>
   <input class="admin-form-input" type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
   
   <label class="admin-form-label" for="nom">Nom:</label>
   <input class="admin-form-input" type="text" id="nom" name="nom" autocomplete="family-name" required>
   
   <label class="admin-form-label" for="prenom">Prénom:</label>
   <input class="admin-form-input" type="text" id="prenom" name="prenom" autocomplete="given-name" required>
   
   <label class="admin-form-label" for="telephone">Téléphone:</label>
   <input class="admin-form-input" type="tel" id="telephone" name="telephone" autocomplete="tel" required>
   
   <label class="admin-form-label" for="adresse">Adresse:</label>
   <input class="admin-form-input" type="text" id="adresse" name="adresse" autocomplete="street-address" required>
   
   <label class="admin-form-label" for="code_postal">Code Postal:</label>
   <input class="admin-form-input" type="text" id="code_postal" name="code_postal" autocomplete="postal-code" required>
   
   <label class="admin-form-label" for="ville">Ville:</label>
   <input class="admin-form-input" type="text" id="ville" name="ville" autocomplete="address-level2" required>
   
   <button class="admin-action-btn" type="submit">Créer</button>
</form>
    </div>

    <div class="admin-user-container m-3">
        <h2 class="admin-section-title">Gestion des utilisateurs</h2>

        <form method="POST" action="manage.php" class="admin-management-form">
            <label class="admin-form-label" for="user_id">ID Utilisateur:</label>
            <input class="admin-form-input" type="number" id="user_id" name="user_id" required>
            <button class="admin-action-btn" type="submit" name="search_user">Rechercher</button>
        </form>

        <?php if (isset($_POST['search_user'])):
            $userId = InputValidator::sanitizeString($_POST['user_id']);
            if (InputValidator::validateUserId($userId)) {
                try {
                    $stmt = $pdo->prepare("SELECT id, pseudo, email, nom, prenom, suspendu FROM utilisateurs WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();

                    if ($user): ?>
                        <div class="user-details">
                            <h3 class="admin-subtitle">Détails de l'utilisateur :</h3>
                            <p>ID: <?php echo htmlspecialchars($user['id']); ?></p>
                            <p>Pseudo: <?php echo htmlspecialchars($user['pseudo']); ?></p>
                            <p>Nom: <?php echo htmlspecialchars($user['nom']); ?></p>
                            <p>Prénom: <?php echo htmlspecialchars($user['prenom']); ?></p>
                            <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
                            <p>Statut: <?php echo $user['suspendu'] ? 'Suspendu' : 'Actif'; ?></p>

                            <form method="POST" action="manage.php" class="admin-management-form">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                <?php if ($user['suspendu']): ?>
                                    <button class="admin-action-btn admin-unsuspend-btn" type="submit" name="unsuspend_user">Désuspendre</button>
                                <?php else: ?>
                                    <button class="admin-action-btn admin-suspend-btn" type="submit" name="suspend_user">Suspendre</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="admin-status-message admin-status-error">Utilisateur non trouvé.</div>
                    <?php endif;
                } catch (PDOException $e) {
                    error_log("Erreur DB recherche: " . $e->getMessage());
                    echo '<div class="admin-status-message admin-status-error">Erreur lors de la recherche de l\'utilisateur.</div>';
                }
            } else {
                echo '<div class="admin-status-message admin-status-error">ID utilisateur invalide.</div>';
            }
        endif; ?>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const password = form.querySelector('input[type="password"]');
                const confirmPassword = form.querySelector('#confirm_password');

                if (password && confirmPassword) {
                    if (password.value !== confirmPassword.value) {
                        e.preventDefault();
                        alert('Les mots de passe ne correspondent pas');
                        return false;
                    }

                    if (!isPasswordStrong(password.value)) {
                        e.preventDefault();
                        alert('Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre');
                        return false;
                    }
                }
            });
        });

        function isPasswordStrong(password) {
            return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(password);
        }
    });
</script>
