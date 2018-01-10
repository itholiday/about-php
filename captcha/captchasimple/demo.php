<?php
require_once 'captcha.class.php';

$captcha = new Captcha(80,30,4);

$captcha->showImg();