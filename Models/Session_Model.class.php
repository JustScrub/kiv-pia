<?php

namespace conference\Models;

const USER_ID = "id_uzivatel";
const USER_NAME = "jmeno_a_prijmeni";
const USER_RIGHTS = "pravo";

class Session_Model
{

    public function __construct()
    {
        session_start();
    }

    public function set($key,$val){
        $_SESSION[$key] = $val;
    }

    public function get($key){
        if($this->is_set($key))
            return $_SESSION[$key];
        else return null;
    }

    public function get_user_data(){
        $userdata = array();
        foreach($_SESSION as $key => $val)
            $userdata[$key] = $val;
        return $userdata;
    }

    public function is_set($key){
        return isset($_SESSION[$key]);
    }

    public function remove($key){
        unset($_SESSION[$key]);
    }

    public function login($user_data){
        $this->set(USER_ID,$user_data["id_uzivatel"]);
        $this->set(USER_NAME,"$user_data[jmeno] $user_data[prijmeni]");
        $this->set(USER_RIGHTS,$user_data["pravo"]);
    }

    public function logout(){
        foreach( $_SESSION as $key => $_){
            $this->remove($key);
        }
    }

    public function is_logged(){
        return $this->is_set(USER_ID);
    }

}