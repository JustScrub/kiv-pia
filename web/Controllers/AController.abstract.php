<?php

namespace conference\Controllers;

use conference\Models\DB_Model;
use conference\Models\Session_Model;
use \Twig\Environment;

abstract class AController
{

    /* names of website button to process login form from any subsite */
    const LOGIN_BUTTON_NAME = "log_but";
    const LOGIN_INP_NAME = "login";
    const PASS_INP_NAME = "pass";

    const LOGOUT_BUTTON_NAME = "logout_but";

    // and language switch button
    const LANG_SWITCH_NAME = "lang_switch";

    protected Environment $twig;      // View generator
    protected DB_Model $pdo;          // DB access
    protected Session_Model $session; // Sessions -- state including logged user data, chosen language
    protected ?string $VIEW;           // name of view template file
    protected array $view_data;       // data passed to fill the template
    protected array $titles;          // page title in czech and english


    /**
     * @param $twig Environment to generate the View
     * @param $pdo DB_Model to access the Database
     */
    public function __construct(Environment $twig, DB_model $pdo){
        $this->twig = $twig;
        $this->pdo = $pdo;

        //set defaults, overriden in subclasses 
        $this->VIEW = null;
        $title = $_GET["page"] ?? "";
        $this->view_data = array(  // common parameters of the templates
            "title" => $title,
            "bad_login" => false,
            "ban" => false,
            "bad_pass" => false,
            "logged" => false,
            "rights" => 5,
            "user" => "",
            "lang" => "en"
        );
        $this->titles = array("cz" => $title,"en" => $title);
    }

    public function add_session($session){
        $this->session = $session;
    }

    /**
     * To be overriden in subclasses
     * processes forms of the specific controller, 
     * handles everything it is supposed to
     * and renders the page HTML from the view template
     */
    public abstract function do_stuff();

    /**
     * initializes the controllers
     */
    public function init()
    {
        $this->logout_process();
        $this->login_process();
        $this->set_lang();
        $this->view_data["title"] = $this->titles[$this->view_data["lang"]];
        $this->set_session_data();
    }

    /**
     * set state of the client -- login data and language
     */
    public function set_session_data(){
        if($this->view_data["logged"] = $this->session->is_logged()){
            $this->view_data["user"] =  $this->session->get(Session_Model::USER_NAME);
            $this->view_data["rights"] = $this->session->get(Session_Model::USER_RIGHTS);
        }
        else{
            $this->view_data["user"] = "";
            $this->view_data["rights"] = 5;
        }
        $this->view_data["lang"] = $this->session->get(Session_Model::USER_LANG) ?? "en";
    }

    /**
     * self explanatory?
     * sets language of the website based on the clicked language button
     */
    public function set_lang(){
        if(isset($_POST[self::LANG_SWITCH_NAME])){
            $lang = strtolower($_POST[self::LANG_SWITCH_NAME]);
            $this->session->set(Session_Model::USER_LANG,$lang);
        }
    }

    /**
     * if the user submitted the logout form,
     * logs the user out (deletes logged user data from session)
     */
    public function logout_process(){
        if(!isset($_POST[self::LOGOUT_BUTTON_NAME])) return;

        $this->session->logout();
        header('Location: index.php');
    }

    /**
     * if the user submitted the login form,
     * tries to log the user in (login and passwd check, ban check)
     * @return int error state 
     */
    public function login_process(): int{
        if(!isset($_POST[self::LOGIN_BUTTON_NAME])) return -1;

        $user = $this->pdo->get_user_data($_POST[self::LOGIN_INP_NAME]);
        if(!$user) {
            $this->view_data["bad_login"] = true;
            return DB_Model::UNKNOWN_LOGIN;
        }

        if($user["ban"]){
            $this->view_data["ban"] = true;
            return DB_Model::BANNED;
        }

        if($this->pdo->verify_user_knowing_hash($_POST[self::PASS_INP_NAME],$user["heslo"]) != DB_Model::SUCCESS)
        {
            $this->view_data["bad_pass"] = true;
            return DB_Model::WRONG_PASSWORD;
        }

        $this->session->login($user);
        return DB_Model::SUCCESS;
    }
}