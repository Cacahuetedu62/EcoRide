CREATE DATABASE IF NOT EXISTS EcoRide;
USE EcoRide;

CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    prenom VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    adresse TEXT,
    photo VARCHAR(255),
    pseudo VARCHAR(255) UNIQUE NOT NULL,
    date_naissance DATE
);

CREATE TABLE voitures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modele VARCHAR(255) NOT NULL,
    immatriculation VARCHAR(20) UNIQUE NOT NULL,
    marque VARCHAR(255) NOT NULL,
    energie VARCHAR(50),
    couleur VARCHAR(50),
    nb_places INT,
    date_premiere_immatriculation DATE,
    utilisateur_id INT,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

CREATE TABLE trajets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_depart DATE NOT NULL,
    heure_depart TIME NOT NULL,
    lieu_depart VARCHAR(255) NOT NULL,
    date_arrive DATE NOT NULL,
    heure_arrive TIME NOT NULL,
    lieu_arrive VARCHAR(255) NOT NULL,
    statut VARCHAR(50),
    nb_places INT,
    prix_personnes DECIMAL(10, 2),
    preferences TEXT
);

CREATE TABLE trajet_utilisateur (
    utilisateur_id INT,
    trajet_id INT,
    PRIMARY KEY (utilisateur_id, trajet_id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (trajet_id) REFERENCES trajets(id) ON DELETE CASCADE
);

CREATE TABLE avis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commentaires TEXT,
    note INT,
    statut VARCHAR(50),
    utilisateur_id INT,
    trajet_id INT,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (trajet_id) REFERENCES trajets(id) ON DELETE CASCADE
);

INSERT INTO utilisateurs (nom, prenom, email, password, telephone, adresse, photo, pseudo, date_naissance) VALUES
('Durand', 'Jean', 'jean.durand@example.com', 'password123', '0601020304', '12 rue des Fleurs, Paris', 'photo1.jpg', 'jdurand', '1985-06-15'),
('Lemoine', 'Marie', 'marie.lemoine@example.com', 'password456', '0605060708', '5 avenue des Champs, Lyon', 'photo2.jpg', 'mlemoine', '1990-09-22'),
('Lemoine', 'Paul', 'paul.lemoine@example.com', 'password789', '0612345678', '3 rue des Roses, Lille', 'photo3.jpg', 'plemoine', '1993-02-17'),
('Dupont', 'Alice', 'alice.dupont@example.com', 'alicepass', '0623456789', '7 rue du Parc, Bordeaux', 'photo4.jpg', 'adupont', '1991-03-30'),
('Benoit', 'Pierre', 'pierre.benoit@example.com', 'pierre123', '0634567890', '15 avenue Victor Hugo, Marseille', 'photo5.jpg', 'pbenoit', '1984-11-10'),
('Gomez', 'Carlos', 'carlos.gomez@example.com', 'carlosecret', '0678901234', '22 rue des Ecoles, Toulouse', 'photo6.jpg', 'cgomez', '1987-07-05'),
('Martin', 'Sophie', 'sophie.martin@example.com', 'sophie123', '0654321098', '3 place des Nations, Paris', 'photo7.jpg', 'smartin', '1992-08-21'),
('Boulanger', 'Lucie', 'lucie.boulanger@example.com', 'lucie456', '0689012345', '10 rue de la République, Nice', 'photo8.jpg', 'lboulanger', '1988-12-30'),
('Pires', 'Antonio', 'antonio.pires@example.com', 'antonio789', '0690123456', '8 boulevard Saint-Germain, Paris', 'photo9.jpg', 'apires', '1995-01-10'),
('Dupuis', 'Julien', 'julien.dupuis@example.com', 'julien123', '0615987342', '20 rue du Général Leclerc, Nantes', 'photo10.jpg', 'jdupuis', '1994-05-16'),
('Lemoine', 'Isabelle', 'isabelle.lemoine@example.com', 'isabellepass', '0698765432', '12 rue des Lilas, Strasbourg', 'photo11.jpg', 'ilemoine', '1990-06-12'),
('Marchand', 'Claude', 'claude.marchand@example.com', 'claude987', '0676543210', '1 place de la Liberté, Paris', 'photo12.jpg', 'cmarchand', '1986-01-22'),
('Bernard', 'Michel', 'michel.bernard@example.com', 'michel321', '0608091011', '25 rue des Acacias, Bordeaux', 'photo13.jpg', 'mbernard', '1993-11-03'),
('Vasseur', 'Laura', 'laura.vasseur@example.com', 'laura321', '0612344321', '14 rue des Cygnes, Lyon', 'photo14.jpg', 'lvasseur', '1996-09-25'),
('Roux', 'David', 'david.roux@example.com', 'david654', '0623459876', '6 avenue des Ternes, Paris', 'photo15.jpg', 'droux', '1982-05-17'),
('Meyer', 'Chantal', 'chantal.meyer@example.com', 'chantal123', '0698765432', '17 rue des Vignes, Lille', 'photo16.jpg', 'cmeyer', '1989-02-09'),
('Deschamps', 'Nicolas', 'nicolas.deschamps@example.com', 'nicolas456', '0622222333', '18 boulevard de la Gare, Marseille', 'photo17.jpg', 'ndeschamps', '1992-11-04'),
('Chevalier', 'Jacques', 'jacques.chevalier@example.com', 'jacques789', '0611234321', '21 rue des Alpes, Grenoble', 'photo18.jpg', 'jchevalier', '1985-07-23'),
('Girard', 'Elodie', 'elodie.girard@example.com', 'elodie123', '0681234567', '23 avenue des Vosges, Strasbourg', 'photo19.jpg', 'egirard', '1990-12-18'),
('Tanguy', 'Bernadette', 'bernadette.tanguy@example.com', 'bernadette321', '0695432109', '9 rue du Midi, Toulouse', 'photo20.jpg', 'btanguy', '1994-04-11');

