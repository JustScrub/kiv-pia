<?php

namespace conference\Controllers;

use conference\Models\DB_Model;
use conference\Models\Session_Model;

class Register extends AController
{

    public function __construct($twig, $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->VIEW = "Register.view.twig";
    }

    public function do_stuff()
    {
        $this->init();

        $this->view_data["registered"] = false;
        $this->view_data["same"] = array();


        if($this->session->is_logged())
            $this->view_data["registered"] = true;
        else
            $this->register_process();

        echo $this->twig->render($this->VIEW,$this->view_data);
    }


    public function register_process(){
        if(!isset($_POST["reg_but"])) {
            $this->view_data["registered"] = false;
            return;
        }

        // check for duplicity of existing email or login
        if($this->pdo->get_user_data($_POST["login"])) array_push( $this->view_data["same"],"login" );
        if($this->pdo->get_user_data($_POST["email"])) array_push( $this->view_data["same"],"email" );
        if(count($this->view_data["same"])>0) return;

        $this->pdo->register($_POST["fname"],$_POST["sname"],$_POST["login"],$_POST["email"],$_POST["pwd"]);

        $this->session->login($this->pdo->getSessionLoginInfo($_POST["login"]));
        $this->view_data["registered"] = true;

    }
}