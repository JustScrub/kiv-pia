<?php

namespace conference\Controllers;

require_once "Models/Session_Model.class.php";
use conference\Models\Session_Model;
use Attribute;

#[Attribute]
class ApiMeta
{
    public int $rights;

    public function __construct($rights){
        $this->rights = $rights;
    }
}

class Api
{
    public function __construct($pdo){
        $this->pdo = $pdo;
        $this->session = new Session_Model;
    }

    public function execute_service($service){
        $resp = null;
        if(!$this->session->is_logged()){
            $resp = array(
                "error" => "Unauthorized", "status" => 401,
                "message" => "You must be logged in to use the API", 
                "service" => $service,
                "redirect" => "api.php?service=login"
            );
        }

        if(!method_exists($this,$service)){
            $resp = array(
                "error" => "Not Found", "status" => 404,
                "message" => "Service not found", 
                "service" => $service
            );
        }

        $reflection = new \ReflectionMethod($this,$service);
        $attributes = $reflection->getAttributes(ApiMeta::class);

        if(count($attributes) == 0){ // helper methods, not API endpoints
            $resp = array(
                "error" => "Forbidden", "status" => 403,
                "message" => "Service not available", 
                "service" => $service
            );
        }

        $rights = $attributes[0]->newInstance()->rights;
        if($this->session->get(Session_Model::USER_RIGHTS) < $rights){
            $resp = array(
                "error" => "Forbidden", "status" => 403,
                "message" => "Insufficient rights", 
                "service" => $service
            );
        }

        if(is_null($resp)){ // no error up to this point
            $resp = $this->$service();
        }

        if(isset($resp["error"])){
            header("HTTP/1.1 $resp[status] $resp[error]");
        }
        echo json_encode($resp);
    }

    // TODO: come up with a better way to login via API
    //  - some kind of session ID or API key

    #[ApiMeta(1)]
    public function login(){
        /*$username = $_GET["username"];
        $password = $_GET["password"];
        $resp = null;

        $user = $this->pdo->get_user_data($username);
        if(!$user) {
            $resp = array(
                "error" => "Unauthorized", "status" => 401,
                "message" => "User not found", 
                "service" => "login"
            );
        }

        if($user["ban"]){
            $resp = array(
                "error" => "Forbidden", "status" => 403,
                "message" => "User is banned", 
                "service" => "login"
            );
        }

        if($this->pdo->verify_user_knowing_hash($_POST[self::PASS_INP_NAME],$user["heslo"]) != DB_Model::SUCCESS)
        {
            $resp = array(
                "error" => "Unauthorized", "status" => 401,
                "message" => "Invalid password", 
                "service" => "login"
            );
        }

        $this->session->login($user);
        if(is_null($resp)){
            $resp = array(
                "status" => 200,
                "message" => "Logged in",
                "service" => "login"
            );
        }
        return $resp;
        */
    }
}