INSERT INTO voitures (modele, immatriculation, marque, energie, couleur, nb_places, date_premiere_immatriculation, utilisateur_id) VALUES
('Clio', 'AB123CD', 'Renault', 'Essence', 'Rouge', 5, '2015-03-12', 1),
('208', 'XY987ZB', 'Peugeot', 'Diesel', 'Bleu', 5, '2018-06-01', 2),
('A3', 'CD456EF', 'Audi', 'Essence', 'Noir', 5, '2020-09-15', 3),
('C4', 'EF789GH', 'Citroën', 'Hybride', 'Blanc', 5, '2019-07-20', 4),
('Golf', 'GH123IJ', 'Volkswagen', 'Essence', 'Gris', 5, '2017-10-10', 5),
('Mégane', 'IJ456KL', 'Renault', 'Diesel', 'Bleu', 5, '2016-02-25', 6),
('Focus', 'KL789MN', 'Ford', 'Essence', 'Rouge', 5, '2021-01-05', 7),
('X5', 'MN012OP', 'BMW', 'Essence', 'Noir', 7, '2022-11-30', 8),
('Talisman', 'OP345QR', 'Renault', 'Diesel', 'Blanc', 5, '2020-04-10', 9),
('Megane', 'QR567ST', 'Renault', 'Essence', 'Vert', 5, '2018-08-20', 10),
('Corsa', 'ST890UV', 'Opel', 'Essence', 'Jaune', 5, '2017-12-15', 11),
('A4', 'UV123WX', 'Audi', 'Diesel', 'Bleu', 5, '2019-03-25', 12),
('Polo', 'WX456YZ', 'Volkswagen', 'Essence', 'Rouge', 5, '2021-07-12', 13),
('Freemont', 'YZ789AB', 'Fiat', 'Diesel', 'Noir', 7, '2016-11-08', 14),
('Kuga', 'AB012CD', 'Ford', 'Hybride', 'Gris', 5, '2020-05-20', 15),
('Qashqai', 'CD234EF', 'Nissan', 'Diesel', 'Blanc', 5, '2019-09-19', 16),
('CX-5', 'EF567GH', 'Mazda', 'Essence', 'Bleu', 5, '2018-02-18', 17),
('Captur', 'GH789IJ', 'Renault', 'Essence', 'Jaune', 5, '2017-04-23', 18),
('5008', 'IJ012KL', 'Peugeot', 'Essence', 'Noir', 7, '2019-12-30', 19),
('Kona', 'KL345MN', 'Hyundai', 'Hybride', 'Rouge', 5, '2020-10-12', 20);

