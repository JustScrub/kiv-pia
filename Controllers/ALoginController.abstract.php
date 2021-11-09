<?php

namespace conference\Controllers;

use conference\Models\Session_Model;

abstract class ALoginController extends AController
{
    public function login_process(){
        if(!isset($_POST[parent::LOGIN_BUTTON_NAME])) return;

       // $user_data = $this->pdo->retrieve_user_data($_POST[self::LOGIN_INP_NAME],$_POST[self::PASS_INP_NAME]);

        $user_data = array(
            "id_uzivatel" => 3,
            "jmeno" => $_POST[parent::LOGIN_INP_NAME],
            "prijmeni" => "karle",
            "pravo" => 4
        );

        $this->session->login($user_data);
    }

}