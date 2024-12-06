<?php

namespace conference\Models;

class Logger_Model
{
    //All functions return $this for chaining

    const string LOG_FILE = ROOT_DIR."/LOG.txt"; // log file name
    private $file;

    public function __construct()
    {
        $this->file = fopen(self::LOG_FILE, "a");

        return $this;
    }

    /**
     * log a message to the file
     */
    public function log($log): Logger_Model{
        $date = gmdate("Y-m-d H:i:s");
        fwrite($this->file, "$date\t$log\n");

        return $this;
    }

    public function destruct(){
        if($this->file)
            fclose($this->file);
    }

}