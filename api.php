<?php

/*
- API library: swagger-php (using attributes)
- API paths: api.php?service=<service>
- API controller: Controllers/Api.class.php
  - services are methods of the controller

- User must be logged in to use the API via login form on the website (session)
    or via api.php?service=login&username=<username_or_email>&password=<password>

- Path api.php is the entry point for the API and requests to it return OpenAPI documentation (yaml)
  - fs path: ./api-doc.yaml

*/

require "config.php";
require_once "Models/DB_Model.class.php";
require_once "Controllers/Api.class.php";


if(isset($_GET["service"])){
    $service = $_GET["service"];
    $db = new \conference\Models\DB_Model;
    $controller = new \conference\Controllers\Api($db);
    $controller->execute_service($service);
}
else{
    header("Content-Type: text/yaml");
    readfile("api-doc.yaml");
}