<?php

namespace conference\Controllers;

use conference\Models\Session_Model;

class UserManagement extends AccountManager
{

    public function __construct($twig, $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->view_data["title"] = "Správa uživatelů";
        $this->view_data["ofWhatManagement"] = "UŽIVATELŮ";
        $this->get_all = "get_all_users";
        $this->def_view = "UserManagement.view.twig";
        $this->min_rights = 2;
    }
}