INSERT INTO trajets (date_depart, heure_depart, lieu_depart, date_arrive, heure_arrive, lieu_arrive, statut, nb_places, prix_personnes, preferences) VALUES
('2024-12-15', '08:00:00', 'Paris', '2024-12-15', '12:00:00', 'Lyon', 'Disponible', 4, 50.00, 'Non-fumeur'),
('2024-12-16', '09:00:00', 'Lille', '2024-12-16', '13:00:00', 'Marseille', 'Complet', 0, 80.00, 'Animaux permis'),
('2024-12-17', '10:00:00', 'Paris', '2024-12-17', '14:00:00', 'Toulouse', 'Disponible', 3, 70.00, 'Aucun stop'),
('2024-12-18', '11:00:00', 'Strasbourg', '2024-12-18', '15:00:00', 'Nice', 'Annulé', 0, 90.00, 'Musique à fond'),
('2024-12-19', '12:00:00', 'Bordeaux', '2024-12-19', '16:00:00', 'Marseille', 'Disponible', 4, 75.00, 'Non-fumeur'),
('2024-12-20', '13:00:00', 'Paris', '2024-12-20', '17:00:00', 'Lyon', 'Disponible', 5, 65.00, 'Climatisation'),
('2024-12-21', '14:00:00', 'Lyon', '2024-12-21', '18:00:00', 'Strasbourg', 'Complet', 0, 85.00, 'Trajet rapide'),
('2024-12-22', '15:00:00', 'Marseille', '2024-12-22', '19:00:00', 'Toulouse', 'Disponible', 4, 60.00, 'Aucun stop'),
('2024-12-23', '16:00:00', 'Lille', '2024-12-23', '20:00:00', 'Paris', 'Annulé', 0, 55.00, 'Musique calme'),
('2024-12-24', '17:00:00', 'Bordeaux', '2024-12-24', '21:00:00', 'Nice', 'Disponible', 3, 95.00, 'Animaux permis'),
('2024-12-25', '18:00:00', 'Lyon', '2024-12-25', '22:00:00', 'Marseille', 'Disponible', 5, 50.00, 'Non-fumeur'),
('2024-12-26', '19:00:00', 'Paris', '2024-12-26', '23:00:00', 'Strasbourg', 'Complet', 0, 80.00, 'Aucun stop'),
('2024-12-27', '20:00:00', 'Marseille', '2024-12-27', '00:00:00', 'Toulouse', 'Disponible', 4, 60.00, 'Trajet rapide'),
('2024-12-28', '21:00:00', 'Nice', '2024-12-28', '01:00:00', 'Paris', 'Complet', 0, 100.00, 'Musique à fond'),
('2024-12-29', '22:00:00', 'Strasbourg', '2024-12-29', '02:00:00', 'Lyon', 'Disponible', 5, 55.00, 'Climatisation'),
('2024-12-30', '23:00:00', 'Bordeaux', '2024-12-30', '03:00:00', 'Marseille', 'Annulé', 0, 95.00, 'Animaux permis'),
('2024-12-31', '08:00:00', 'Lyon', '2024-12-31', '12:00:00', 'Toulouse', 'Disponible', 4, 65.00, 'Non-fumeur'),
('2025-01-01', '09:00:00', 'Paris', '2025-01-01', '13:00:00', 'Strasbourg', 'Complet', 0, 70.00, 'Aucun stop'),
('2025-01-02', '10:00:00', 'Marseille', '2025-01-02', '14:00:00', 'Nice', 'Disponible', 3, 80.00, 'Trajet rapide');

