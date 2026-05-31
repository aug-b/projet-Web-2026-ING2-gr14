
CREATE DATABASE IF NOT EXISTS smartcampus;
USE smartcampus;

CREATE TABLE utilisateur (
    id_utilisateur INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'enseignant', 'eleve') NOT NULL,
    telephone VARCHAR(20),
    date_de_naissance DATE,
    photo VARCHAR(255)
);

CREATE TABLE admin (
    id_utilisateur INT PRIMARY KEY,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
);

CREATE TABLE eleve (
    id_utilisateur INT PRIMARY KEY,
    numero_eleve VARCHAR(50) UNIQUE NOT NULL,
    niveau_scolaire VARCHAR(100),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
);

CREATE TABLE enseignant (
    id_utilisateur INT PRIMARY KEY,
    specialite VARCHAR(100),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
);

CREATE TABLE classe (
    id_classe INT PRIMARY KEY AUTO_INCREMENT,
    nom_classe VARCHAR(100) NOT NULL,
    niveau VARCHAR(100)
);

CREATE TABLE cours (
    id_cours INT PRIMARY KEY AUTO_INCREMENT,
    matiere VARCHAR(100) NOT NULL,
    id_enseignant INT NOT NULL,
    FOREIGN KEY (id_enseignant) REFERENCES enseignant(id_utilisateur) ON DELETE CASCADE
);

CREATE TABLE suivre (
    id_classe INT NOT NULL,
    id_cours INT NOT NULL,
    PRIMARY KEY (id_classe, id_cours),
    FOREIGN KEY (id_classe) REFERENCES classe(id_classe) ON DELETE CASCADE,
    FOREIGN KEY (id_cours) REFERENCES cours(id_cours) ON DELETE CASCADE
);

CREATE TABLE inscription (
    id_inscription INT PRIMARY KEY AUTO_INCREMENT,
    date_inscription DATE,
    statut VARCHAR(50),
    id_eleve INT NOT NULL,
    id_classe INT NOT NULL,
    UNIQUE (id_eleve, id_classe),
    FOREIGN KEY (id_eleve) REFERENCES eleve(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_classe) REFERENCES classe(id_classe) ON DELETE CASCADE
);

CREATE TABLE note (
    id_note INT PRIMARY KEY AUTO_INCREMENT,
    valeur DECIMAL(5,2),
    type VARCHAR(100),
    date_note DATE,
    id_eleve INT NOT NULL,
    id_cours INT NOT NULL,
    FOREIGN KEY (id_eleve) REFERENCES eleve(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_cours) REFERENCES cours(id_cours) ON DELETE CASCADE
);

CREATE TABLE presence (
    id_presence INT PRIMARY KEY AUTO_INCREMENT,
    date_presence DATE,
    statut ENUM('present','absent','retard') NOT NULL,
    id_eleve INT NOT NULL,
    id_cours INT NOT NULL,
    id_creneau INT NOT NULL
);

CREATE TABLE emploi_du_temps (
    id_creneau INT PRIMARY KEY AUTO_INCREMENT,
    jour ENUM('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi') NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    salle VARCHAR(100) NOT NULL,
    id_cours INT NOT NULL,
    id_classe INT NOT NULL,
    FOREIGN KEY (id_cours) REFERENCES cours(id_cours) ON DELETE CASCADE,
    FOREIGN KEY (id_classe) REFERENCES classe(id_classe) ON DELETE CASCADE
);

CREATE TABLE message (
    id_message INT PRIMARY KEY AUTO_INCREMENT,
    contenu TEXT,
    date_message DATETIME,
    id_expediteur INT NOT NULL,
    id_destinataire INT NOT NULL,
    FOREIGN KEY (id_expediteur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_destinataire) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
);

CREATE TABLE notification (
    id_notification INT PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(100),
    contenu TEXT,
    date_notification DATETIME,
    id_utilisateur INT NOT NULL,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
);

CREATE TABLE utilisateur_en_attente (
    id_attente INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    date_de_naissance DATE,
    classe VARCHAR(20),
    photo VARCHAR(255),
    photo_id VARCHAR(255),
    role ENUM('admin', 'enseignant', 'eleve') NOT NULL,
    date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('en_attente', 'accepte', 'refuse') DEFAULT 'en_attente'
);

