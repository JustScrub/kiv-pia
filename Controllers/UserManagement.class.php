<?php

namespace conference\Controllers;

use conference\Models\Session_Model;

class UserManagement extends ALoggedController
{

    public function __construct($twig, $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->view_data["title"] = "Správa uživatelů";
    }

    public function do_stuff()
    {
        $this->logout_process();
        $this->set_session_data();
        $this->logged_check();
        $this->rights_check(2);

        $this->view_data["reason"] = $this->access_denied_reason;
        if(!$this->VIEW){
            $this->VIEW = "UserManagement.view.twig";
            $this->view_data["user_list"] = $this->pdo->get_all_users();
        }
        echo $this->twig->render($this->VIEW,$this->view_data);

    }
}