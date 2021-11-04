<?php

namespace conference\Models;

use PDO;

class DB_Model
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = new PDO();
    }

}