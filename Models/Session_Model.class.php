<?php

namespace conference\Models;

class Session_Model
{
    const USER_ID = "id_uzivatel";
    const USER_NAME = "jmeno_a_prijmeni";
    const USER_RIGHTS = "pravo";
    const USER_LANG = "lang";

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

    /**
     * Get browser language, given an array of avalaible languages.
     * https://gist.github.com/joke2k/c8118e8179172f2f075f0f024ed379d2
     * 
     * @param  [array]   $availableLanguages  Avalaible languages for the site
     * @param  [string]  $default             Default language for the site
     * @return [string]                       Language code/prefix
     */
    private function get_browser_language( $default = 'en' ) {
        if ( isset( $_SERVER[ 'HTTP_ACCEPT_LANGUAGE' ] ) ) {

            $langs = explode( ',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
            $available = array("en" => "en","cs" => "cz", "cz" => "cz");

            foreach ( $langs as $lang ){
                $lang = substr( $lang, 0, 2 );
                if( array_key_exists($lang,$available) ){ {
                    return $available[$lang];
                    }
                }
            }
        }
        return $default;
    }

    public function set_lang($lang = null){
        $lang = $lang ?? $this->get_browser_language();
        $this->set(self::USER_LANG,$lang);
    }

    //user_data: data as passed by DB_Model from get_user_data
    public function login($user_data){
        if(!$user_data) return;
        $this->set(self::USER_ID,$user_data["id_uzivatel"]);
        $this->set(self::USER_NAME,"$user_data[jmeno] $user_data[prijmeni]");
        $this->set(self::USER_RIGHTS,$user_data["id_pravo"]);
    }

    public function logout(){
        foreach( $_SESSION as $key => $_){
            if ($key != self::USER_LANG)
                $this->remove($key);
        }
    }

    public function is_logged(){
        return $this->is_set(self::USER_ID);
    }

}