<?php
require "config.php";
require_once "Composer/vendor/autoload.php";

spl_autoload_register(
    function ($class_name){

        $file_name = str_replace(NAMESPACE_ROOT,ROOT_DIR,$class_name);


        foreach(EXTENSIONS as $ext){
            if(file_exists($file_name.$ext)){
                $file_name .= $ext;
                break;
            }
        }

        require_once $file_name;

    }
);

$controller_info = isset($_GET["page"]) && array_key_exists($_GET["page"],CONTROLLER_LIST) ?
                 (CONTROLLER_LIST[$_GET["page"]]) :
                 (CONTROLLER_LIST[DEFAULT_PAGE]);


$loader = new Twig\Loader\FilesystemLoader("Views/");
$twig = new Twig\Environment($loader, ["debug" => false]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$db = new \conference\Models\DB_Model;

$controller = new $controller_info["class_name"]($twig,$db);
$controller->do_stuff();

?>