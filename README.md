EcoRide 🌱

EcoRide est une plateforme de covoiturage écologique développée en PHP qui met en relation conducteurs et passagers, avec un accent particulier sur les véhicules électriques et hybrides.

🚀 Fonctionnalités

- Système de crédits internes pour les transactions
- Recherche avancée de trajets avec filtres écologiques
- Gestion des profils conducteurs et passagers
- Système d'avis et de notation
- Interface d'administration complète
- Validation des avis par des employés
- Tableau de bord avec statistiques
- Envoi de notifications par email

💻 Technologies Utilisées

- PHP 8.2+
- MySQL 8.0+ (données principales)
- MongoDB 6.0+ (gestion des crédits)
- JavaScript
- Bootstrap
- Docker
- Chart.js
- PHPMailer

📋 Prérequis

- PHP 8.2 ou supérieur
- MySQL 8.0 ou supérieur
- MongoDB 6.0 ou supérieur
- Apache 2.4 ou supérieur
- Composer
- Docker (optionnel)

🛠 Installation

1. Clonez le dépôt

git clone [url-du-repo]


2. Installez les dépendances

composer install


3. Configurez les bases de données
- Importez le fichier `ecoride_bdd.sql` dans MySQL
- Configurez les connexions dans `config.local.php` et `config.php`

4. Configuration avec Docker (optionnel)

docker-compose up -d --build


5. Accédez à l'application via

[http://localhost:8080](https://ecoride-projet-32f6b3dd2af2.herokuapp.com/)


🔐 Sécurité

- Protection contre les injections SQL
- Protection XSS
- Tokens CSRF
- Sessions sécurisées
- Validation des entrées
- En-têtes de sécurité configurés
- Tests de sécurité avec Nikto + SNYK

📱 Interface Responsive

L'application est entièrement responsive grâce à Bootstrap et adaptée pour :
- Ordinateurs de bureau
- Tablettes
- Smartphones

👥 Rôles Utilisateurs

Visiteur : Recherche de trajets, mentions légales, cookies, CGDV
Utilisateur : Réservation/création de trajets, gestion du profil, lancer/annuler/terminé trajet, ajouter des véhicules... il est passager ou chauffeur selon le trajet
Employé : Validation des avis, gestion des incidents
Administrateur : Gestion complète de la plateforme

💳 Système de Crédits

- 20 crédits offerts à l'inscription
- Commission de 2 crédits par trajet pour la plateforme
- Validation des crédits après modération des avis

🔧 Identifiants Admin

Login: SuperAdminPseudo
Password: SuperAdminEcoride

📝 Structure du Projet

lib/               # Fichiers de configuration
public/            # Fichiers publics
templates/         # Templates de l'interface
uploads/photos/    # Stockage des fichiers
vendor/           # Dépendances Composer


📄 Licence

Développé par Aurore Rogez dans le cadre d'un projet d'examen
