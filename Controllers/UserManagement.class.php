<?php

namespace conference\Controllers;

use conference\Models\Session_Model;

class UserManagement extends AccountManager
{

    public function __construct($twig, $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->ofWhatManagement = array("cz" => "UŽIVATELŮ", "en" => "USERS");
        $this->get_all = "get_all_users";
        $this->def_view = "UserManagement.view.twig";
        $this->min_rights = 2;
        $this->titles = array("cz" => "Správa uživatelů", "en" => "User management");
    }
}