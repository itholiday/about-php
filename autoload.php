<?php
    define('ROOT',str_replace('\\','/',__DIR__) . '/');
    define('CORE',ROOT . 'core/');

    function autoloadCore($class_name){
        $file = CORE . "$class_name.class.php";
        if(is_file($file)){
            include_once $file;
        }
    }
    function autoloadVendor($class_name){
        $file = ROOT . "smarty/$class_name.class.php";
        if(is_file($file)){
            include_once $file;
        }
    }

    spl_autoload_register('autoloadCore');
    spl_autoload_register('autoloadVendor');