<?php

namespace conference\Controllers;

use conference\Models\Session_Model;

class AdminManagement extends AccountManager
{

    public function __construct($twig, $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->view_data["title"] = "Správa adminů";
        $this->view_data["ofWhatManagement"] = "ADMINŮ";
        $this->get_all = "get_all_admins";
        $this->def_view = "AdminManagement.view.twig";
        $this->min_rights = 1;
    }

}