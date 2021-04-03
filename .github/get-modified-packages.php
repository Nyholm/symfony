<?php

if (3 > $_SERVER['argc']) {
    echo "Usage: app-packages modified-files\n";
    //exit(1);
}

var_dump($_SERVER['argv']);
//chdir(dirname(__DIR__));
