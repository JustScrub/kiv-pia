<?php

namespace conference\Controllers;

use conference\Models\DB_Model;
use conference\Models\Session_Model;
use Twig\Environment;

abstract class AController
{

    const LOGIN_BUTTON_NAME = "log_but";
    const LOGIN_INP_NAME = "login";
    const PASS_INP_NAME = "pass";

    const LOGOUT_BUTTON_NAME = "logout_but";

    /**
     * @param $twig Environment
     * @param $pdo DB_Model
     */
    public function __construct($twig,$pdo){
        $this->twig = $twig;
        $this->pdo = $pdo;
        $this->session = new Session_Model;
        $this->VIEW = null;
        $this->view_data = array(
            "title" => $_GET["page"] ?? "",
            "bad_login" => false,
            "bad_pass" => false,
            "logged" => false,
            "rights" => 5,
            "user" => ""
        );
    }
    public abstract function do_stuff();

    public function init()
    {
        $this->logout_process();
        $this->login_process();
        $this->set_session_data();
    }

    public function set_session_data(){
        if($this->view_data["logged"] = $this->session->is_logged()){
            $this->view_data["user"] =  $this->session->get(Session_Model::USER_NAME);
            $this->view_data["rights"] = $this->session->get(Session_Model::USER_RIGHTS);
        }
        else{
            $this->view_data["user"] = "";
            $this->view_data["rights"] = 5;
        }
    }

    public function logout_process(){
        if(!isset($_POST[self::LOGOUT_BUTTON_NAME])) return;

        $this->session->logout();
        header('Location: index.php');
    }

    public function login_process(){
        if(!isset($_POST[self::LOGIN_BUTTON_NAME])) return -1;

        $user = $this->pdo->get_user_data($_POST[self::LOGIN_INP_NAME]);
        if(!$user) {
            $this->view_data["bad_login"] = true;
            return DB_Model::UNKNOWN_LOGIN;
        }

        if($this->pdo->verify_user_knowing_hash($_POST[self::PASS_INP_NAME],$user["heslo"]) != DB_Model::SUCCESS)
        {
            $this->view_data["bad_pass"] = true;
            return DB_Model::WRONG_PASSWORD;
        }

        $this->session->login($user);
        return DB_Model::SUCCESS;
    }
}