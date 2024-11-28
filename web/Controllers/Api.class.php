<?php

namespace conference\Controllers;
use conference\Models\DB_Model as DB_Model;
use conference\Models\Logger_Model as Logger_Model;
use Attribute;
use OpenApi\Attributes as OAT;

#[Attribute]
class ApiMeta
{
    // the min rights to access the endpoint
    public int $rights;

    public function __construct($rights){
        $this->rights = $rights;
    }
}

#[OAT\Schema(
    schema: "Article",
    type: "object",
    properties: [
        new OAT\Property(property: "id", type: "integer", description: "Article ID"),
        new OAT\Property(property: "author_id", type: "integer", description: "Author ID"),
        new OAT\Property(property: "title", type: "string", description: "Article title"),
        new OAT\Property(property: "descr", type: "string", description: "Description"),
        new OAT\Property(property: "key-words", type: "string", description: "Key words"),
        new OAT\Property(property: "approved", type: "string", enum: ["yes","no", "pending"], description: "Approval status")
    ],
)]
class _Article{}

#[OAT\Schema(
    schema: "Error",
    type: "object",
    properties: [
        new OAT\Property(property: "error", type: "string", description: "Error type"),
        new OAT\Property(property: "status", type: "integer", description: "HTTP status code"),
        new OAT\Property(property: "message", type: "string", description: "Error message"),
        new OAT\Property(property: "redirect", type: "string", description: "API endpoint that may help resolve the error")
    ],
    required: ["error","status","message"]
)]
class _Error{}

/**
 * get the HTTP header 'Authorization: ___'
 * retrieval is different based on the PHP environment (apache2, Nginx etc.)
 */
function getAuthorizationHeader(){
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    }
    else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        //print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}

#[OAT\Info(title: "Conference API", version: "0.1")]
class Api
{
    //the DB model
    private DB_Model $pdo;

    public function __construct(DB_Model $pdo){
        $this->pdo = $pdo;
        $_SERVER["HTTP_AUTHORIZATION"] = getAuthorizationHeader(); // the API key
    }

    /**
     * the entrypoint of this class: 
     * handles authorization checks, input format and calling of the requested API endpoint
     */
    public function execute_service(string $service){
        $resp = null;
        $body = null;

        // authorization
        do { // funky way to dodge the 'goto' keyword
            if($service == "get_auth_key"){ // do not authorize for this endpoint
                break;
            }

            if(!method_exists($this,$service)){ // endpoint not found
                $resp = array(
                    "error" => "Not Found", "status" => 404,
                    "message" => "Service not found", 
                );
                break;
            }

            // get meta info about the endpoint: the min rights
            $reflection = new \ReflectionMethod($this,$service);
            $attributes = $reflection->getAttributes(ApiMeta::class);

            if(count($attributes) == 0){ // helper methods, not API endpoints
                $resp = array(
                    "error" => "Forbidden", "status" => 403,
                    "message" => "Service not available", 
                );
                break;
            }

            // check user rights
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
        } while(false); // end of funky goto dodge

        // check the request body format
        $body = $this->params_check($service);
        if(isset($body["error"])){
            $resp = $body;
        }

        if(is_null($resp)){ // no error up to this point
            $resp = $this->$service($body);
        }
        if(is_null($resp)){ // service does not send data this way
            return;
        }

        if(isset($resp["error"])){ // set appropriate status code on error
            header("HTTP/1.1 $resp[status] $resp[error]");
        }
        header("Content-Type: application/json"); // responses are JSONs (even if just strings)
        echo json_encode($resp); // send response to caller
    }

    /**
     * checks the request parameters for the specified API endpoint
     */
    private function params_check(string $service): array{
        $reflection = new \ReflectionMethod($this,$service);

        // URL GET-like query parameters (?<param>=<value>&...)
        // endpoint may have none --> getAttributes() returns empty iterable
        $params = $reflection->getAttributes(OAT\Parameter::class);
        foreach($params as $param){
            $p = $param->newInstance();
            if(!isset($_GET[$p->name]) && $p->required){ // required param not specified
                return array(
                    "error" => "Bad Request", "status" => 400,
                    "message" => "Missing query parameter $p->name"
                );
            }
        }

        // request body format
        $params = $reflection->getAttributes(OAT\RequestBody::class);
        if(count($params) == 0){ return array(); }; // may not have any -- e.g. GET requests
        $params = $params[0]->newInstance()->content[0]; // the actual body metadata
        
        $body = file_get_contents("php://input"); // get the request body
        if(strlen($body) == 0){ // body is required, but none given
            return array(
                "error" => "Bad Request", "status" => 400,
                "message" => "Missing request body"
            );
        }
        if($params->mediaType != "application/json"){ // if request body is not json, do not parse
            return $body;
        }
        $body = json_decode($body, true) ?? array(); // parse json to assoc array, or empty array on failure 
        if($params->schema->type != "object"){ // continue parsing only JSON objects
            return $body;
        }

        if(count($body) == 0){ // parse error
            return array(
                "error" => "Bad Request", "status" => 400,
                "message" => "Missing request body"
            );
        }

        $params = $params->schema; // json schema of the object
        foreach($params->properties as $p){
            // check for required properties
            if(in_array($p->property,$params->required) && !array_key_exists($p->property,$body)){
                return array(
                    "error" => "Bad Request", "status" => 400,
                    "message" => "Missing body parameter $p->property"
                );
            }
            // set defaults to unprovided properties
            if(!array_key_exists($p->property,$body)){ 
                $body[$p->property] = $p->default;
             }
        }
        return $body;
    }

