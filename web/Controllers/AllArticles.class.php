<?php

namespace conference\Controllers;
use conference\Models\DB_Model;

class AllArticles extends AController
{

    public function __construct(\Twig\Environment $twig, DB_model $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->VIEW = "AllArticles.view.twig";
        $this->titles = array("cz" => "Články", "en" => "Articles");
    }

    public function do_stuff()
    {
        $this->init();

        $clanky = $this->pdo->get_accepted_articles();
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