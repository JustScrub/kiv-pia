<?php

namespace conference\Controllers;

use conference\Models\Session_Model;
use conference\Models\DB_Model;

class UserManagement extends AccountManager
{

    public function __construct(\Twig\Environment $twig, DB_model $pdo)
    {
        parent::__construct($twig, $pdo);
        // data for superclass
        $this->ofWhatManagement = array("cz" => "UŽIVATELŮ", "en" => "USERS");
        $this->get_all = "get_all_users";
        $this->def_view = "UserManagement.view.twig";
        $this->min_rights = 2; // admins
        $this->titles = array("cz" => "Správa uživatelů", "en" => "User management");
    }
}