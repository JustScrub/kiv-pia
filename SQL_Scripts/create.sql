DROP TABLE IF EXISTS `pravo` ;

CREATE TABLE IF NOT EXISTS `pravo` (
    `id_pravo` INT NOT NULL,
    `nazev` VARCHAR(20) NOT NULL,
    `vaha` INT NOT NULL,
    PRIMARY KEY (`id_pravo`))
    ENGINE = InnoDB
    DEFAULT CHARACTER SET = utf8
    COLLATE = utf8_czech_ci;

BEGIN TRANSACTION `pravo_insert`;
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
    `login` VARCHAR(30) NOT NULL,
    `heslo` VARCHAR(60) NOT NULL,
    `email` VARCHAR(35) NOT NULL,
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
    `klicova_slova` VARCHAR(200),
    `popis` VARCHAR(2000),
    `schvalen` BOOL,
    INDEX `fk_clanku_uzivatele_idx` (`id_autor` ASC),
    CONSTRAINT `fk_clanku_uzivatele`
    FOREIGN KEY (`id_autor`)
    REFERENCES  `uzivatel` (`id_uzivatel`))
    ENGINE = InnoDB
    DEFAULT CHARACTER SET = utf8
    COLLATE = utf8_czech_ci;


