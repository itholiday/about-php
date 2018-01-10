<?php
    return array(
        'host' => 'localhost',
        'username' => 'root',
        'password' => '12345',
        'dbname' => 'imp',
        'options' => array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8',
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ),
    );