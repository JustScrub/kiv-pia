<?php

// FILES
const ROOT_DIR = __DIR__;
const CONTR_DIR = ROOT_DIR."/Controllers/";
const MODEL_DIR = ROOT_DIR."/Models/";
const VIEW_DIR = ROOT_DIR."/Views/";
const ARTICLES_DIR = ROOT_DIR."/Articles/";
const EXTENSIONS = array(
    ".interface.php", ".class.php", ".abstract.php"
);


// INDEX
const NAMESPACE_ROOT = "conference";

const DEFAULT_PAGE = "uvod";

//TODO: undo unused elems
const CONTROLLER_LIST = array(
    "intro" => array("file_name" => CONTR_DIR."Intro.controller.class.php", "class_name" => "Intro.controller"),
    "test"  => array("file_name" => CONTR_DIR."Test.class.php", "class_name" => \conference\Controllers\Test::class),
    "registrace" => array("file_name" => CONTR_DIR."Register.class.php", "class_name" => \conference\Controllers\Register::class),
    "otp_login" => array("file_name" => CONTR_DIR."OTPLoginController.class.php", "class_name" => \conference\Controllers\OTPLoginController::class),
    "sprava_uzivatelu" => array("file_name" => CONTR_DIR."UserManagement.class.php", "class_name" => \conference\Controllers\UserManagement::class),
    "sprava_adminu" => array("file_name" => CONTR_DIR."AdminManagement.class.php", "class_name" => \conference\Controllers\AdminManagement::class),
    "pridat_clanek" => array("file_name" => CONTR_DIR."AddArticle.class.php", "class_name" => \conference\Controllers\AddArticle::class),
    "moje_clanky" => array("file_name" => CONTR_DIR."MyArticles.class.php", "class_name" => \conference\Controllers\MyArcticles::class),
    "priradit_recenzenta" => array("file_name" => CONTR_DIR."AddReviewer.class.php", "class_name" => \conference\Controllers\AddReviewer::class),
    "recenzovat" => array("file_name" => CONTR_DIR."ReviewArticle.class.php", "class_name" => \conference\Controllers\ReviewArticle::class),
    "rozhodnout_recenze" => array("file_name" => CONTR_DIR."ReviewReview.class.php", "class_name" => \conference\Controllers\ReviewReview::class),
    "clanky" => array("file_name" => CONTR_DIR."AllArticles.class.php", "class_name" => \conference\Controllers\AllArticles::class),
    "uvod" => array("file_name" => CONTR_DIR."Intro.class.php", "class_name" => \conference\Controllers\Intro::class)

);

// DATABASE
const DB_SERVER = "localhost";
const DB_NAME = "kiv_pia";
const DB_LOGIN = "root";
const DB_PASS = "rootroot";

const WSS_HOST = "localhost";
const WSS_PORT = 8765;

const TB_USERS = "uzivatel";
const TB_RIGHTS = "pravo";
const TB_ARTICLE = "clanek";
const TB_REVIEW = "recenzenti";
const TB_API_KEYS = "api_klice"; // key, user_id, expiration -- one key per user
const VW_NEED_REVIEW = "nedostatek_recenzentu";
const VW_AUTHORS_ARTICLES = "autori_clanky";
const VW_API_RIGHTS = "api_prava"; // key, rights, expiration


