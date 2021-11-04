<?php

namespace conference\Controllers;

use conference\Models\DB_Model;
use Twig\Environment;

interface IController
{
    /**
     * @param $twig Environment
     * @param $pdo DB_Model
     */
    public function __construct($twig,$pdo);
    public function do_stuff();
}