<?php

namespace conference\Controllers;

use conference\Models\DB_Model;
use conference\Models\Session_Model;

class Register extends AController
{

    public function __construct(\Twig\Environment $twig, DB_model $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->VIEW = "Register.view.twig";
        $this->titles = array("cz" => "Registrace", "en" => "Registration");
    }

    public function do_stuff()
    {
        $this->init();

        // template parameters
        $this->view_data["registered"] = false;
        $this->view_data["same"] = array();

        if($this->session->is_logged()) // cannot register already logged user xd
            $this->view_data["registered"] = true; // when true, the page only congrats the user to registering, without showing register form
        else
            $this->register_process();

        echo $this->twig->render($this->VIEW,$this->view_data);
    }


    /**
     * process the registration form
     */
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