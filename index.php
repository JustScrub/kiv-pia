<?php
namespace conference;
require "config.php";

$controller_info = isset($_GET["page"]) && array_key_exists($_GET["page"],CONTROLLER_LIST) ?
                 (CONTROLLER_LIST[$_GET["page"]]) :
                 (CONTROLLER_LIST[DEFAULT_PAGE]);
require $controller_info["file_name"];

$controller = new $controller_info["class_name"];
$controller->do_stuff();
?>