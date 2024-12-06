<?php

namespace conference\Controllers;
use conference\Models\DB_Model;

class AccountManager extends ALoggedController
{
    // form input names
    const string DELETE_BUT_NAME = "delete";
    const string EDIT_BUT_NAME = "set_role";
    const string USER_ID_NAME = "id_uzivatel";
    const string RIGHT_NAME = "prava_select";

    private string $get_all;         // method "pointer" to the method that returns the correct account roles (admins or users)
    private string $def_view;        // the view template name -- subclasses use this parameter, this class sets the view for them
    private array $ofWhatManagement; // the role which the sublcassed management page is supposed to enable management of (admins or users), in czech and english

    public function __construct(\Twig\Environment $twig, DB_model $pdo)
    {
        parent::__construct($twig, $pdo);
        // defaults to be overriden in subclass
        $this->get_all = "";
        $this->def_view = "";
        $this->ofWhatManagement = array("cz" => "", "en" => "");
    }

    /**
     * the common do_stuff() entrypoint of the controllers
     * sublcasses only provide data to this parent class
     * this class handles the functionality
     * in order to conform to the DRY principle
     */
    public function do_stuff()
    {
        $this->init();
        $this->process_action();

        $this->view_data["ofWhatManagement"] = $this->ofWhatManagement[$this->view_data["lang"]]; //provided in sublcass constructor

        $func = $this->get_all; // provided in subclass constructor
        if(!$this->VIEW){
            $this->VIEW = $this->def_view; // in subclass construcor
            $this->view_data["user_list"] = $this->pdo->$func();
            //var_dump($this->view_data["user_list"]);
        }
        echo $this->twig->render($this->VIEW,$this->view_data);
    }

    /**
     * common actions to do with users, be it regular users or admins
     */
    public function process_action(){
        if(isset($_POST[self::DELETE_BUT_NAME])){
            $this->pdo->ban_user($_POST[self::USER_ID_NAME]);
        }
        if(isset($_POST[self::EDIT_BUT_NAME])){
            $this->pdo->update_rights($_POST[self::USER_ID_NAME],$_POST[self::RIGHT_NAME]);
        }
    }
}