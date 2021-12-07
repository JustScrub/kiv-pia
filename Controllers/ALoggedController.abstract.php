<?php

namespace conference\Controllers;

use conference\Models\Session_Model;

abstract class ALoggedController extends AController
{

    public function __construct($twig, $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->access_denied_reason = "none";
    }

    public function logged_check(){
        if(!$this->session->is_logged()){
            $this->VIEW = "Access_denied.view.twig";
            $this->access_denied_reason = "login";
        }
    }

    public function rights_check($rights){
        if(!$this->VIEW and $this->session->get(Session_Model::USER_RIGHTS)>$rights){
            $this->VIEW = "Access_denied.view.twig";
            $this->access_denied_reason = "rights";
        }
    }

}