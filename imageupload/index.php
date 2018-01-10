<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
<form action="" method="post" enctype="multipart/form-data">
    <input type="file" name="image[]" multiple="multiple">
    <input type="submit">
</form>
</body>
</html>
<?php
/**
 * @author:haive
 */
include_once 'ImageUpload.class.php';
$up = new ImageUpload();
$up->upload('jpg|png',1024,'image');
$up->set_dir('upload/');
$up->set_thumb(100,50);
$up->set_watermark('upload/thumb_2017111302494165.png',6,80);
$up->execute();

?>