INSERT INTO avis (commentaires, note, statut, utilisateur_id, trajet_id) VALUES
('Très bon trajet, conducteur sympathique', 5, 'Validé', 1, 1),
('Tout s\'est bien passé, je recommande', 4, 'Validé', 2, 2),
('Le trajet était agréable, mais un peu lent', 3, 'Validé', 3, 3),
('Super trajet, merci au conducteur', 5, 'Validé', 4, 4),
('Très bonne expérience, mais un peu trop bruyant', 4, 'Validé', 5, 5),
('Le trajet était confortable, rien à redire', 5, 'Validé', 6, 6),
('Très bonne organisation, mais trajet un peu long', 4, 'Validé', 7, 7),
('Bon trajet, mais le véhicule était un peu sale', 3, 'Validé', 8, 8),
('Je recommande ce conducteur, très ponctuel', 5, 'Validé', 9, 9),
('Le trajet était bien, mais le conducteur avait un mauvais comportement', 2, 'Validé', 10, 10),
('Confort total, j\'ai adoré', 5, 'Validé', 11, 11),
('Très mauvais trajet, trop de retards', 1, 'Validé', 12, 12),
('Bien, mais pas assez d\'arrêts pour les pauses', 4, 'Validé', 13, 13),
('Le conducteur était sympathique mais le véhicule était trop petit', 3, 'Validé', 14, 14),
('Le trajet a été agréable, mais la musique était trop forte', 4, 'Validé', 15, 15),
('J\'ai apprécié le trajet, mais les sièges étaient inconfortables', 3, 'Validé', 16, 16),
('Excellente expérience, mais manque de climatisation', 4, 'Validé', 17, 17),
('Très bon trajet, je reviendrai', 5, 'Validé', 18, 18),
('Rien à dire, tout s\'est bien passé', 5, 'Validé', 19, 19),
('Un trajet parfait, merci beaucoup', 5, 'Validé', 20, 20);

INSERT INTO trajet_utilisateur (utilisateur_id, trajet_id) VALUES
(1, 5), (2, 1), (3, 2), (4, 3), (5, 4), (6, 5), (7, 6), (8, 7), (9, 8), (10, 9),
(11, 10), (12, 11), (13, 12), (14, 13), (15, 14), (16, 15), (17, 16), (18, 17), (19, 18), (20, 19);

ALTER TABLE trajets
ADD COLUMN numero_trajets INT NOT NULL DEFAULT 1000;

ALTER TABLE trajets
MODIFY COLUMN numero_trajets INT AUTO_INCREMENT;

ALTER TABLE trajets
DROP COLUMN numero_trajets;

DESCRIBE trajets;

ALTER TABLE utilisateurs
ADD COLUMN note_moyenne DECIMAL(3, 2) DEFAULT NULL;

UPDATE utilisateurs u
JOIN (
    SELECT utilisateur_id, AVG(note) AS moyenne
    FROM avis
    GROUP BY utilisateur_id
) AS avg_avis ON u.id = avg_avis.utilisateur_id
SET u.note_moyenne = avg_avis.moyenne;


INSERT INTO avis (commentaires, note, statut, utilisateur_id, trajet_id) VALUES
('Très bon trajet, chauffeur sympathique.', 5, 'validé', 6, 1),
('Voyage agréable, un peu long mais tout s\'est bien passé.', 4, 'validé', 6, 2),
('Chauffeur ponctuel, mais la voiture n\'était pas très propre.', 3, 'validé', 6, 3);

INSERT INTO avis (commentaires, note, statut, utilisateur_id, trajet_id) VALUES
('Horrible', 1, 'validé', 6, 1);


UPDATE utilisateurs
SET note_moyenne = (
    SELECT AVG(note)
    FROM avis
    WHERE utilisateur_id = 6
)
WHERE id = 6;

