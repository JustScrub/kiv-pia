<?php

namespace conference\Controllers;

use conference\Models\Session_Model;
use conference\Models\DB_Model;

class ReviewArticle extends ALoggedController
{

    public function __construct(\Twig\Environment $twig, DB_model $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->min_rights = 3; // reviewers
        $this->view_data["title"] = "Recenzovat";
        $this->titles = array("cz" => "Recenzovat", "en" => "Review");
    }

    public function do_stuff()
    {
        $this->init();

        $this->process_form();

        if(!$this->VIEW){
            $this->VIEW = "ReviewArticle.view.twig";

            // get articles the current reviewer is assigned to
            $clanky = $this->pdo->artorev($this->session->get(Session_Model::USER_ID));

            //additional info for all articles: author's name
            foreach ($clanky as $idx => $clanek){
                $autor = $this->pdo->get_article_author($clanek["id_clanek"])[0];
                $autor = $autor["jmeno"]." ".$autor["prijmeni"];

                $clanky[$idx]["autor_jmeno"] = $autor;
            }

            $this->view_data["clanky"] = $clanky;
        }

        echo $this->twig->render($this->VIEW,$this->view_data);
    }

    /**
     * adding a review to an article form
     */
    private function process_form(){
        if(!isset( $_POST["add_review"] )) return;

        $this->pdo->revar($this->session->get(Session_Model::USER_ID),
                          $_POST["id_clanek"],$_POST["rev_val"],$_POST["review_comment"]);
    }
}