INSERT INTO utilisateur
(nom, prenom, email, mot_de_passe, role, telephone, date_de_naissance, photo)
VALUES
('Martin','Admin','admin@smartcampus.fr','admin123','admin','0600000000','1980-01-01',''),
('Dubois','Pierre','p.dubois@smartcampus.fr','admin123','admin','0600000099','1978-04-15',''),

('Dupont','Jean','j.dupont@smartcampus.fr','prof123','enseignant','0600000001','1985-05-10',''),
('Durand','Sophie','s.durand@smartcampus.fr','prof123','enseignant','0600000002','1987-08-15',''),
('Leroy','Marc','m.leroy@smartcampus.fr','prof123','enseignant','0600000003','1982-04-18',''),
('Moreau','Claire','c.moreau@smartcampus.fr','prof123','enseignant','0600000004','1990-11-22',''),
('Bernier','Thomas','t.bernier@smartcampus.fr','prof123','enseignant','0600000005','1984-01-10',''),
('Petit','Sarah','sarah.petit@smartcampus.fr','prof123','enseignant','0600000006','1988-09-02',''),

('Bernard','Lucas','lucas@smartcampus.fr','eleve123','eleve','0600000010','2008-03-12',''),
('Petit','Emma','emma@smartcampus.fr','eleve123','eleve','0600000011','2007-06-20',''),
('Robert','Hugo','hugo@smartcampus.fr','eleve123','eleve','0600000012','2006-09-30',''),
('Garcia','Noah','noah@smartcampus.fr','eleve123','eleve','0600000013','2008-01-14',''),
('Roux','Lina','lina@smartcampus.fr','eleve123','eleve','0600000014','2007-12-02',''),
('Morel','Nathan','nathan@smartcampus.fr','eleve123','eleve','0600000015','2006-02-25',''),
('Fournier','Chloe','chloe@smartcampus.fr','eleve123','eleve','0600000016','2008-07-08','');

INSERT INTO admin (id_utilisateur) VALUES
(1),
(2);

INSERT INTO enseignant (id_utilisateur, specialite) VALUES
(3,'Mathématiques'),
(4,'Informatique'),
(5,'Physique-Chimie'),
(6,'Français'),
(7,'Histoire-Géographie'),
(8,'Anglais');

INSERT INTO eleve (id_utilisateur, numero_eleve, niveau_scolaire) VALUES
(9,'E001','Seconde'),
(10,'E002','Première'),
(11,'E003','Terminale'),
(12,'E004','Seconde'),
(13,'E005','Première'),
(14,'E006','Terminale'),
(15,'E007','Seconde');

INSERT INTO classe (nom_classe, niveau) VALUES
('2nde A','Seconde'),
('2nde B','Seconde'),
('1ère A','Première'),
('1ère B','Première'),
('Terminale A','Terminale'),
('Terminale B','Terminale');

INSERT INTO cours (matiere, id_enseignant) VALUES
('Mathématiques',3),
('Informatique',4),
('Physique-Chimie',5),
('Français',6),
('Histoire-Géographie',7),
('Anglais',8),
('SVT',5),
('Philosophie',6);

INSERT INTO inscription (date_inscription, statut, id_eleve, id_classe) VALUES
(CURDATE(),'active',9,1),
(CURDATE(),'active',12,1),
(CURDATE(),'active',15,2),
(CURDATE(),'active',10,3),
(CURDATE(),'active',13,3),
(CURDATE(),'active',11,5),
(CURDATE(),'active',14,5);

INSERT INTO suivre (id_classe, id_cours) VALUES
(1,1),(1,4),(1,5),(1,6),(1,7),
(2,1),(2,4),(2,5),(2,6),(2,7),
(3,1),(3,2),(3,3),(3,4),(3,6),
(4,1),(4,2),(4,3),(4,4),(4,6),
(5,1),(5,2),(5,3),(5,6),(5,8),
(6,1),(6,2),(6,3),(6,6),(6,8);

INSERT INTO emploi_du_temps
(jour, heure_debut, heure_fin, salle, id_cours, id_classe)
VALUES
('Lundi','08:00:00','10:00:00','A101',1,1),
('Lundi','10:00:00','12:00:00','B201',4,1),
('Mardi','08:00:00','10:00:00','Lab1',7,1),
('Mardi','10:00:00','12:00:00','A105',6,1),
('Mercredi','08:00:00','10:00:00','A102',5,1),

