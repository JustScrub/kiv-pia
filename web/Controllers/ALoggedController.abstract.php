<?php

namespace conference\Controllers;

use conference\Models\Session_Model;

abstract class ALoggedController extends AController
{
    // pages for logged in users are ranked by rights
    // only logged users with the apropriate rights can
    // access the site
    protected int $min_rights;
    
    public function __construct(Twig\Environment $twig, conference\Models\DB_Model $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->view_data["reason"] = "none"; // common template parameter for logged pages: reason for prohibiting access
        $this->min_rights = 1; // superadmin only
    }

    /**
     * checks whether the user is logged
     * and if not, sets access prohibition reason
     * and the view template to the Access denied page
     */
    public function logged_check(){
        if(!$this->session->is_logged()){
            $this->VIEW = "Access_denied.view.twig";
            $this->view_data["reason"] = "login";
        }
    }

    /**
     * checks the rights of the user
     * and if they are lower (numerically higher), denies access
     */
    public function rights_check($rights){
        // View might have been set previously by logged_check() -- in that case don't compare rights (unlogged user has none)
        if(!$this->VIEW and $this->session->get(Session_Model::USER_RIGHTS)>$rights){
            $this->VIEW = "Access_denied.view.twig";
            $this->view_data["reason"] = "rights";
        }
    }

    /**
     * overloads the initilaization of the subclassed controllers
     * adds the above checks to the init
     */
    public function init(){
        parent::init();
        $this->logged_check();
        $this->rights_check($this->min_rights);
    }

}