<?php
/**
 * 图片加水印（适用于png/jpg/gif格式）
 *
 * @author flynetcn
 *
 * @param $srcImg 原图片
 * @param $waterImg 水印图片
 * @param $savepath 保存路径
 * @param $savename 保存名字
 * @param $positon 水印位置
 * 1:顶部居左, 2:顶部居右, 3:居中, 4:底部局左, 5:底部居右
 * @param $alpha 透明度 -- 0:完全透明, 100:完全不透明
 *
 * @return 成功 -- 加水印后的新图片地址
 *          失败 -- -1:原文件不存在, -2:水印图片不存在, -3:原文件图像对象建立失败
 *          -4:水印文件图像对象建立失败 -5:加水印后的新图片保存失败
 */
function img_water_mark($srcImg, $waterImg, $savepath=null, $savename=null, $positon=5, $alpha=30)
{
    $temp = pathinfo($srcImg);
    $name = $temp['basename'];
    $path = $temp['dirname'];
    $exte = $temp['extension'];
    $savename = $savename ? $savename : $name;
    $savepath = $savepath ? $savepath : $path;
    $savefile = $savepath .'/'. $savename;
    $srcinfo = @getimagesize($srcImg);
    if (!$srcinfo) {
        return -1; //原文件不存在
    }
    $waterinfo = @getimagesize($waterImg);
    if (!$waterinfo) {
        return -2; //水印图片不存在
    }
    $srcImgObj = image_create_from_ext($srcImg);
    if (!$srcImgObj) {
        return -3; //原文件图像对象建立失败
    }
    $waterImgObj = image_create_from_ext($waterImg);
    if (!$waterImgObj) {
        return -4; //水印文件图像对象建立失败
    }
    switch ($positon) {
        //1顶部居左
        case 1: $x=$y=0; break;
        //2顶部居右
        case 2: $x = $srcinfo[0]-$waterinfo[0]; $y = 0; break;
        //3居中
        case 3: $x = ($srcinfo[0]-$waterinfo[0])/2; $y = ($srcinfo[1]-$waterinfo[1])/2; break;
        //4底部居左
        case 4: $x = 0; $y = $srcinfo[1]-$waterinfo[1]; break;
        //5底部居右
        case 5: $x = $srcinfo[0]-$waterinfo[0]; $y = $srcinfo[1]-$waterinfo[1]; break;
        default: $x=$y=0;
    }
    imagecopymerge($srcImgObj, $waterImgObj, $x, $y, 0, 0, $waterinfo[0], $waterinfo[1], $alpha);
    switch ($srcinfo[2]) {
        case 1: imagegif($srcImgObj, $savefile); break;
        case 2: imagejpeg($srcImgObj, $savefile); break;
        case 3: imagepng($srcImgObj, $savefile); break;
        default: return -5; //保存失败
    }
    imagedestroy($srcImgObj);
    imagedestroy($waterImgObj);
    return $savefile;
}


function image_create_from_ext($imgfile)
{
    $info = getimagesize($imgfile);
    $im = null;
    switch ($info[2]) {
        case 1: $im=imagecreatefromgif($imgfile); break;
        case 2: $im=imagecreatefromjpeg($imgfile); break;
        case 3: $im=imagecreatefrompng($imgfile); break;
    }
    return $im;
}