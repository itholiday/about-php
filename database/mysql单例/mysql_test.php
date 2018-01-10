<?php
	header('content-type:text/html;charset=utf8');
	include 'MySQL.class.php';
	$config = array(
			'host' => 'localhost',
			'user' => 'root',
			'pass' => '12345',
			'charset' => 'utf8',
			'db' => 'imp'
		);
	$db_obj = new MySQL($config);
	var_dump($db_obj);