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
    }
    public abstract function do_stuff();

    public function logout_process(){
        if(!isset($_POST[self::LOGOUT_BUTTON_NAME])) return;

        $this->session->logout();
    }
}