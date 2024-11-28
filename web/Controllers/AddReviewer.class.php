<?php

namespace conference\Controllers;

class AddReviewer extends ALoggedController
{

    public function __construct(Twig\Environment $twig, DB_model $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->min_rights=2; // admins
        $this->titles = array("cz" => "Přidat recenzenta", "en" => "Add reviewer");
    }

    public function do_stuff()
    {
        $this->init();

        $this->process_form();

        if(!$this->VIEW){
            $this->VIEW = "AddReviewer.view.twig";

            // articles without enough reviewers
            $clanky = $this->pdo->get_articles_to_add_recenzenti_to();

            //additional info for all articles: author's name and reviewers
            foreach ($clanky as $idx => $clanek){
                $autor = $this->pdo->get_article_author($clanek["id_clanek"])[0];
                $autor = $autor["jmeno"]." ".$autor["prijmeni"];
                $recenzenti = $this->pdo->get_article_reviewers($clanek["id_clanek"]);

                $clanky[$idx]["autor_jmeno"] = $autor;
                $clanky[$idx]["recenzenti"] = $recenzenti;
            }
            //var_dump($clanky);

            $vsichni_recenzenti = $this->pdo->get_recenzenti();

            $this->view_data["clanky"] = $clanky;
            $this->view_data["vsichni_recenzenti"] = $vsichni_recenzenti;
        }


        echo $this->twig->render($this->VIEW,$this->view_data);
    }

    /**
     * adding reviewers to a article or declining it right away, without reviewing
     */
    private function process_form(){
        //add reviewer
        if(isset( $_POST["add_reviewer"] )){
            if(!$this->pdo->insert_recenzent($_POST["id_clanek"],$_POST["id_recenzent"]))
                $this->view_data["alert"] = true;
        }
        // decline article
        else if( isset( $_POST["decl_article"] )){
            $this->pdo->ardecl($_POST["id_clanek"]);
        }


    }
}