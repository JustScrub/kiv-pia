<?php

namespace conference\Models;

use PDO;
use PDOException;

class DB_Model
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = new PDO("mysql:host=".DB_SERVER.";dbname=".DB_NAME, "root");
        $this->pdo->exec("set names utf8");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function execute_query($query){
        try {
            $res = $this->pdo->query($query);
            return $res;
        } catch (PDOException $ex){
            echo "Nastala výjimka: ". $ex->getCode() ."<br>"
                ."Text: ". $ex->getMessage();
            return null;
        }
    }

    //// SELECTS
    public function select_query($tab_name,$condition = "",$order_by = "",$sort_order = "ASC"){
        $q = "SELECT * FROM $tab_name".
            ($condition=="" ? "" : " WHERE $condition").
            ($order_by=="" ? "" : " ORDER BY $order_by $sort_order");
        $q .= ";";
        //echo($q);

        $res = $this->execute_query($q);
        if(!$res) return null;
        return $res->fetchAll(PDO::FETCH_ASSOC);
    }

    private function get_user_data_by_login($user_login){
        return  @$this->select_query(TB_USERS,"login = '$user_login'")[0];
    }
    private function get_user_data_by_email($user_email){
        return  @$this->select_query(TB_USERS,"email = '$user_email'")[0];
    }

    public function get_user_data($login_or_email){
        if(filter_var($login_or_email, FILTER_VALIDATE_EMAIL)){
            return $this->get_user_data_by_email($login_or_email);
        }
        else{
            return $this->get_user_data_by_login($login_or_email);
        }
    }

    public function getSessionLoginInfo($login_or_email){
        // naming convention allows us to do this
        return $this->get_user_data($login_or_email);
    }

    public function get_all_users(){
        return $this->select_query(TB_USERS,"id_pravo>2");
    }
    public function get_all_admins(){
        return $this->select_query(TB_USERS,"id_pravo<=2");
    }

    const UNKNOWN_LOGIN = 1;
    const WRONG_PASSWORD = 2;
    const SUCCESS = 0;
    public function verify_user($login,$pwd){
        $pwd_hash = $this->get_user_data_by_login($login);
        if(!$pwd_hash) return self::UNKNOWN_LOGIN;
        $pwd_hash =  $pwd_hash["heslo"];
        return $this->verify_user_knowing_hash($pwd,$pwd_hash);
    }

    public function verify_user_knowing_hash($pwd,$pwd_hash){
        if(password_verify($pwd,$pwd_hash)) return self::SUCCESS;
        return self::WRONG_PASSWORD;
    }

    //// INSERTS

    public function insert_query($tableName, $insertStatement, $insertValues) {
        $q = "INSERT INTO $tableName($insertStatement) VALUES ($insertValues)";
        $obj = $this->execute_query($q);
        return ($obj != null);
    }

    private function addUser($fname,$sname,$login,$mail,$pwd,$pravo){
        $insert_statement = "id_pravo, login, jmeno, prijmeni, email, heslo";
        $pwd = password_hash($pwd,PASSWORD_DEFAULT);
        $insert_values = "$pravo,'$login','$fname','$sname','$mail','$pwd'";
        $this->insert_query(TB_USERS,$insert_statement,$insert_values);
    }

    public function register($fname,$sname,$login,$mail,$pwd){
        $this->addUser($fname,$sname,$login,$mail,$pwd,4);
    }
    //// UPDATES
    //// DELETES

}