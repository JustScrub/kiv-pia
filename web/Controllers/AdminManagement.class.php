<?php

namespace conference\Controllers;

use conference\Models\Session_Model;

class AdminManagement extends AccountManager
{

    public function __construct(Twig\Environment $twig, DB_model $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->ofWhatManagement = array("cz" => "ADMINŮ", "en" => "ADMINS");
        $this->get_all = "get_all_admins";
        $this->def_view = "AdminManagement.view.twig";
        $this->min_rights = 1;
        $this->titles = array("cz" => "Správa adminů", "en" => "Admin management");
    }

}