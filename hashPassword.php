
<?php
$password = 'SuperAdminEcoride';  
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
echo "Mot de passe haché : " . $hashedPassword;
?>
