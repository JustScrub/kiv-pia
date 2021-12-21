<?php

namespace conference\Controllers;

class AddReviewer extends ALoggedController
{

    public function __construct($twig, $pdo)
    {
        parent::__construct($twig, $pdo);
        $this->min_rights=2;
        $this->view_data["title"] = "Přidat recenzenta";
    }

    public function do_stuff()
    {
        $this->init();

        $this->process_form();

        if(!$this->VIEW){
            $this->VIEW = "AddReviewer.view.twig";


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

    private function process_form(){
        if(isset( $_POST["add_reviewer"] )){
            if(!$this->pdo->insert_recenzent($_POST["id_clanek"],$_POST["id_recenzent"]))
                $this->view_data["alert"] = true;
        }
        else if( isset( $_POST["decl_article"] )){
            $this->pdo->ardecl($_POST["id_clanek"]);
        }


    }
}