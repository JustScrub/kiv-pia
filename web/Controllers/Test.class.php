<?php

namespace conference\Controllers;

use conference\Models\DB_Model;
use Twig\Environment;

class Test extends AController
{
    public function __construct(Twig\Environment $twig, DB_model $pdo)
     {
         parent::__construct($twig, $pdo);
         $this->VIEW = "Test.view.twig";
     }

    public function do_stuff()
    {
        $this->login_process();
        $this->logout_process();

       echo $this->twig->render($this->VIEW,

           array("title" => "TEST", "user" => $this->session->get("jmeno_a_prijmeni"),
           "rights" => $this->session->get("pravo"),
           "logged" => $this->session->is_logged())
       );
    }
}