INSERT INTO voitures (modele, immatriculation, marque, energie, couleur, nb_places, date_premiere_immatriculation, utilisateur_id)
VALUES ('Tesla Model 3', 'AB-126-CD', 'Tesla', 'électrique', 'Noir', 5, '2023-01-15', 1);

INSERT INTO trajets (date_depart, heure_depart, lieu_depart, date_arrive, heure_arrive, lieu_arrive, statut, nb_places, prix_personnes, preferences) VALUES
('2024-12-15', '08:00:00', 'Paris', '2024-12-15', '12:00:00', 'dijon', 'Disponible', 2, 40.00, 'Commentaire du trajets 20 pour utilisateur 4 ADUPONT');

SELECT * FROM trajets WHERE id = 58;

DESCRIBE trajet_utilisateur;
DELIMITER //

CREATE PROCEDURE CreerTrajet(
    IN p_utilisateur_id INT,
    IN p_date_depart DATE,
    IN p_heure_depart TIME,
    IN p_lieu_depart VARCHAR(255),
    IN p_date_arrive DATE,
    IN p_heure_arrive TIME,
    IN p_lieu_arrive VARCHAR(255),
    IN p_nb_places INT,
    IN p_prix_personnes DECIMAL(10, 2),
    IN p_preferences TEXT,
    IN p_voiture_id INT
)
BEGIN

    DECLARE trajet_id INT;

    INSERT INTO trajets (date_depart, heure_depart, lieu_depart, date_arrive, heure_arrive, lieu_arrive, nb_places, prix_personnes, preferences)
    VALUES (p_date_depart, p_heure_depart, p_lieu_depart, p_date_arrive, p_heure_arrive, p_lieu_arrive, p_nb_places, p_prix_personnes, p_preferences);

    SET trajet_id = LAST_INSERT_ID();

    INSERT INTO trajet_utilisateur (utilisateur_id, trajet_id) VALUES (p_utilisateur_id, trajet_id);

END DELIMITER ;

INSERT INTO avis (commentaires, note, statut, utilisateur_id, trajet_id) VALUES
('commentaire test utilisateur 4', 2, 'validé', 4, 3);

INSERT INTO trajets (date_depart, heure_depart, lieu_depart, date_arrive, heure_arrive, lieu_arrive, statut, nb_places, prix_personnes, preferences)
VALUES
('2024-12-20', '08:00:00', 'Lens', '2024-12-20', '10:30:00', 'Metz', 'Disponible', 4, 25.00, 'Voiture avec air conditionné');

ALTER TABLE utilisateurs
ADD COLUMN credits INT DEFAULT 0;

ALTER TABLE utilisateurs
ADD COLUMN role VARCHAR(50) DEFAULT 'passager';

ALTER TABLE utilisateurs
ADD COLUMN code_postal VARCHAR(5),
ADD COLUMN ville VARCHAR(100);

DESCRIBE voitures;

DELETE FROM voitures WHERE id IN (45, 46, 47, 48, 49, 50, 51, 53);

DELETE FROM utilisateurs
WHERE id IN (22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32);

UPDATE utilisateurs
SET credits = credits + 100
WHERE id = 33;


UPDATE utilisateurs
SET credits = credits + 100
WHERE id = 33;

INSERT INTO trajets (
    date_depart, heure_depart, lieu_depart, 
    date_arrive, heure_arrive, lieu_arrive, 
    statut, nb_places, prix_personnes, preferences
) VALUES (
    '2025-01-02', '08:00:00', 'Paris',
    '2025-01-02', '10:00:00', 'Lyon',
    'Disponible', 4, 50.00, 'Voyage confortable, climatisation et WiFi disponibles'
);


INSERT INTO trajets (
    date_depart, heure_depart, lieu_depart, 
    date_arrive, heure_arrive, lieu_arrive, 
    statut, nb_places, prix_personnes, preferences
) VALUES (
    '2025-01-03', '08:00:00', 'Nice',
    '2025-01-04', '10:00:00', 'Paris',
    'Disponible', 4, 50.00, 'Voyage confortable, climatisation et WiFi disponibles'
);


