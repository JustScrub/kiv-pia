<?php
namespace conference;

const ROOT_DIR = "C:/xampp/htdocs/SEM";
const CONTR_DIR = ROOT_DIR."/Controllers/";
const MODEL_DIR = ROOT_DIR."/Models/";
const VIEW_DIR = ROOT_DIR."/Views/";

const DEFAULT_PAGE = "test";

const CONTROLLER_LIST = array(
    "intro" => array("file_name" => CONTR_DIR."Intro.controller.class.php", "class_name" => "Intro.controller"),
    "test"  => array("file_name" => CONTR_DIR."Test.controller.class.php", "class_name" => "Test")
);


?>