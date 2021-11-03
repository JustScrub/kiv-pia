<?php
namespace conference\view;

function common_header($title){
    ?>
    <!doctype html>
    <html lang="en">
        <head>
            <title><?= $title ?></title>
        </head>
        <body>
    <?php
}


function common_footer(){
    ?>
        </body>
    </html>
    <?php
}


?>