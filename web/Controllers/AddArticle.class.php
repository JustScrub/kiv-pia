<?php

namespace conference\Controllers;

use conference\Models\Session_Model;
use conference\Models\DB_Model;

class AddArticle extends ALoggedController
{

    public function __construct(\Twig\Environment $twig, DB_model $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->min_rights = 4; // authors and higher

        // specific view template parameters
        $this->view_data["bad_file_type"] = false;
        $this->view_data["form_success"] = false;

        $this->titles = array("cz" => "Přidat článek", "en" => "Add article");
    }

    public function do_stuff()
    {
        $this->init();
        $this->process_form();

        if(!$this->VIEW) $this->VIEW = "AddArticle.view.twig";
        echo $this->twig->render($this->VIEW,$this->view_data);
    }

    /**
     * process the add article form
     * Articles in DB are just meta-info about the articles
     * the actual file is stored in the FS to keep DB records small
     */
    private function process_form(){
        if(!isset($_POST["submit"])) return;

        //has to be a PDF
        if(!str_ends_with( $_FILES["fl"]["name"],".pdf")){
            $this->view_data["bad_file_type"] = true;
            return;
        }

        $filename = hash("sha256",$this->session->get(Session_Model::USER_ID) . $_POST["nazev"] . random_bytes(4),true);
        $filename = base64_encode($filename).".pdf";
        $filename = strtr($filename,"+/","-_");
        $filename = str_replace("=","",$filename);

        if(
            $this->pdo->addArticle( $this->session->get(Session_Model::USER_ID),
                                $_POST["nazev"],
                                $filename,
                                $_POST["klicova_slova"],
                                $_POST["popis"]
            )
        ){
            move_uploaded_file($_FILES["fl"]["tmp_name"],ARTICLES_DIR.$filename);
            $this->view_data["form_success"] = true;
        }


    }
}