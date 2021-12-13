DROP TABLE IF EXISTS `pravo` ;

CREATE TABLE IF NOT EXISTS `pravo` (
    `id_pravo` INT NOT NULL,
    `nazev` VARCHAR(20) NOT NULL,
    `vaha` INT NOT NULL,
    PRIMARY KEY (`id_pravo`))
    ENGINE = InnoDB
    DEFAULT CHARACTER SET = utf8
    COLLATE = utf8_czech_ci;

START TRANSACTION;
INSERT INTO `pravo` (`id_pravo`, `nazev`, `vaha`) VALUES (1, 'SuperAdmin', 20);
INSERT INTO `pravo` (`id_pravo`, `nazev`, `vaha`) VALUES (2, 'Admin', 10);
INSERT INTO `pravo` (`id_pravo`, `nazev`, `vaha`) VALUES (3, 'Recenzent', 5);
INSERT INTO `pravo` (`id_pravo`, `nazev`, `vaha`) VALUES (4, 'Autor', 2);
COMMIT;

DROP TABLE IF EXISTS `uzivatel` ;

CREATE TABLE IF NOT EXISTS `uzivatel` (
    `id_uzivatel` INT NOT NULL AUTO_INCREMENT,
    `id_pravo` INT NOT NULL DEFAULT 3,
    `jmeno` VARCHAR(60) NOT NULL,
    `prijmeni` VARCHAR(60) NOT NULL,
    `login` VARCHAR(30) UNIQUE NOT NULL,
    `heslo` VARCHAR(60) NOT NULL,
    `email` VARCHAR(35) UNIQUE NOT NULL,
    PRIMARY KEY (`id_uzivatel`),
    INDEX `fk_uzivatele_prava_idx` (`id_pravo` ASC),
    CONSTRAINT `fk_uzivatele_prava`
    FOREIGN KEY (`id_pravo`)
    REFERENCES `pravo` (`id_pravo`))
    ENGINE = InnoDB
    DEFAULT CHARACTER SET = utf8
    COLLATE = utf8_czech_ci;

DROP TABLE IF EXISTS `clanek` ;

CREATE TABLE IF NOT EXISTS `clanek` (
    `id_clanek` INT PRIMARY KEY AUTO_INCREMENT,
    `id_autor`  INT NOT NULL,
    `nazev`     VARCHAR(100) NOT NULL,
    `nazev_souboru` VARCHAR(100) UNIQUE NOT NULL,
    `klicova_slova` VARCHAR(200),
    `popis` VARCHAR(2000),
    `schvalen` INT NOT NULL CHECK (`schvalen` in (0,1,2)), -- 0: neposouzen, 1: schvalen, 2: zamitnut
    `datum_schvaleni` DATE,
    INDEX `fk_clanku_uzivatele_idx` (`id_autor` ASC),
    CONSTRAINT `fk_clanku_uzivatele`
    FOREIGN KEY (`id_autor`)
    REFERENCES  `uzivatel` (`id_uzivatel`))
    ENGINE = InnoDB
    DEFAULT CHARACTER SET = utf8
    COLLATE = utf8_czech_ci;

DROP TABLE IF EXISTS `recenzenti` ;

CREATE TABLE IF NOT EXISTS `recenzenti` (
    `id_clanek` INT NOT NULL,
    `id_recenzent` INT NOT NULL,
    `hodnoceni` INT CHECK (`hodnoceni` > 0 AND `hodnoceni` < 10),
    `poznamky` VARCHAR(200),
    CONSTRAINT `pk_recenzenti`
    PRIMARY KEY (`id_clanek`,`id_recenzent`),
    CONSTRAINT  `fk_recenzent_clanek`
    FOREIGN KEY (`id_clanek`)
    REFERENCES `clanek` (`id_clanek`),
    INDEX `fk_recenzent_uzivatel_idx` (`id_recenzent`),
    CONSTRAINT  `fk_recenzent_uzivatel`
    FOREIGN KEY (`id_recenzent`)
    REFERENCES `uzivatel` (`id_uzivatel`)
    )
    ENGINE = InnoDB
    DEFAULT CHARACTER SET = utf8
    COLLATE = utf8_czech_ci;

DROP VIEW IF EXISTS `nedostatek_recenzentu`;

CREATE VIEW IF NOT EXISTS `nedostatek_recenzentu` AS
    SELECT * from clanek c where 3 > (SELECT count(r.id_recenzent) from recenzenti r WHERE r.id_clanek = c.id_clanek);

DROP VIEW IF EXISTS `autori_clanky`;
CREATE VIEW IF NOT EXISTS `autori_clanky` AS
    SELECT * FROM uzivatel u, clanek c WHERE u.id_uzivatel = c.id_autor;