INSERT INTO voitures (modele, immatriculation, marque, energie, couleur, nb_places, date_premiere_immatriculation, utilisateur_id)
VALUES ('Tesla Model S', 'AB-123-ZZ', 'Tesla', 'Électrique', 'Noir', 5, '2021-06-15', 33);

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    trajet_id INT NOT NULL,
    nb_personnes INT NOT NULL,
    date_reservation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (trajet_id) REFERENCES trajets(id)
);

ALTER TABLE trajet_utilisateur ADD COLUMN role VARCHAR(50) NOT NULL;

ALTER TABLE trajets
ADD COLUMN fumeur BOOLEAN DEFAULT FALSE,
ADD COLUMN animaux BOOLEAN DEFAULT FALSE;

DROP PROCEDURE IF EXISTS CreerTrajet;

DELIMITER $$

CREATE PROCEDURE CreerTrajet(
    IN utilisateur_id INT,
    IN date_depart DATE,
    IN heure_depart TIME,
    IN lieu_depart VARCHAR(255),
    IN date_arrive DATE,
    IN heure_arrive TIME,
    IN lieu_arrive VARCHAR(255),
    IN nb_places INT,
    IN prix_personne DECIMAL(10, 2),
    IN preferences TEXT,
    IN voiture_id INT,
    IN fumeur TINYINT(1),
    IN animaux TINYINT(1)
)
BEGIN
    INSERT INTO trajets 
    (utilisateur_id, date_depart, heure_depart, lieu_depart, date_arrive, heure_arrive, lieu_arrive, nb_places, prix_personne, preferences, voiture_id, fumeur, animaux)
    VALUES 
    (utilisateur_id, date_depart, heure_depart, lieu_depart, date_arrive, heure_arrive, lieu_arrive, nb_places, prix_personne, preferences, voiture_id, fumeur, animaux);
END

DELIMITER ;

ALTER TABLE reservations CHANGE user_id utilisateur_id INT;

ALTER TABLE reservations ADD COLUMN utilisateur_id INT;

CREATE TABLE passagers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    utilisateur_id INT NULL,
    nom_passager VARCHAR(255) NOT NULL,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
);

ALTER TABLE passagers
ADD COLUMN prenom_passager VARCHAR(255) NOT NULL AFTER nom_passager;


INSERT INTO trajets (date_depart, heure_depart, lieu_depart, date_arrive, heure_arrive, lieu_arrive, statut, nb_places, prix_personnes, preferences, fumeur, animaux)
VALUES
('2025-01-07', '08:00:00', 'Paris', '2025-01-07', '15:00:00', 'Lyon', 'disponible', 2, 50.00, 'Vue dégagée, espace confortable', 0, 0);


INSERT INTO trajets (date_depart, heure_depart, lieu_depart, date_arrive, heure_arrive, lieu_arrive, statut, nb_places, prix_personnes, preferences, fumeur, animaux)
VALUES
('2025-01-07', '06:00:00', 'Marseille', '2025-01-07', '09:00:00', 'Nice', 'disponible', 4, 30.00, 'Voyage silencieux, siège près de la fenêtre', 0, 1);


INSERT INTO trajets (date_depart, heure_depart, lieu_depart, date_arrive, heure_arrive, lieu_arrive, statut, nb_places, prix_personnes, preferences, fumeur, animaux)
VALUES
('2025-01-07', '10:00:00', 'Bordeaux', '2025-01-07', '13:00:00', 'Toulouse', '', 3, 45.00, 'Confort et calme', 0, 1);

ALTER TABLE trajets
DROP COLUMN statut;


ALTER TABLE reservations ADD COLUMN statut ENUM('en_attente', 'en_cours', 'termine') DEFAULT 'en_attente';


