<?php

namespace conference\Controllers;
use conference\Models\DB_Model as DB_Model;
use Attribute;
use OpenApi\Attributes as OAT;

#[Attribute]
class ApiMeta
{
    public int $rights;

    public function __construct($rights){
        $this->rights = $rights;
    }
}

#[OAT\Info(title: "Conference API", version: "0.1")]
#[OAT\Schema(
    schema: "Error",
    type: "object",
    properties: [
        new OAT\Property(property: "error", type: "string"),
        new OAT\Property(property: "status", type: "integer"),
        new OAT\Property(property: "message", type: "string"),
        new OAT\Property(property: "redirect", type: "string")
    ],
    required: ["error","status","message"]
)]
class Api
{
    public function __construct($pdo){
        $this->pdo = $pdo;
    }

    public function execute_service($service){
        $resp = null;
        do {
            if($service == "get_auth_key"){
                break;
            }

            if(!method_exists($this,$service)){
                $resp = array(
                    "error" => "Not Found", "status" => 404,
                    "message" => "Service not found", 
                );
                break;
            }

            $reflection = new \ReflectionMethod($this,$service);
            $attributes = $reflection->getAttributes(ApiMeta::class);

            if(count($attributes) == 0){ // helper methods, not API endpoints
                $resp = array(
                    "error" => "Forbidden", "status" => 403,
                    "message" => "Service not available", 
                );
                break;
            }

            $rights = $attributes[0]->newInstance()->rights;
            $key = $_SERVER["HTTP_AUTHORIZATION"];
            if(!$this->pdo->verify_key($key,$rights)){
                $resp = array(
                    "error" => "Unauthorized", "status" => 401,
                    "message" => "Invalid API key. Authorize by providing a valid key in the Authorization header",
                    "redirect" => "/api.php?service=get_auth_key",
                );
                break;
            }
        } while(false);

        $body = $this->params_check($service);
        if(isset($body["error"])){
            $resp = $body;
        }

        if(is_null($resp)){ // no error up to this point
            $resp = $this->$service($body);
        }

        if(isset($resp["error"])){
            header("HTTP/1.1 $resp[status] $resp[error]");
        }
        header("Content-Type: application/json");
        echo json_encode($resp);
    }

    private function params_check($service){
        $reflection = new \ReflectionMethod($this,$service);
        $params = $reflection->getAttributes(OAT\Parameter::class);
        foreach($params as $param){
            $p = $param->newInstance();
            if(!isset($_GET[$p->name]) && $p->required){
                return array(
                    "error" => "Bad Request", "status" => 400,
                    "message" => "Missing query parameter $p->name"
                );
            }
        }

        $params = $reflection->getAttributes(OAT\RequestBody::class);
        if(count($params) == 0){ return array(); };
        $params = $params[0]->newInstance()->content[0]->schema;
        $body = json_decode(file_get_contents("php://input"), true) ?? array();

        foreach($params->properties as $p){
            if(in_array($p->property,$params->required) && !array_key_exists($p->property,$body)){
                return array(
                    "error" => "Bad Request", "status" => 400,
                    "message" => "Missing body parameter $p->property"
                );
            }
            if(!array_key_exists($p->property,$body)){ 
                $body[$p->property] = $p->default;
             }
        }
        return $body;
    }

    #[OAT\Post(path: "/api.php?service=get_auth_key")]
    #[OAT\Response(response: "200", description: "OK", 
                content: new OAT\MediaType(
                    mediaType: "application/json",
                    schema: new OAT\Schema(
                        properties: [
                            new OAT\Property(property: "key", type: "string")
                        ],
                ))
    )]
    #[OAT\Response(response: "401", description: "Unauthorized", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "403", description: "Forbidden", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "400", description: "Bad Request", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\RequestBody(
           content: new OAT\MediaType(
                mediaType: "application/json",
                schema: new OAT\Schema(
                    properties: [
                        new OAT\Property(property: "login", type: "string", description: "Username or email"),
                        new OAT\Property(property: "pass", type: "string", description: "Password"),
                        new OAT\Property(property: "expiration", type: "integer", default: 3600, description: "Expiration time in seconds")
                    ],
                    required: ["login","pass"]
           ))
    )]
    #[ApiMeta(1)]
    public function get_auth_key($body){
        $login = $body["login"];
        $pass = $body["pass"];
        $expiration = $body["expiration"];

        $key = $this->pdo->new_auth_key($login,$pass,$expiration);
        switch($key){
            case DB_Model::UNKNOWN_LOGIN:
            case DB_Model::WRONG_PASSWORD:
                return array(
                    "error" => "Unauthorized", "status" => 401,
                    "message" => "Invalid login or password"
                );
            case DB_Model::BANNED:
                return array(
                    "error" => "Forbidden", "status" => 403,
                    "message" => "User is banned"
                );
            default:
                return array(
                    "key" => $key
                );
        }
    }
}