('Lundi','08:00:00','10:00:00','Info1',2,3),
('Lundi','10:00:00','12:00:00','A201',1,3),
('Jeudi','08:00:00','10:00:00','Lab2',3,3),
('Jeudi','10:00:00','12:00:00','B202',4,3),
('Vendredi','08:00:00','10:00:00','A205',6,3),

('Lundi','14:00:00','16:00:00','A301',8,5),
('Mardi','08:00:00','10:00:00','Info2',2,5),
('Mercredi','10:00:00','12:00:00','A302',1,5),
('Jeudi','14:00:00','16:00:00','Lab3',3,5),
('Vendredi','10:00:00','12:00:00','A303',6,5);

INSERT INTO note
(valeur, type, date_note, id_eleve, id_cours)
VALUES
(15.50,'Contrôle','2026-05-10',9,1),
(13.00,'Rédaction','2026-05-12',9,4),
(16.00,'Oral','2026-05-15',9,6),

(12.50,'Contrôle','2026-05-10',12,1),
(14.00,'TP','2026-05-14',12,7),

(17.00,'Contrôle','2026-05-10',15,1),
(11.50,'Rédaction','2026-05-12',15,4),

(16.00,'Contrôle','2026-05-10',10,1),
(14.50,'Projet','2026-05-20',10,2),
(13.00,'TP','2026-05-21',10,3),

(12.00,'Contrôle','2026-05-10',13,1),
(15.00,'Projet','2026-05-20',13,2),

(13.50,'Contrôle','2026-05-11',11,1),
(15.50,'Philosophie','2026-05-22',11,8),
(14.00,'TP','2026-05-18',11,2),

(11.00,'Contrôle','2026-05-11',14,1),
(16.50,'Philosophie','2026-05-22',14,8);

INSERT INTO presence
(date_presence, statut, id_eleve, id_cours)
VALUES
('2026-05-25','present',9,1),
('2026-05-25','retard',12,1),
('2026-05-25','absent',15,1),

('2026-05-26','present',9,7),
('2026-05-26','absent',12,7),
('2026-05-26','present',15,7),

('2026-05-27','present',10,2),
('2026-05-27','retard',13,2),

('2026-05-28','present',10,3),
('2026-05-28','absent',13,3),

('2026-05-29','present',11,8),
('2026-05-29','retard',14,8),

('2026-05-29','present',11,2),
('2026-05-29','absent',14,2);

INSERT INTO message
(contenu, date_message, id_expediteur, id_destinataire)
VALUES
('Bonjour, pensez à réviser le contrôle de mathématiques.', NOW(), 3, 9),
('Merci monsieur, je vais réviser.', NOW(), 9, 3),
('Le devoir de français est à rendre vendredi.', NOW(), 6, 10),
('Votre absence a bien été enregistrée.', NOW(), 2, 13);

INSERT INTO notification
(type, contenu, date_notification, id_utilisateur)
VALUES
('note','Une nouvelle note a été ajoutée.', NOW(), 9),
('presence','Une absence a été enregistrée.', NOW(), 13),
('cours','Un cours a été ajouté à votre emploi du temps.', NOW(), 11),
('message','Vous avez reçu un nouveau message.', NOW(), 10);

INSERT INTO utilisateur_en_attente
(nom, prenom, email, mot_de_passe, telephone, date_de_naissance, classe, photo, photo_id, role)
VALUES
('Mercier','Julie','julie.mercier@gmail.com','temp123','0611111111','2008-05-12','2nde A','','','eleve'),
('Renaud','Tom','tom.renaud@gmail.com','temp123','0622222222','2007-11-03','1ère A','','','eleve'),
('Leclerc','Camille','camille.leclerc@gmail.com','temp123','0633333333','2006-02-20','Terminale A','','','eleve'),
('Girard','Antoine','antoine.girard@gmail.com','temp123','0644444444','1986-08-15','','','','enseignant'),
('Faure','Marie','marie.faure@gmail.com','temp123','0655555555','1989-01-10','','','','enseignant'),
('Robert','Nicolas','nicolas.robert@gmail.com','temp123','0666666666','1980-07-07','','','','admin');