CREATE TABLE historique (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trajet_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    date_debut_reel DATETIME NOT NULL,
    date_fin_reel DATETIME NOT NULL,
    date_enregistrement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trajet_id) REFERENCES trajets(id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

ALTER TABLE historique MODIFY date_fin_reel DATETIME NULL;

ALTER TABLE trajets ADD COLUMN prix_total DECIMAL(10, 2);

ALTER TABLE trajet_utilisateur DROP COLUMN role;

SELECT h.id, r.id
FROM historique h
LEFT JOIN reservations r ON h.id = r.id;

ALTER TABLE trajets
ADD COLUMN statut ENUM('en cours', 'réservé_en_attente', 'annulé', 'terminé', 'disponible') DEFAULT 'disponible';

DESCRIBE avis;

-- Ajouter un avis avec le statut "en attente"
INSERT INTO avis (commentaires, note, statut, utilisateur_id, trajet_id)
VALUES ('Cet avis est en attente de validation.', 4, 'en attente', 1, 1);

-- Ajouter un avis avec le statut "validé"
INSERT INTO avis (commentaires, note, statut, utilisateur_id, trajet_id)
VALUES ('Cet avis a été validé par un membre de l\'équipe.', 5, 'validé', 2, 2);

-- Ajouter un avis avec le statut "rejeté"
INSERT INTO avis (commentaires, note, statut, utilisateur_id, trajet_id)
VALUES ('Cet avis a été rejeté par un membre de l\'équipe.', 2, 'rejeté', 3, 3);


-- Ajouter un avis avec le statut "en attente" pour l'utilisateur 37
INSERT INTO avis (commentaires, note, statut, utilisateur_id, trajet_id)
VALUES ('Cet avis est en attente de validation.', 4, 'en attente', 37, 1);

-- Ajouter un avis avec le statut "validé" pour l'utilisateur 37
INSERT INTO avis (commentaires, note, statut, utilisateur_id, trajet_id)
VALUES ('Cet avis a été validé par un membre de l\'équipe.', 5, 'validé', 37, 2);

-- Ajouter un avis avec le statut "rejeté" pour l'utilisateur 37
INSERT INTO avis (commentaires, note, statut, utilisateur_id, trajet_id)
VALUES ('Cet avis a été rejeté par un membre de l\'équipe.', 2, 'rejeté', 37, 3);

SHOW DATABASES;

SHOW TABLES;

SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_KEY 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'trajets' AND TABLE_SCHEMA = 'trajets';

DESCRIBE avis;
DESCRIBE historique;

DESCRIBE passagers;

DESCRIBE reservations;

DESCRIBE trajet_utilisateur;

DESCRIBE trajets;

DESCRIBE utilisateurs;

DESCRIBE voitures;

ALTER TABLE utilisateurs 
ADD COLUMN type_acces ENUM('utilisateur', 'employe', 'administrateur') DEFAULT 'utilisateur';
INSERT INTO utilisateurs (nom, prenom, pseudo, email, password, type_acces)
VALUES ('SuperAdmin', 'Admin', 'SuperAdminPseudo', 'testing.projets.siteweb@gmail.com', '$2y$10$7d4qWvBLPeRkxh9/XOGacOhEUDbg.1YcldgpyalwJP/ghvbYL.K0W', 'administrateur');

ALTER TABLE utilisateurs DROP COLUMN role;

ALTER TABLE utilisateurs ADD COLUMN role ENUM('passager', 'chauffeur', 'passager-chauffeur') DEFAULT 'passager';

ALTER TABLE utilisateurs ADD COLUMN suspendu TINYINT DEFAULT 0;

ALTER TABLE avis ADD COLUMN date_validation DATETIME DEFAULT NULL;

SELECT * 
FROM trajet_utilisateur 
WHERE trajet_id = 113 
AND utilisateur_id = 35;


CREATE TABLE admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    user_id INT NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
DESCRIBE avis;

CREATE INDEX idx_lieu_depart ON trajets(lieu_depart);
CREATE INDEX idx_prix_personnes ON trajets(prix_personnes);