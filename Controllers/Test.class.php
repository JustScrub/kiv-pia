<?php

namespace conference\Controllers;

use conference\Models\DB_Model;
use Twig\Environment;

class Test implements IController
{
    const VIEW = "Test.view.twig";

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @param $twig Environment
     * @param $pdo DB_Model
     */
    public function __construct($twig,$pdo)
    {
        $this->twig = $twig;
    }

    public function do_stuff()
    {
       echo $this->twig->render(self::VIEW,array("title" => "TEST"));
    }
}