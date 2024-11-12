<?php

namespace conference\Controllers;

use conference\Models\Session_Model;

abstract class ALoggedController extends AController
{

    public function __construct($twig, $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->view_data["reason"] = "none";
        $this->min_rights = 5;
    }

    public function logged_check(){
        if(!$this->session->is_logged()){
            $this->VIEW = "Access_denied.view.twig";
            $this->view_data["reason"] = "login";
        }
    }

    public function rights_check($rights){
        if(!$this->VIEW and $this->session->get(Session_Model::USER_RIGHTS)>$rights){
            $this->VIEW = "Access_denied.view.twig";
            $this->view_data["reason"] = "rights";
        }
    }

    public function init(){
        parent::init();
        $this->logged_check();
        $this->rights_check($this->min_rights);
    }

}