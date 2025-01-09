<?php

namespace conference\Models;
require_once "Logger_Model.class.php";

use PDO;
use PDOException;
use PDOStatement;
use function Sodium\add;

class DB_Model
{
    private PDO $pdo; //PDO instance
    private string $last_err; // err code

    public function __construct()
    {
        $this->pdo = new PDO("mysql:host=".DB_SERVER.";dbname=".DB_NAME, DB_LOGIN, DB_PASS);
        $this->pdo->exec("set names utf8");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function last_err(){
        return $this->last_err;
    }

    /**
     * executes a SQL query using PDO prepared statement
     */
    private function execute_query(string $query, ?array $params): ?PDOStatement{
        // a'); update uzivatel set jmeno='Kuba' where id_uzivatel=3; --
        try {
            $stmt = $this->pdo->prepare($query);

            if($stmt->execute($params)){
                return $stmt;
            }
            return null;

        } catch (PDOException $ex){
            $ex_text = "PDO: Nastala výjimka: ". $ex->getCode() ."\t"
                ."Text: ". $ex->getMessage();

            $this->last_err = $ex->getCode();


            (new Logger_Model())->log($ex_text)->destruct();

            return null;
        }
    }

    //// SELECTS

    /**
     * generic select SQL query
     * select all columns
     * returns rows as assoc arrays
     */
    public function select_query(
        string $tab_name, 
        ?array $params,
        string $condition = "", 
        string $order_by = "", 
        string $sort_order = "ASC"): ?array{

        $q = "SELECT * FROM $tab_name".
            ($condition=="" ? "" : " WHERE $condition").
            ($order_by=="" ? "" : " ORDER BY $order_by $sort_order");
        $q .= ";";
        //echo($q);

        $res = $this->execute_query($q, $params);
        if(!$res) return null;
        return $res->fetchAll(PDO::FETCH_ASSOC);
    }

    private function get_user_data_by_login(string $user_login): ?array{
        $params = array($user_login);
        return  @$this->select_query(TB_USERS,$params,"login = ?")[0];
    }
    private function get_user_data_by_email(string $user_email): ?array{
        $params = array($user_email);
        return  @$this->select_query(TB_USERS,$params,"email = ?")[0];
    }

    public function get_user_data(string $login_or_email): ?array{
        if(filter_var($login_or_email, FILTER_VALIDATE_EMAIL)){
            return $this->get_user_data_by_email($login_or_email);
        }
        else{
            return $this->get_user_data_by_login($login_or_email);
        }
    }

    public function getSessionLoginInfo(string $login_or_email): ?array{
        // naming convention allows us to do this
        return $this->get_user_data($login_or_email);
    }

    public function get_all_users(): ?array{
        return $this->select_query(TB_USERS,null,"id_pravo>2");
    }
    public function get_all_admins(): ?array{
        return $this->select_query(TB_USERS,null,"id_pravo<=2");
    }

    // login error statuses
    const int UNKNOWN_LOGIN = 1;
    const int WRONG_PASSWORD = 2;
    const int BANNED = 3;
    const int SUCCESS = 0;
    public function verify_user(string $login_or_email, string $pwd): int{
        $pwd_hash = $this->get_user_data($login_or_email);
        if(!$pwd_hash) return self::UNKNOWN_LOGIN;
        $pwd_hash =  $pwd_hash["heslo"];
        return $this->verify_user_knowing_hash($pwd,$pwd_hash);
    }

    public function verify_user_knowing_hash(string $pwd, string $pwd_hash): int{
        if(password_verify($pwd,$pwd_hash)) return self::SUCCESS;
        return self::WRONG_PASSWORD;
    }


    public function articles_by_author(int $author_id): ?array{
        $params=array($author_id);
        return $this->select_query(TB_ARTICLE,$params,"id_autor=? and nazev_souboru != 'tmp-$author_id'","id_clanek", "desc");
    }

    public function get_tmp_article(int $user_id): ?array{
        $params=array($user_id);
        return $this->select_query(TB_ARTICLE,$params,"id_autor=? and nazev_souboru='tmp-$user_id'");
    }

    public function get_article(int $id): ?array{
        $params=array($id);
        return $this->select_query(TB_ARTICLE,$params,"id_clanek=?");
    }

    public function get_article_author(int $id_ar): ?array{
        $params=array($id_ar);
        return $this->select_query(VW_AUTHORS_ARTICLES,$params, "id_clanek=?");
    }

    public function get_articles_to_add_recenzenti_to(): ?array{
        return $this->select_query(VW_NEED_REVIEW,null,"schvalen < 2");
    }

    public function get_article_reviewers(int $id_article): ?array{
        $params = array($id_article);
        //SELECT * FROM `uzivatel` u WHERE u.id_uzivatel in (SELECT r.id_recenzent from `recenzenti` r WHERE r.id_clanek = $id_article);
        $q = "SELECT * FROM ".TB_USERS." u WHERE u.id_uzivatel in (SELECT r.id_recenzent from ".TB_REVIEW." r WHERE r.id_clanek = ?);";
        $res = $this->execute_query($q,$params);
        if(!$res) return null;
        return $res->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_recenzenti(): ?array{
        return $this->select_query(TB_USERS,null,"id_pravo<4");
    }

    /**
     * ARticles TO REView
     * @param $id_reviewer
     * @return array|false|null
     */
    public function artorev(int $id_reviewer): ?array{
        $params=array($id_reviewer);
        //SELECT * FROM clanek c WHERE c.id_clanek IN (SELECT r.id_clanek FROM recenzenti r WHERE r.id_recenzent = $id_reviewer and r.hodnoceni is null);
        return $this->select_query(TB_ARTICLE." c",$params,
            "c.id_clanek IN (SELECT r.id_clanek FROM recenzenti r WHERE r.id_recenzent = ? and r.hodnoceni is null)");
    }

    /**
     * ARticles with ENough REViewers
     * gets all articles that have at least 3 reviewers (which is enough)
     * (gets the opposite set of articles from <code>get_articles_to_add_recenzenti_to</code>)
     */
    public function arenrev(): ?array{
        // (SELECT * FROM `clanek` WHERE schvalen=0) EXCEPT (SELECT * FROM nedostatek_recenzentu);
        $res = $this->execute_query("(SELECT * FROM ".TB_ARTICLE." WHERE schvalen=0) EXCEPT (SELECT * FROM ".VW_NEED_REVIEW.");",null);
        if(!$res) return null;
        return $res->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ARticle ALL REViewerS
     * @param $id_ar
     */
    public function arallrevs(int $id_ar): ?array{
        $params=array($id_ar);
        return $this->select_query(TB_REVIEW,$params,"id_clanek=?");
    }

    /**
     * ARticle ALL REViewerS with USER INFO
     * @param $id_ar
     * @return array|false|null
     */
    public function arallrevs_user_info(int $id_ar): ?array{
        $params = array($id_ar);
        //select u.jmeno, u.prijmeni, r.hodnoceni, r.poznamky from uzivatel u, recenzenti r where r.id_clanek = $id_ar and r.id_recenzent = u.id_uzivatel;
        $res = $this->execute_query(
            "select u.jmeno, u.prijmeni, r.hodnoceni, r.poznamky from
                  ".TB_USERS." u, ".TB_REVIEW." r 
                  where r.id_clanek = ? and r.id_recenzent = u.id_uzivatel;",$params);
        if(!$res) return null;
        return $res->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_accepted_articles(): ?array{
        return $this->select_query(TB_ARTICLE,null,"schvalen=1","datum_schvaleni","DESC");
    }

    public function get_latest_accepted_articles(): ?array{
        return $this->select_query(TB_ARTICLE,null,"schvalen=1 and datum_schvaleni>DATE_SUB(NOW(),INTERVAL 1 WEEK)");
    }


    //// INSERTS

    /**
     * generic insert query
     * returns whether successful
     */
    public function insert_query(string $tableName, ?array $p, string $insertStatement, string $insertValues): bool{
        $q = "INSERT INTO $tableName($insertStatement) VALUES ($insertValues);";
        $obj = $this->execute_query($q,$p);
        return ($obj != null);
    }

    private function addUser(string $fname,string $sname,string $login,string $mail,string $pwd, int $pravo): bool{
        $insert_statement = "id_pravo, login, jmeno, prijmeni, email, heslo";
        $pwd = password_hash($pwd,PASSWORD_DEFAULT);
        $p=array($pravo,$login,$fname,$sname,$mail,$pwd);
        $insert_values = "?,?,?,?,?,?";
       return $this->insert_query(TB_USERS,$p,$insert_statement,$insert_values);
    }

    public function register(string $fname,string $sname,string $login,string $mail,string $pwd): bool{
        return $this->addUser($fname,$sname,$login,$mail,$pwd,4);
    }

    public function addArticle(int $id_author,string $name,string $file_name,string $key_words,string $desc): bool{
        //insert into clanky(id_autor,nazev,nazev_souboru,klicova_clova,popis) values ($id_autor,$name,$file_name,$key_words,$desc)
        /*
         * insert into clanky(id_autor,nazev,nazev_souboru,klicova_clova,popis)
         * values ($id_autor,$name,$file_name,$key_words,' a'); update table uzivatel set jmeno='Kuba' where id_uzivatel=3; -- ')
         *
         * attack: a'); update uzivatel set jmeno='Kuba' where id_uzivatel=3; --
         */

        $insert_statement = "id_autor,nazev,nazev_souboru,klicova_slova,popis";
        $p = array($id_author,$name,$file_name,$key_words,$desc);
        $insert_values = "?,?,?,?,?";
        return $this->insert_query(TB_ARTICLE,$p,$insert_statement,$insert_values);
    }

    public function insert_recenzent(int $id_clanek,int $id_recenzent): bool{
        $statement = "id_clanek,id_recenzent";
        $p=array($id_clanek,$id_recenzent);
        $values = "?,?";
        return $this->insert_query(TB_REVIEW,$p,$statement,$values);
    }


    //// 
    
    /**
     * generic update query
     */
    private function update_query( string $tableName,?array $params, string $updateStatementWithValues, string $whereStatement): bool{
        $q = "UPDATE $tableName SET $updateStatementWithValues WHERE $whereStatement";
        $obj = $this->execute_query($q,$params);
        return ($obj != null);
    }

    public function update_rights(int $id_uzivatel,int $id_pravo): bool{
        $p=array($id_pravo,$id_uzivatel);
        return $this->update_query("uzivatel",$p,"id_pravo=?","id_uzivatel=?");
    }

    public function ban_user(int $id){
        $this->update_query(TB_USERS,array($id),"ban=true","id_uzivatel=?");
        //delete api keys
        $this->delete_query(TB_API_KEYS,array($id),"id_uzivatel=?");
    }

    /**
     * update ARticle DESCription
     * @param $id
     * @param $new_desc
     */
    public function update_ardesc(int $id,string $new_desc){
        // update clanky set popis='popis' where id_clanek=id
        // update clanky set popis='' where id_clanek=id

        $p=array($new_desc,$id);
        $this->update_query(TB_ARTICLE,$p,"popis=?","id_clanek=?");
    }

    /**
     * ARticle DECLine
     * @param $id
     * @return bool
     */
    public function ardecl(int $id): bool{
        return $this->update_query(TB_ARTICLE,array($id),"schvalen=2","id_clanek=?");
    }

    /**
     * update ARticle INfo
     * @param $id
     * @param $title
     * @param $key_words
     * @param $desc
     */
    public function update_arinfo(int $id,string $title,string $key_words,string $desc){
        $p=array($title,$key_words,$desc,$id);
        $this->update_query(TB_ARTICLE,$p,"nazev=?, klicova_slova=?, popis=?","id_clanek=?");
    }

    /**
     * update ARticle file path
     */
    public function update_arfilepath(int $id,string $new_file_name){
        $p=array($new_file_name,$id);
        $this->update_query(TB_ARTICLE,$p,"nazev_souboru=?","id_clanek=?");
    }

    /**
     * ARticle ACCept
     * @param $id
     * @return bool
     */
    public function aracc(int $id): bool{
        return $this->update_query(TB_ARTICLE,array($id),"schvalen=1, datum_schvaleni=NOW()","id_clanek=?");
    }

    /**
     * REView ARticle
     * @param $id_rev
     * @param $id_ar
     * @param $rev_val
     * @param $rev_desc
     * @return bool
     */
    public function revar(int $id_rev,int $id_ar,int $rev_val,string $rev_desc): bool{
        $vals = "hodnoceni=?, poznamky=?";
        $where = "id_clanek=? and id_recenzent=?";

        $p = array($rev_val,$rev_desc,$id_ar,$id_rev);
        // echo $vals . "     " . $where;
        return $this->update_query(TB_REVIEW,$p,$vals,$where);
    }

    //// DELETES

    /**
     * generic delete query
     */
    private function delete_query( string $tableName, ?array $params, string $whereStatement): bool{
        $q = "DELETE FROM $tableName WHERE $whereStatement";
        $obj = $this->execute_query($q,$params);
        return ($obj != null);
    }

    public function deleteUser(int $id_uzivatel){
        $this->delete_query(TB_USERS,array($id_uzivatel),"id_uzivatel = ?");
    }

    public function  deletArticle(int $id_clanek): bool{
        return $this->delete_query(TB_ARTICLE,array($id_clanek),"id_clanek = ?");
    }

    /**
     * remove reviews (and reviewers) of an article
     */
    public function delrevs(int $id_ar): bool{
        return $this->delete_query(TB_REVIEW,array($id_ar),"id_clanek=?");
    }


    //// API

    /**
     * create new API key after verification
     */
    public function new_auth_key(string $login,string $pwd,int $expiration): string{
        $user = $this->get_user_data($login);
        if(!$user) return self::UNKNOWN_LOGIN;
        if($user["ban"]) return self::BANNED;
        if($this->verify_user_knowing_hash($pwd,$user["heslo"]) != self::SUCCESS) return self::WRONG_PASSWORD;

        // delete old key
        $this->delete_query(TB_API_KEYS,array($user["id_uzivatel"]),"id_uzivatel=?");
        // create new key
        $key = bin2hex(random_bytes(16));
        $params = array($key,$user["id_uzivatel"],time() + $expiration);
        $this->insert_query(TB_API_KEYS,$params,"klic, id_uzivatel, expirace","?,?,?");
        return $key;
    }

    /**
     * get API key of a user
     */
    public function get_api_key_by_id(int $id): array{
        $params = array($id);
        return $this->select_query(TB_API_KEYS,$params,"id_uzivatel=?");
    }

    /**
     * Check the key is valid: not expired and has sufficient rights (same as owner)
     */
    public function verify_key(string $key,int $rights): bool{
        $params = array($key);
        $res = $this->select_query(VW_API_RIGHTS,$params,"klic=?");
        if(!$res) return false;
        if(count($res) == 0) return false;
        return $res[0]["expirace"] > time() && $res[0]["prava"] >= $rights;
    }

}