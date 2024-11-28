<?php

namespace conference\Controllers;

class AccountManager extends ALoggedController
{
    const string DELETE_BUT_NAME = "delete";
    const string EDIT_BUT_NAME = "set_role";
    const string USER_ID_NAME = "id_uzivatel";
    const string RIGHT_NAME = "prava_select";

    private string $get_all;
    private string $def_view;
    private array $ofWhatManagement;

    public function __construct(Twig\Environment $twig, DB_model $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->get_all = "";
        $this->def_view = "";
        $this->ofWhatManagement = array("cz" => "", "en" => "");
    }

    public function do_stuff()
    {
        $this->init();
        $this->process_action();

        $this->view_data["ofWhatManagement"] = $this->ofWhatManagement[$this->view_data["lang"]];

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
            $this->pdo->ban_user($_POST[self::USER_ID_NAME]);
        }
        if(isset($_POST[self::EDIT_BUT_NAME])){
            $this->pdo->update_rights($_POST[self::USER_ID_NAME],$_POST[self::RIGHT_NAME]);
        }
    }
}