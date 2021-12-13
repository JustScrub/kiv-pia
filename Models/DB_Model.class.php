<?php

namespace conference\Models;

use PDO;
use PDOException;
use function Sodium\add;

class DB_Model
{
    private $pdo;
    private $last_err;

    public function __construct()
    {
        $this->pdo = new PDO("mysql:host=".DB_SERVER.";dbname=".DB_NAME, "root");
        $this->pdo->exec("set names utf8");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function last_err(){
        return $this->last_err;
    }

    private function execute_query($query){
        try {
            $res = $this->pdo->query($query);
            return $res;
        } catch (PDOException $ex){
            echo "Nastala výjimka: ". $ex->getCode() ."<br>"
                ."Text: ". $ex->getMessage();

            $this->last_err = $ex->getCode();
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
    public function verify_user($login_or_email,$pwd){
        $pwd_hash = $this->get_user_data($login_or_email);
        if(!$pwd_hash) return self::UNKNOWN_LOGIN;
        $pwd_hash =  $pwd_hash["heslo"];
        return $this->verify_user_knowing_hash($pwd,$pwd_hash);
    }

    public function verify_user_knowing_hash($pwd,$pwd_hash){
        if(password_verify($pwd,$pwd_hash)) return self::SUCCESS;
        return self::WRONG_PASSWORD;
    }


    public function articles_by_author($author_id){
        return $this->select_query(TB_ARTICLE,"id_autor=$author_id","id_clanek", "desc");
    }

    public function get_article($id){
        return $this->select_query(TB_ARTICLE,"id_clanek=$id");
    }

    public function get_article_author($id_ar){
        return $this->select_query(VW_AUTHORS_ARTICLES, "id_clanek=$id_ar");
    }

    public function get_articles_to_add_recenzenti_to(){
        return $this->select_query(VW_NEED_REVIEW,"schvalen < 2");
    }

    public function get_article_reviewers($id_article){
        //SELECT * FROM `uzivatel` u WHERE u.id_uzivatel in (SELECT r.id_recenzent from `recenzenti` r WHERE r.id_clanek = $id_article);
        $q = "SELECT * FROM ".TB_USERS." u WHERE u.id_uzivatel in (SELECT r.id_recenzent from ".TB_REVIEW." r WHERE r.id_clanek = $id_article);";
        $res = $this->execute_query($q);
        if(!$res) return null;
        return $res->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_recenzenti(){
        return $this->select_query(TB_USERS,"id_pravo<4");
    }

    public function artorev($id_reviewer){
        //SELECT * FROM clanek c WHERE c.id_clanek IN (SELECT r.id_clanek FROM recenzenti r WHERE r.id_recenzent = $id_reviewer and r.hodnoceni is null);
        return $this->select_query(TB_ARTICLE." c",
            "c.id_clanek IN (SELECT r.id_clanek FROM recenzenti r WHERE r.id_recenzent = $id_reviewer and r.hodnoceni is null)");
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
       return $this->insert_query(TB_USERS,$insert_statement,$insert_values);
    }

    public function register($fname,$sname,$login,$mail,$pwd){
        return $this->addUser($fname,$sname,$login,$mail,$pwd,4);
    }

    public function addArticle($id_author,$name,$file_name,$key_words,$desc){
        //sqli safety
        $key_words = addslashes($key_words);
        $desc = addslashes($desc);
        $name = addslashes($name);

        $insert_statement = "id_autor,nazev,nazev_souboru,klicova_slova,popis";
        $insert_values = "$id_author,'$name','$file_name','$key_words','$desc'";
        return $this->insert_query(TB_ARTICLE,$insert_statement,$insert_values);
    }

    public function insert_recenzent($id_clanek,$id_recenzent){
        $statement = "id_clanek,id_recenzent";
        $values = "$id_clanek,$id_recenzent";
        return $this->insert_query(TB_REVIEW,$statement,$values);
    }
    //// UPDATES
    private function update_query( $tableName,  $updateStatementWithValues,  $whereStatement) {
        $q = "UPDATE $tableName SET $updateStatementWithValues WHERE $whereStatement";
        $obj = $this->execute_query($q);
        return ($obj != null);
    }

    public function update_rights($id_uzivatel, $id_pravo){
        $this->update_query("uzivatel","id_pravo=$id_pravo","id_uzivatel=$id_uzivatel");
    }

    public function update_ardesc($id,$new_desc){
        $this->update_query(TB_ARTICLE,"popis='$new_desc'","id_clanek=$id");
    }
    public function ardecl($id){
        return $this->update_query(TB_ARTICLE,"schvalen=2","id_clanek=$id");
    }

    public function revar($id_rev,$id_ar,$rev_val,$rev_desc){
        $vals = "hodnoceni=$rev_val, poznamky='$rev_desc'";
        $where = "id_clanek=$id_ar and id_recenzent=$id_rev";
        // echo $vals . "     " . $where;
        return $this->update_query(TB_REVIEW,$vals,$where);
    }

    //// DELETES
    private function delete_query( $tableName,  $whereStatement) {
        $q = "DELETE FROM $tableName WHERE $whereStatement";
        $obj = $this->execute_query($q);
        return ($obj != null);
    }

    public function deleteUser($id_uzivatel){
        $this->delete_query("uzivatel","id_uzivatel = $id_uzivatel");
    }

    public function  deletArticle($id_clanek){
        return $this->delete_query(TB_ARTICLE,"id_clanek = $id_clanek");
    }

}