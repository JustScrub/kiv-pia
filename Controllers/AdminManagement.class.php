<?php

namespace conference\Controllers;

use conference\Models\Session_Model;

class AdminManagement extends ALoggedController
{

    public function __construct($twig, $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->view_data["title"] = "Správa adminů";
    }

    public function do_stuff()
    {
        $this->logout_process();
        $this->set_session_data();
        $this->logged_check();
        $this->rights_check(1);

        $this->view_data["reason"] = $this->access_denied_reason;
        if(!$this->VIEW){
            $this->VIEW = "AdminManagement.view.twig";
            $this->view_data["user_list"] = $this->pdo->get_all_admins();
        }
        //var_dump($this->view_data);
        echo $this->twig->render($this->VIEW,$this->view_data);

    }
}