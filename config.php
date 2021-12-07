<?php

// FILES
const ROOT_DIR = "C:/xampp/htdocs/SEM";
const CONTR_DIR = ROOT_DIR."/Controllers/";
const MODEL_DIR = ROOT_DIR."/Models/";
const VIEW_DIR = ROOT_DIR."/Views/";
const EXTENSIONS = array(
    ".interface.php", ".class.php", ".abstract.php"
);


// INDEX
const NAMESPACE_ROOT = "conference";

const DEFAULT_PAGE = "test";

//TODO: undo unused elems
const CONTROLLER_LIST = array(
    "intro" => array("file_name" => CONTR_DIR."Intro.controller.class.php", "class_name" => "Intro.controller"),
    "test"  => array("file_name" => CONTR_DIR."Test.class.php", "class_name" => \conference\Controllers\Test::class),
    "registrace" => array("file_name" => CONTR_DIR."Register.class.php", "class_name" => \conference\Controllers\Register::class),
    "sprava_uzivatelu" => array("file_name" => CONTR_DIR."UserManagement.class.php", "class_name" => \conference\Controllers\UserManagement::class),
    "sprava_adminu" => array("file_name" => CONTR_DIR."AdminManagement.class.php", "class_name" => \conference\Controllers\AdminManagement::class)
);

// DATABASE
const DB_SERVER = "localhost";
const DB_NAME = "kiv_web";
const DB_LOGIN = "root";
const DB_PASS = "rootroot";

const TB_USERS = "uzivatel";
const TB_RIGHTS = "pravo";
const TB_ARTICLE = "clanek";
const TB_REVIEW = "recentzenti";

