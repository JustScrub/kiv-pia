<?php

namespace conference\Controllers;

class OTPLoginController extends AController
{

    public function __construct($twig, $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->VIEW = "OTPLoginController.view.twig";
        $this->titles = array("cz" => "OTP přihlášení", "en" => "OTP Login");
    }

    public function do_stuff()
    {
        $this->init();
        $otp = $bin2hex(random_bytes(4));
        $this->view_data["otp"] = $otp;
        $this->view_data["ws_client"] ="ws://".WSS_HOST.":".WSS_PORT;
        echo $this->twig->render($this->VIEW,$this->view_data);
    }
}