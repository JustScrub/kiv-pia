<?php

namespace conference\Controllers;

class Intro extends AController
{
    public function __construct($twig, $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->VIEW = "Intro.view.twig";
        $this->view_data["title"] = "Úvod";
    }

    public function do_stuff()
    {
        $this->init();

        $clanky = $this->pdo->get_latest_accepted_articles();
        //additional info for all articles: author's name and reviewers
        foreach ($clanky as $idx => $clanek){
            $autor = $this->pdo->get_article_author($clanek["id_clanek"])[0];
            $autor = $autor["jmeno"]." ".$autor["prijmeni"];

            $clanky[$idx]["autor_jmeno"] = $autor;
        }

        $this->view_data["clanky"] = $clanky;

        echo $this->twig->render($this->VIEW,$this->view_data);
    }
}