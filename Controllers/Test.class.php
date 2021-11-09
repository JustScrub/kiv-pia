<?php

namespace conference\Controllers;

use conference\Models\DB_Model;
use Twig\Environment;

class Test extends ALoginController
{
    const VIEW = "Test.view.twig";

    public function do_stuff()
    {
        $this->login_process();
        $this->logout_process();

       echo $this->twig->render(self::VIEW,

           array("title" => "TEST", "user" => $this->session->get("jmeno_a_prijmeni"),
           "logged" => $this->session->is_logged())
       );
    }
}