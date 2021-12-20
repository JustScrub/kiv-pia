<?php

namespace conference\Models;

class Logger_Model
{
    //All functions return $this for chaining

    const LOG_FILE = ROOT_DIR."/LOG.txt";
    private $file;

    public function __construct()
    {
        $this->file = fopen(self::LOG_FILE, "a");

        return $this;
    }

    public function log($log){
        $date = gmdate("Y-m-d H:i:s");
        fwrite($this->file, "$date\t$log\n");

        return $this;
    }

    public function destruct(){
        if($this->file)
            fclose($this->file);
    }

}