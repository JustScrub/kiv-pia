<?php

namespace conference\Controllers;

abstract class ALoggedController extends AController
{

    public function __construct($twig, $pdo)
    {
        parent::__construct($twig, $pdo);
        if(!$this->session->is_logged()){
            $this->VIEW = "Access_denied.view.twig";
            $this->access_denied_reason = "login";
        }
    }

}