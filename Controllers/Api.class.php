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

#[OAT\Schema(
    schema: "Article",
    type: "object",
    properties: [
        new OAT\Property(property: "id", type: "integer"),
        new OAT\Property(property: "title", type: "string"),
        new OAT\Property(property: "file-id", type: "string"),
        new OAT\Property(property: "descr", type: "string"),
        new OAT\Property(property: "key-words", type: "string"),
        new OAT\Property(property: "approved", type: "string", enum: ["yes","no", "pending"])
    ],
    required: ["id","title","file-id","descr"]
)]
class RespArticle{}

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
class RespError{}

#[OAT\Info(title: "Conference API", version: "0.1")]
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

    #[OAT\Get(path: "/api.php?service=get_user", description: "Get user info: first name, last name, email, id. Admins also get rights and banned status.")]
    #[OAT\Parameter(name: "login", in: "query", required: true, description: "User login or email", schema: new OAT\Schema(type: "string"))]
    #[OAT\Response(response: "200", description: "OK", 
                content: new OAT\MediaType(
                    mediaType: "application/json",
                    schema: new OAT\Schema(
                        properties: [
                            new OAT\Property(property: "fname", type: "string", description: "First name"),
                            new OAT\Property(property: "lname", type: "string", description: "Last name"),
                            new OAT\Property(property: "email", type: "string", description: "Email"),
                            new OAT\Property(property: "id", type: "integer", description: "User ID"),
                            new OAT\Property(property: "rights", type: "integer", description: "User rights"),
                            new OAT\Property(property: "banned", type: "boolean", description: "Is user banned"),
                        ],
                        required: ["fname","lname","email","id"]
                ))
    )]
    #[OAT\Response(response: "401", description: "Unauthorized", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "403", description: "Forbidden", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "400", description: "Bad Request", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "404", description: "Not Found", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[ApiMeta(2)]
    public function get_user($body){
        $login = $_GET["login"];
        $user = $this->pdo->get_user($login);
        if($user === false){
            return array(
                "error" => "Not Found", "status" => 404,
                "message" => "User not found"
            );
        }
        $ret = array(
            "fname" => $user["jmeno"],
            "lname" => $user["prijmeni"],
            "email" => $user["email"],
            "id" => $user["id_uzivatel"],
            "rights" => $user["id_pravo"],
            "banned" => $user["ban"]
        );
        if(!$this->pdo->verify_key($_SERVER["HTTP_AUTHORIZATION"],20)){ // admin rights
            unset($ret["banned"]);
            unset($ret["rights"]);
        }
        return $ret;
    }

    #[OAT\Get(path: "/api.php?service=get_user_articles", description: "Get articles by user")]
    #[OAT\Parameter(name: "id", in: "query", required: true, description: "User ID", schema: new OAT\Schema(type: "integer"))]
    #[OAT\Response(response: "200", description: "OK", 
                content: new OAT\MediaType(
                    mediaType: "application/json",
                    schema: new OAT\Schema(
                        type: "array",
                        items: new OAT\Items(ref: "#/components/schemas/Article")
                ))
    )]
    #[OAT\Response(response: "401", description: "Unauthorized", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "403", description: "Forbidden", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "400", description: "Bad Request", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "404", description: "Not Found", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[ApiMeta(2)]
    public function get_user_articles($body){
        $id = $_GET["id"];
        $articles = $this->pdo->articles_by_author($id);
        if($articles === false){
            return array(
                "error" => "Not Found", "status" => 404,
                "message" => "User not found",
                "redirect" => "/api.php?service=get_user"
            );
        }
        $app_state = array("pending","yes","no");
        return array_reduce($articles,function($acc,$article){
            $acc[] = array(
                "id" => $article["id_clanek"],
                "title" => $article["nazev"],
                "file-id" => $article["nazev_souboru"],
                "descr" => $article["popis"],
                "key-words" => $article["klicova_slova"],
                "approved" => $app_state[$article["schvalen"]]
            );
            return $acc;
        },array());
    }

    #[OAT\Get(path: "/api.php?service=show_article", description: "Get article contents by file ID")]
    #[OAT\Parameter(name: "file-id", in: "query", required: true, description: "File ID", schema: new OAT\Schema(type: "string"))]
    #[OAT\Response(response: "200", description: "OK", 
                content: new OAT\MediaType(
                    mediaType: "application/pdf",
                    schema: new OAT\Schema(
                        type: "string",
                        format: "binary"
                ))
    )]
    #[OAT\Response(response: "401", description: "Unauthorized", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "403", description: "Forbidden", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "400", description: "Bad Request", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "404", description: "Not Found", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[ApiMeta(2)]
    public function show_article($body){
        $file_id = $_GET["file-id"];
        if(!file_exists(ARTICLES_DIR."$file_id")){
            return array(
                "error" => "Not Found", "status" => 404,
                "message" => "Article not found",
                "redirect" => "/api.php?service=get_articles"
            );
        }
        header("Content-Type: application/pdf");
        readfile(ARTICLES_DIR."$file_id");
    }

    #[OAT\Get(path: "/api.php?service=get_articles", description: "Get all ACCEPTED articles")]
    #[OAT\Response(response: "200", description: "OK", 
                content: new OAT\MediaType(
                    mediaType: "application/json",
                    schema: new OAT\Schema(
                        type: "array",
                        items: new OAT\Items(
                            ref: "#/components/schemas/Article"
                        )
                ))
    )]
    #[OAT\Response(response: "401", description: "Unauthorized", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "403", description: "Forbidden", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "400", description: "Bad Request", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[ApiMeta(1)]
    public function get_articles($body){
        $articles = $this->pdo->get_accepted_articles();
        $app_state = array("pending","yes","no");
        return array_reduce($articles,function($acc,$article){
            $acc[] = array(
                "id" => $article["id_clanek"],
                "title" => $article["nazev"],
                "file-id" => $article["nazev_souboru"],
                "descr" => $article["popis"],
                "key-words" => $article["klicova_slova"],
            );
            return $acc;
        },array());
    }

    #[OAT\Put(path: "/api.php?service=ban_users", description: "Ban users by logins or emails")]
    #[OAT\RequestBody(
           content: new OAT\MediaType(
                mediaType: "application/json",
                schema: new OAT\Schema(
                    type: "array",
                    items: new OAT\Items(
                        type: "string"
                ))
           )
    )]
    #[OAT\Response(response: "200", description: "OK", 
                content: new OAT\MediaType(
                    mediaType: "application/json",
                    schema: new OAT\Schema(
                        type: "array",
                        items: new OAT\Items(
                            type: "string"
                        )
                ))
    )]
    #[OAT\Response(response: "401", description: "Unauthorized", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "403", description: "Forbidden", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "400", description: "Bad Request", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[ApiMeta(10)]
    public function ban_users($body){
        $banned = array();
        $key_rights = $this->select_query(VW_API_RIGHTS,$params,"klic=?")[0]["prava"];
        foreach($body as $login){
            //can only ban users with lower rights
            $rights = $this->pdo->get_user_data($login);
            if(!$rights){
                continue;
            }
            $rights = array(0,20,10,5,2)[$rights["id_pravo"]];
            if($key_rights <= $rights){
                continue;
            }
            $this->pdo->ban_user($login);
            $banned[] = $login;
        }
        return $banned;
    }

}