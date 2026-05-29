CREATE DATABASE smartcampus;
USE smartcampus;


CREATE TABLE utilisateur (
    id_utilisateur INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    date_de_naissance DATE,
    photo VARCHAR(255)
);


CREATE TABLE admin (
    id_utilisateur INT PRIMARY KEY,
    
    FOREIGN KEY (id_utilisateur)
    REFERENCES utilisateur(id_utilisateur)
    ON DELETE CASCADE
);


CREATE TABLE eleve (
    id_utilisateur INT PRIMARY KEY,
    numero_eleve VARCHAR(50) UNIQUE NOT NULL,
    niveau_scolaire VARCHAR(100),

    FOREIGN KEY (id_utilisateur)
    REFERENCES utilisateur(id_utilisateur)
    ON DELETE CASCADE
);


CREATE TABLE enseignant (
    id_utilisateur INT PRIMARY KEY,
    specialite VARCHAR(100),

    FOREIGN KEY (id_utilisateur)
    REFERENCES utilisateur(id_utilisateur)
    ON DELETE CASCADE
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

    FOREIGN KEY (id_enseignant)
    REFERENCES enseignant(id_utilisateur)
    ON DELETE CASCADE
);


CREATE TABLE suivre (
    id_classe INT,
    id_cours INT,

    PRIMARY KEY (id_classe, id_cours),

    FOREIGN KEY (id_classe)
    REFERENCES classe(id_classe)
    ON DELETE CASCADE,

    FOREIGN KEY (id_cours)
    REFERENCES cours(id_cours)
    ON DELETE CASCADE
);


CREATE TABLE inscription (
    id_inscription INT PRIMARY KEY AUTO_INCREMENT,
    date_inscription DATE,
    statut VARCHAR(50),

    id_eleve INT NOT NULL,
    id_classe INT NOT NULL,

    FOREIGN KEY (id_eleve)
    REFERENCES eleve(id_utilisateur)
    ON DELETE CASCADE,

    FOREIGN KEY (id_classe)
    REFERENCES classe(id_classe)
    ON DELETE CASCADE
);

CREATE TABLE note (
    id_note INT PRIMARY KEY AUTO_INCREMENT,
    valeur DECIMAL(5,2),
    type VARCHAR(100),
    date_note DATE,

    id_eleve INT NOT NULL,
    id_cours INT NOT NULL,

    FOREIGN KEY (id_eleve)
    REFERENCES eleve(id_utilisateur)
    ON DELETE CASCADE,

    FOREIGN KEY (id_cours)
    REFERENCES cours(id_cours)
    ON DELETE CASCADE
);


CREATE TABLE presence (
    id_presence INT PRIMARY KEY AUTO_INCREMENT,
    date_presence DATE,
    statut VARCHAR(50),

    id_eleve INT NOT NULL,
    id_cours INT NOT NULL,

    FOREIGN KEY (id_eleve)
    REFERENCES eleve(id_utilisateur)
    ON DELETE CASCADE,

    FOREIGN KEY (id_cours)
    REFERENCES cours(id_cours)
    ON DELETE CASCADE
);


CREATE TABLE emploi_du_temps (
    id_creneau INT PRIMARY KEY AUTO_INCREMENT,
    jour VARCHAR(50),
    heure_debut TIME,
    heure_fin TIME,
    salle VARCHAR(100),

    id_cours INT NOT NULL,

    FOREIGN KEY (id_cours)
    REFERENCES cours(id_cours)
    ON DELETE CASCADE
);

CREATE TABLE message (
    id_message INT PRIMARY KEY AUTO_INCREMENT,
    contenu TEXT,
    date_message DATETIME,

    id_expediteur INT NOT NULL,
    id_destinataire INT NOT NULL,

    FOREIGN KEY (id_expediteur)
    REFERENCES utilisateur(id_utilisateur)
    ON DELETE CASCADE,

    FOREIGN KEY (id_destinataire)
    REFERENCES utilisateur(id_utilisateur)
    ON DELETE CASCADE
);


CREATE TABLE notification (
    id_notification INT PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(100),
    contenu TEXT,
    date_notification DATETIME,

    id_utilisateur INT NOT NULL,

    FOREIGN KEY (id_utilisateur)
    REFERENCES utilisateur(id_utilisateur)
    ON DELETE CASCADE
);

CREATE TABLE utilisateur_en_attente (
    id_attente INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'enseignant', 'eleve') NOT NULL,
    telephone VARCHAR(20),
    date_de_naissance DATE,
    photo VARCHAR(255),
    date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('en_attente', 'accepte', 'refuse') DEFAULT 'en_attente'
);