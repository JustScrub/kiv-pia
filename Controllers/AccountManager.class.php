<?php

namespace conference\Controllers;

class AccountManager extends ALoggedController
{
    const DELETE_BUT_NAME = "delete";
    const EDIT_BUT_NAME = "set_role";
    const USER_ID_NAME = "id_uzivatel";
    const RIGHT_NAME = "prava_select";

    public function __construct($twig, $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->get_all = "";
        $this->def_view = "";
    }

    public function do_stuff()
    {
        $this->init();
        $this->process_action();

        $func = $this->get_all;
        if(!$this->VIEW){
            $this->VIEW = $this->def_view;
            $this->view_data["user_list"] = $this->pdo->$func();
            //var_dump($this->view_data["user_list"]);
        }
        echo $this->twig->render($this->VIEW,$this->view_data);

    }

    public function process_action(){
        if(isset($_POST[self::DELETE_BUT_NAME])){
            $this->pdo->deleteUser($_POST[self::USER_ID_NAME]);
        }
        if(isset($_POST[self::EDIT_BUT_NAME])){
            $this->pdo->update_rights($_POST[self::USER_ID_NAME],$_POST[self::RIGHT_NAME]);
        }
    }
}