    #[OAT\Post(path: "/api.php?service=get_auth_key", description: "Create new authorization key for the user with the optionally specified expiration time and return it")]
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
                    type: "object",
                    properties: [
                        new OAT\Property(property: "login", type: "string", description: "Username or email"),
                        new OAT\Property(property: "pass", type: "string", description: "Password"),
                        new OAT\Property(property: "expiration", type: "integer", default: 3600, description: "Expiration time in seconds")
                    ],
                    required: ["login","pass"]
           ))
    )]
    #[ApiMeta(1)]
    /**
     * Get new authorization key for the provided user
     * @param array body json object with mandatory login and password fields, and optional expiration field
     */
    public function get_auth_key(array $body): array{
        $login = $body["login"];
        $pass = $body["pass"];
        $expiration = $body["expiration"];

        $key = $this->pdo->new_auth_key($login,$pass,$expiration);
        // handle errors
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
    /**
     * get user info by user id
     * if auth key belongs to admin, show more info
     * @param array body not used, parameters in query mode (URL)
     */
    public function get_user(array $body): array{
        $login = $_GET["login"];
        $user = $this->pdo->get_user_data($login);
        if(!$user){
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
        if(!$this->pdo->verify_key($_SERVER["HTTP_AUTHORIZATION"],10)){ // admin rights
            // only admins get to know these
            unset($ret["banned"]);
            unset($ret["rights"]);
        }
        return $ret;
    }

    #[OAT\Get(path: "/api.php?service=get_user_articles", description: "Get articles by user")]
    #[OAT\Parameter(name: "login", in: "query", required: true, description: "User login or email", schema: new OAT\Schema(type: "string"))]
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
    /**
     * get all articles of a user by their id
     * @param array body not used, parameters passed in query
     * @return array array of user's articles
     */
    public function get_user_articles(array $body): array{
        $login = $_GET["login"];
        $user = $this->pdo->get_user_data($login);
        if(!$user){
            return array(
                "error" => "Not Found", "status" => 404,
                "message" => "User not found",
                "redirect" => "/api.php?service=get_user"
            );
        }
        $id = $user["id_uzivatel"];
        $articles = $this->pdo->articles_by_author($id);
        $app_state = array("pending","yes","no");
        // transform array keys and state value
        return array_reduce($articles,function($acc,$article) use ($app_state){
            $acc[] = array(
                "id" => $article["id_clanek"],
                "author_id" => $article["id_autor"],
                "title" => $article["nazev"],
                "descr" => $article["popis"],
                "key-words" => $article["klicova_slova"],
                "approved" => $app_state[$article["schvalen"]]
            );
            return $acc;
        },array());
    }

    #[OAT\Get(path: "/api.php?service=show_article", description: "Get article contents article ID")]
    #[OAT\Parameter(name: "id", in: "query", required: true, description: "Article ID", schema: new OAT\Schema(type: "integer"))]
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
    /**
     * Send contents of an article based on its ID
     * @param array body not used, parameter provided in query
     * @return ?array on error, return array, else send article contents and return null
     */
    public function show_article(array $body): ?array{
        $id = $_GET["id"];
        $file_id = $this->pdo->get_article($id);
        if(!$file_id){
            return array(
                "error" => "Not Found", "status" => 404,
                "message" => "Article not found",
                "redirect" => "/api.php?service=get_articles"
            );
        }
        $title = $file_id[0]["nazev"];
        $file_id = $file_id[0]["nazev_souboru"];
        if(!file_exists(ARTICLES_DIR."$file_id")){
            return array(
                "error" => "Not Found", "status" => 404,
                "message" => "Article file not found",
                "redirect" => "/api.php?service=get_articles"
            );
        }
        // set PDF headers and send content
        header("Content-Type: application/pdf");
        header("Content-Disposition: attachment; filename=\"$title\".pdf");
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        readfile(ARTICLES_DIR."$file_id");
        return null;
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
    #[ApiMeta(2)]
    /**
     * get all accepted articles
     * TODO: pagination
     * @param array body not used
     */
    public function get_articles(array $body): array{
        $articles = $this->pdo->get_accepted_articles();
        $app_state = array("pending","yes","no");
        // transform keys and state value
        return array_reduce($articles,function($acc,$article) use ($app_state){
            $acc[] = array(
                "id" => $article["id_clanek"],
                "author_id" => $article["id_autor"],
                "approved" => $app_state[$article["schvalen"]],
                "title" => $article["nazev"],
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
    /**
     * ban users by login or email
     * admins can only ban users, superadmins can ban admins
     * @param array body array of logins/emails
     * @return array array of banned users (may not contain inputs with failure)
     */
    public function ban_users(array $body): array{
        if(!array_is_list($body)){
            return array(
                "error" => "Bad Request", "status" => 400,
                "message" => "Body must be an array of strings"
            );
        }
        $banned = array();
        $key_rights = $this->pdo->select_query(VW_API_RIGHTS,array($_SERVER["HTTP_AUTHORIZATION"]),"klic=?")[0]["prava"];
        foreach($body as $login){
            $user_data = $this->pdo->get_user_data($login);
            if(!$user_data){
                continue;
            }
            //can only ban users with lower rights
            $rights = array(0,20,10,5,2)[$user_data["id_pravo"]];
            if($key_rights <= $rights){
                continue;
            }
            $this->pdo->ban_user($user_data["id_uzivatel"]);
            $banned[] = $login;
        }
        return $banned;
    }

    #[OAT\Delete(path: "/api.php?service=delete_article", description: "Delete user article by ID")]
    #[OAT\Parameter(name: "id", in: "query", required: true, description: "Article ID", schema: new OAT\Schema(type: "integer"))]
    #[OAT\Response(response: "200", description: "OK", 
                content: new OAT\MediaType(
                    mediaType: "application/json",
                    schema: new OAT\Schema(
                        type: "string",
                ))
    )]
    #[OAT\Response(response: "401", description: "Unauthorized", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "403", description: "Forbidden", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "400", description: "Bad Request", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "404", description: "Not Found", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[ApiMeta(2)]
    /**
     * delete an owned article
     * @param array body not used, parameter in query
     * @return array|string json array on error or "OK" on success
     */
    public function delete_article(array $body): array|string{
        $id = $_GET["id"];
        $ar_data = $this->pdo->get_article_author($id);
        if(!$ar_data || count($ar_data) == 0){
            return array(
                "error" => "Not Found", "status" => 404,
                "message" => "Article not found"
            );
        }
        $ar_data = $ar_data[0];
        // check if the user deletes their own article
        $user_id = $this->pdo->select_query(TB_API_KEYS,array($_SERVER["HTTP_AUTHORIZATION"]),"klic=?")[0]["id_uzivatel"];
        if($ar_data["id_autor"] != $user_id){
            return array(
                "error" => "Forbidden", "status" => 403,
                "message" => "You can only delete your own articles"
            );
        }
        $this->pdo->deletArticle($id);
        unlink(ARTICLES_DIR.$ar_data["nazev_souboru"]);
        return "OK";
    }

    #[OAT\Put(path: "/api.php?service=add_article", description: "Add article information")]
    #[OAT\RequestBody(
           content: new OAT\MediaType(
                mediaType: "application/json",
                schema: new OAT\Schema(
                    type: "object",
                    properties: [
                        new OAT\Property(property: "title", type: "string"),
                        new OAT\Property(property: "key-words", type: "string", default: null),
                        new OAT\Property(property: "descr", type: "string")
                    ],
                    required: ["title","descr"]
           ))
    )]
    #[OAT\Response(response: "200", description: "OK", 
                content: new OAT\MediaType(
                    mediaType: "application/json",
                    schema: new OAT\Schema(
                        type: "string",
                ))
    )]
    #[OAT\Response(response: "401", description: "Unauthorized", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "403", description: "Forbidden", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "400", description: "Bad Request", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[ApiMeta(2)]
    /**
     * add to-be-filled article meta-data to the database
     * create a "tmp" article for the user, with invalid FS path
     * this record will be updated by the upload_article API to set the actual path
     * @param array body json containing article metadata (description, keywords etc.)
     * @return string "OK"
     */
    public function add_article(array $body): string{
        $user_id = $this->pdo->select_query(TB_API_KEYS,array($_SERVER["HTTP_AUTHORIZATION"]),"klic=?")[0]["id_uzivatel"];
        // check if the user has article with "tmp" file path
        // yes -> update the title, key-words, description
        // no -> create a new article stub
        $article = $this->pdo->get_tmp_article($user_id);
        if(count($article) == 0){
            $this->pdo->addArticle($user_id,$body["title"],"tmp-$user_id",$body["key-words"],$body["descr"]);
        } else {
            $this->pdo->update_arinfo($article[0]["id_clanek"],$body["title"],$body["key-words"],$body["descr"]);
        }
        return "OK";
    }

    #[OAT\Post(path: "/api.php?service=upload_article", description: "Upload article file")]
    #[OAT\RequestBody(
           content: new OAT\MediaType(
                mediaType: "application/pdf",
                schema: new OAT\Schema(
                    type: "string",
                    format: "binary"
           ))
    )]
    #[OAT\Response(response: "200", description: "OK", 
                content: new OAT\MediaType(
                    mediaType: "application/json",
                    schema: new OAT\Schema(
                        type: "string",
                ))
    )]
    #[OAT\Response(response: "401", description: "Unauthorized", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "403", description: "Forbidden", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "400", description: "Bad Request", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "404", description: "Not Found", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[ApiMeta(2)]
    /**
     * upload the content of the article the metadata of which were previously
     * provided by the add_article API endpoint
     * @param mixed body binary data of the article content
     */
    public function upload_article(mixed $body): array|string{
        $user_id = $this->pdo->select_query(TB_API_KEYS,array($_SERVER["HTTP_AUTHORIZATION"]),"klic=?")[0]["id_uzivatel"];
        $article = $this->pdo->get_tmp_article($user_id);
        // add_article must be called first
        if(count($article) == 0){
            return array(
                "error" => "Not Found", "status" => 404,
                "message" => "No article data found. Add article information first",
                "redirect" => "/api.php?service=add_article"
            );
        }
        $filename = hash("sha256",$user_id . $article[0]["nazev"],true);
        $filename = base64_encode($filename).".pdf";
        $filename = strtr($filename,"+/","-_");
        $filename = str_replace("=","",$filename);
        $this->pdo->update_arfilepath($article[0]["id_clanek"],$filename);
        // TODO: check if $body contains the file...
        file_put_contents(ARTICLES_DIR.$filename,$body);
        return "OK";
    }

    #[OAT\Post(path: "/api.php?service=otp_login", description: "Login to website using OTP")]
    #[OAT\RequestBody(
           content: new OAT\MediaType(
                mediaType: "application/json",
                schema: new OAT\Schema(
                    type: "object",
                    properties: [
                        new OAT\Property(property: "otp", type: "string"),
                    ],
                    required: ["otp"]
           ))
    )]
    #[OAT\Response(response: "200", description: "OK", 
                content: new OAT\MediaType(
                    mediaType: "application/json",
                    schema: new OAT\Schema(
                        type: "string",
                ))
    )]
    #[OAT\Response(response: "401", description: "Unauthorized", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "403", description: "Forbidden", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[OAT\Response(response: "400", description: "Bad Request", content: new OAT\JsonContent(ref: "#/components/schemas/Error"))]
    #[ApiMeta(2)]
    /**
     * OTP login endpoint
     * when logging with OTP, the OTP is sent to this API to proceed with the login
     * @param array body json containing the OTP
     * @return string "OK" on success or "OTP not found" when invalid OTP is provided
     */
    public function otp_login(array $body): string{
        $otp = $body["otp"];
        // get user by API key
        $user_id = $this->pdo->select_query(TB_API_KEYS,array($_SERVER["HTTP_AUTHORIZATION"]),"klic=?")[0]["id_uzivatel"];
        $ws_data = array(
            "otp" => $otp,
            "user_id" => $user_id,
            // sign the OTP with the user's API key (only the user should know the API key)
            "signature" => base64_encode(hash_hmac("sha256",$otp,$_SERVER["HTTP_AUTHORIZATION"],true))
        );
        $ws_data = json_encode($ws_data);
        // connect to the WebSocket server
        $ws = new \WebSocket\Client("ws://".WSS_HOST_API.":".WSS_PORT);
        while(true){ // just in case
            try {
                $ws->text($ws_data); // send OTP data
                $msg = $ws->receive(); // get server response
                if($msg == "OK"){
                    break;
                }
                if($msg == "OTP not found"){
                    break;
                }
                // any other msg is weird --> try again
            } catch (\WebSocket\ConnectionException $e){
                $ex_text = "OTP: Nastala výjimka při komunikaci s WebSocket serverem: ".$e->getCode()
                    ."\tText: ".$e->getMessage();
                (new Logger_Model())->log($ex_text)->destruct();
            }
        }
        // WS server forwards the OTP data to the JS WebSocket client on the OTP page
        // the JS client fills the data to a hidden form and submits the form
        // the OTPLoginController then verifies the data (user and signature)
        // and logs the user in on success
        $ws->close();
        return $msg;
    }


}