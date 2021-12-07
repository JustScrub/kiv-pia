<?php

namespace conference\Controllers;

use conference\Models\DB_Model;
use conference\Models\Session_Model;

abstract class ALoginController extends AController
{

    public function login_process(){
        if(!isset($_POST[parent::LOGIN_BUTTON_NAME])) return -1;

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