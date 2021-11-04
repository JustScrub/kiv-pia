<?php

namespace conference\Controllers;


class Test implements IController
{
    public function __construct()
    {
        ob_start();
        require_once VIEW_DIR."Test.view.php";
        $this->html = ob_get_clean();
    }

    public function do_stuff()
    {
        echo $this->html;
    }
}