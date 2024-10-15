<?php

namespace conference\Controllers;

use conference\Models\Session_Model;

class AddArticle extends ALoggedController
{

    public function __construct($twig, $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->min_rights = 4;
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

    private function process_form(){
        if(!isset($_POST["submit"])) return;

        //has to be a PDF
        if(!str_ends_with( $_FILES["fl"]["name"],".pdf")){
            $this->view_data["bad_file_type"] = true;
            return;
        }

        //replace spaces for '+' and remove forbidden file name chars
        $filename = str_replace(" ","+",preg_replace("~[<>:/|#{}=?\"*\\\\]~","",$_POST["nazev"]));
        //add author id and extension
        $filename = $this->session->get(Session_Model::USER_ID) . "-" . $filename . ".pdf";
        //just to be sure
        $filename = basename($filename);

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