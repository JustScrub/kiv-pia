<?php

namespace conference\Controllers;

class OTPLoginController extends AController
{

    public function __construct(Twig\Environment $twig, DB_model $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->VIEW = "OTPLoginView.view.twig";
        $this->titles = array("cz" => "OTP přihlášení", "en" => "OTP Login");
    }

    public function do_stuff()
    {
        $this->init();
        $this->view_data["ws_client"] ="ws://".WSS_HOST.":".WSS_PORT;
        $this->view_data["error"] = false;
        
        if(isset($_POST["otp-submit"])){
            $otp = $_POST["otp-token"];
            $user_id = $_POST["otp-user-id"];
            $signature = $_POST["otp-signature"];

            # validate the signatrue
            $key = $this->pdo->get_api_key_by_id($user_id);
            if(!$key){
                $this->view_data["error"] = true;
                $this->view_data["err_reason_en"] = "User not found";
                $this->view_data["err_reason_cz"] = "Uživatel nenalezen";
                echo $this->twig->render($this->VIEW,$this->view_data);
                return;
            }
            $key = $key[0]["klic"];
            $sig = base64_encode(hash_hmac("sha256",$otp,$key,true));
            if($sig == $signature){
                $user = $this->pdo->select_query(TB_USERS, array($user_id), "id_uzivatel=?")[0];
                $this->session->login($user);
                header("Location: index.php?page=uvod");
                return;
            }
            $this->view_data["error"] = true;
            $this->view_data["err_reason_en"] = "Invalid incoming verification code from API";
            $this->view_data["err_reason_cz"] = "Nesprávný příchozí verifikační kód z API";
        }
        $otp = bin2hex(random_bytes(4));
        $this->view_data["otp"] = $otp;
        echo $this->twig->render($this->VIEW,$this->view_data);
    }
}