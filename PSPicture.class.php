<?php

/*
图片处理函数功能：缩放、剪切、相框、水印、锐化、旋转、翻转、透明度、反色
处理并保存历史记录的思路：当有图片有改动时自动生成一张新图片，命名方式可以考虑在原图片的基础上加上步骤，例如：图片名称+__第几步
*/

class PSPicture
{
    public $picture_url;//要处理的图片
    public $dest_url = "temp__01.jpg";//生成目标图片位置
    public $picture_create;//要创建的图片
    public $ture_color;//新建一个真彩图象

    public $picture_width;//原图片宽度
    public $picture_height;//原图片高度

    /*
    水印的类型，默认的为水印文字
    */
    public $mark_type = 1;
    public $word;//经过UTF-8后的文字
    public $word_x;//文字横坐标
    public $word_y;//文字纵坐标
    public $font_type;//字体类型
    public $font_size = "12";//字体大小
    public $font_word;//文字
    public $angle = 0;//文字的角度，默认为0
    public $font_color = "#000000";//文字颜色
    public $font_path = "font/simkai.ttf";//字体库，默认为宋体
    public $force_url;//水印图片
    public $force_x = 0;//水印横坐标
    public $force_y = 0;//水印纵坐标
    public $force_start_x = 0;//切起水印的图片横坐标
    public $force_start_y = 0;//切起水印的图片纵坐标

    public $picture_type;//图片类型
    public $picture_mime;//输出的头部

    /*
    缩放比例为1的话就按缩放高度和宽度缩放
    */
    public $zoom = 1;//缩放类型
    public $zoom_multiple;//缩放比例
    public $zoom_width;//缩放宽度
    public $zoom_height;//缩放高度

    /*
    裁切，按比例和固定长度、宽度
    */
    public $cut_type = 1;//裁切类型
    public $cut_x = 0;//裁切的横坐标
    public $cut_y = 0;//裁切的纵坐标
    public $cut_width;//裁切的宽度
    public $cut_height = 100;//裁切的高度

    /*
    锐化
    */
    public $sharp = "7.0";//锐化程度

    /*
    透明度处理
    */
    public $alpha = '100';//透明度在0-127之间
    public $alpha_x = "90";
    public $alpha_y = "50";

    /*
    任意角度旋转
    */
    public $circumrotate = "90.0";//注意，必须为浮点数

    /*
    出错信息
    */
    public $error = array(
        'unalviable' => '没有找到相关图片!'
    );

    /*
    构造函数：函数初始化
    */
    function __construct($picture_url)
    {
        $this->get_info($picture_url);
    }

    function get_info($picture_url)
    {
        /*
        处理原图片的信息,先检测图片是否存在,不存在则给出相应的信息
        */
        @$SIZE = getimagesize($picture_url);
        if (!$SIZE) {
            exit($this->error['unalviable']);
        }

        //得到原图片的信息类型、宽度、高度
        $this->picture_mime = $SIZE['mime'];
        $this->picture_width = $SIZE[0];
        $this->picture_height = $SIZE[1];

        //创建图片
        switch ($SIZE[2]) {
            case 1:
                $this->picture_create = imagecreatefromgif($picture_url);
                $this->picture_type = "imagejpeg";
                $this->PICTURE_ExT = "jpg";
                break;
            case 2:
                $this->picture_create = imagecreatefromjpeg($picture_url);
                $this->picture_type = "imagegif";
                $this->PICTURE_ExT = "gif";
                break;
            case 3:
                $this->picture_create = imagecreatefrompng($picture_url);
                $this->picture_type = "imagepng";
                $this->PICTURE_ExT = "png";
                break;
        }

        /*
        文字颜色转换16进制转换成10进制
        */
        preg_match_all("/([0-f]){2,2}/i", $this->font_color, $MATCHES);
        if (count($MATCHES) == 3) {
            $this->RED = hexdec($MATCHES[0][0]);
            $this->GREEN = hexdec($MATCHES[0][1]);
            $this->BLUE = hexdec($MATCHES[0][2]);
        }
    }

    #end of __construct

    /*
    将16进制的颜色转换成10进制的（R，G，B）
    */
    function hex2dec()
    {
        preg_match_all("/([0-f]){2,2}/i", $this->font_color, $MATCHES);
        if (count($MATCHES) == 3) {
            $this->RED = hexdec($MATCHES[0][0]);
            $this->GREEN = hexdec($MATCHES[0][1]);
            $this->BLUE = hexdec($MATCHES[0][2]);
        }
    }

    //缩放类型
    function zoom_type($zoom_type)
    {
        $this->zoom = $zoom_type;
    }

    //对图片进行缩放,如果不指定高度和宽度就进行缩放
    function zoom()
    {
        //缩放的大小
        if ($this->zoom == 0) {
            $this->zoom_width = $this->picture_width * $this->zoom_multiple;
            $this->zoom_height = $this->picture_height * $this->zoom_multiple;
        }
        //新建一个真彩图象
        $this->true_color = imagecreatetruecolor($this->zoom_width, $this->zoom_height);
        $WHITE = imagecolorallocate($this->true_color, 255, 255, 255);
        imagefilledrectangle($this->true_color, 0, 0, $this->zoom_width, $this->zoom_height, $WHITE);
        imagecopyresized($this->true_color, $this->picture_create, 0, 0, 0, 0, $this->zoom_width, $this->zoom_height, $this->picture_width, $this->picture_height);
    }

    #end of zoom
    //裁切图片,按坐标或自动
    function cut()
    {
        $this->true_color = imagecreatetruecolor($this->cut_width, $this->cut_width);
        imagecopy($this->true_color, $this->picture_create, 0, 0, $this->cut_x, $this->cut_y, $this->cut_width, $this->cut_height);
    }

    #end of cut
    /*
    在图片上放文字或图片
    水印文字
    */
    function watermark_text()
    {
        $this->true_color = imagecreatetruecolor($this->picture_width, $this->picture_height);
        $this->word = mb_convert_encoding($this->font_word, 'utf-8', 'gb2312');
        /*
        取得使用 Truetype 字体的文本的范围
        */
        $TEMP = imagettfbbox($this->font_size, 0, $this->font_path, $this->word);
        $word_LENGTH = strlen($this->word);
        $word_width = $TEMP[2] - $TEMP[6];
        $word_height = $TEMP[3] - $TEMP[7];
        /*
        文字水印的默认位置为右下角
        */
        if ($this->word_x == "") {
            $this->word_x = $this->picture_width - $word_width;
        }
        if ($this->word_y == "") {
            $this->word_y = $this->picture_height - $word_height;
        }
        imagesettile($this->true_color, $this->picture_create);
        imagefilledrectangle($this->true_color, 0, 0, $this->picture_width, $this->picture_height, IMG_COLOR_TILED);
        $TExT2 = imagecolorallocate($this->true_color, $this->RED, $this->GREEN, $this->Blue);
        imagettftext($this->true_color, $this->font_size, $this->angle, $this->word_x, $this->word_y, $TExT2, $this->font_path, $this->word);
    }

    /*
    水印图片
    */
    function watermark_picture()
    {
        /*
        获取水印图片的信息
        */
        @$SIZE = getimagesize($this->force_url);
        if (!$SIZE) {
            exit($this->error['unalviable']);
        }
        $force_PICTURE_;
        $force_picture_height = $SIZE[1];
        //创建水印图片
        switch ($SIZE[2]) {
            case 1:
                $force_picture_create = imagecreatefromgif($this->force_url);
                $force_picture_type = "gif";
                break;
            case 2:
                $force_picture_create = imagecreatefromjpeg($this->force_url);
                $force_picture_type = "jpg";
                break;
            case 3:
                $force_picture_create = imagecreatefrompng($this->force_url);
                $force_picture_type = "png";
                break;
        }
        /*
            判断水印图片的大小，并生成目标图片的大小，如果水印比图片大，则生成图片大小为水印图片的大小。否则生成的图片大小为原图片大小。
        */
        $this->NEW_PICTURE = $this->picture_create;
        if ($force_picture_width > $this->picture_width) {
            $create_width = $force_picture_width - $this->force_start_x;
        } else {
            $create_width = $force_picture_width;
        }

        if ($force_picture_height > $this->picture_height) {
            $create_height = $force_picture_height - $this->force_start_y;
        } else {
            $create_height = $this->picture_height;
        }
        /*
        创建一个画布
        */
        $new_picture_create = imagecreatetruecolor($CREATE_width, $create_height);
        $WHITE = imagecolorallocate($new_picture_create, 255, 255, 255);
        /*
        将背景图拷贝到画布中
        */
        imagecopy($new_picture_create, $this->picture_create, 0, 0, 0, 0, $this->picture_width, $this->picture_height);

        /*
        将目标图片拷贝到背景图片上
        */
        imagecopy($new_picture_create, $force_picture_create, $this->force_x, $this->force_y, $this->force_start_x, $this->force_start_y, $force_picture_width, $force_picture_height);
        $this->true_color = $new_picture_create;
    }

#end of mark

    function alpha_picture()
    {
        $this->true_color = imagecreatetruecolor($this->picture_width, $this->picture_height);
        $rgb = "#CDCDCD";
        $tran_color = "#000000";
        for ($j = 0; $j <= $this->picture_height - 1; $j++) {
            for ($i = 0; $i <= $this->picture_width - 1; $i++) {
                $rgb = imagecolorat($this->picture_create, $i, $j);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $now_color = imagecolorallocate($this->picture_create, $r, $g, $b);
                if ($now_color == $tran_color) {
                    continue;
                } else {
                    $color = imagecolorallocatealpha($this->picture_create, $r, $g, $b, $alpha);
                    imagesetpixel($this->picture_create, $alpha_x + $i, $alpha_y + $j, $color);
                }
                $this->true_color = $this->picture_create;
            }
        }
    }

    /*
    图片旋转:
    沿y轴旋转
    */
    function turn_y()
    {
        $this->true_color = imagecreatetruecolor($this->picture_width, $this->picture_height);
        for ($x = 0; $x < $this->picture_width; $x++) {
            imagecopy($this->true_color, $this->picture_create, $this->picture_width - $x - 1, 0, $x, 0, 1, $this->picture_height);
        }
    }

    /*
    沿x轴旋转
    */
    function turn_x()
    {
        $this->true_color = imagecreatetruecolor($this->picture_width, $this->picture_height);
        for ($y = 0; $y < $this->picture_height; $y++) {
            imagecopy($this->true_color, $this->picture_create, 0, $this->picture_height - $y - 1, 0, $y, $this->picture_width, 1);
        }
    }

    /*
    任意角度旋转
    */
    function turn()
    {
        $this->true_color = imagecreatetruecolor($this->picture_width, $this->picture_height);
        imagecopyresized($this->true_color, $this->picture_create, 0, 0, 0, 0, $this->picture_width, $this->picture_height, $this->picture_width, $this->picture_height);
        $WHITE = imagecolorallocate($this->true_color, 255, 255, 255);
        $this->true_color = imagerotate($this->true_color, $this->circumrotate, $WHITE);
    }

    /*
    图片锐化
    */
    function sharp()
    {
        $this->true_color = imagecreatetruecolor($this->picture_width, $this->picture_height);
        $cnt = 0;
        for ($x = 0; $x < $this->picture_width; $x++) {
            for ($y = 0; $y < $this->picture_height; $y++) {
                $src_clr1 = @imagecolorsforindex($this->true_color, imagecolorat($this->picture_create, $x - 1, $y - 1));
                $src_clr2 = @imagecolorsforindex($this->true_color, imagecolorat($this->picture_create, $x, $y));
                $r = intval($src_clr2["red"] + $this->sharp * ($src_clr2["red"] - $src_clr1["red"]));
                $g = intval($src_clr2["green"] + $this->sharp * ($src_clr2["green"] - $src_clr1["green"]));
                $b = intval($src_clr2["blue"] + $this->sharp * ($src_clr2["blue"] - $src_clr1["blue"]));
                $r = min(255, max($r, 0));
                $g = min(255, max($g, 0));
                $b = min(255, max($b, 0));
                if (($DST_CLR = imagecolorexact($this->picture_create, $r, $g, $b)) == -1)
                    $DST_CLR = imagecolorallocate($this->picture_create, $r, $g, $b);
                $cnt++;
                if ($DST_CLR == -1) die("color allocate faile at $x, $y ($cnt).");
                imagesetpixel($this->true_color, $x, $y, $DST_CLR);
            }
        }
    }

    /*
       将图片反色处理??
    */
    function return_color()
    {
        /*
        创建一个画布
        */
        $new_picture_create = imagecreate($this->picture_width, $this->picture_height);
        $WHITE = imagecolorallocate($new_picture_create, 255, 255, 255);
        /*
        将背景图拷贝到画布中
        */
        imagecopy($new_picture_create, $this->picture_create, 0, 0, 0, 0, $this->picture_width, $this->picture_height);
        $this->true_color = $new_picture_create;
    }

    /*
    生成目标图片并显示
    */
    function show()
    {
        // 判断浏览器,若是IE就不发送头
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $ua = strtoupper($_SERVER['HTTP_USER_AGENT']);
            if (!preg_match('/^.*MSIE.*\)$/i', $ua)) {
                header("Content-type:$this->picture_mime");
            }
        }
        $OUT = $this->picture_type;
        $OUT($this->true_color);
    }

    /*
    生成目标图片并保存
    */
    function save_picture()
    {
        // 以 JPEG 格式将图像输出到浏览器或文件
        $OUT = $this->picture_type;
        if (function_exists($OUT)) {
            // 判断浏览器,若是IE就不发送头
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $ua = strtoupper($_SERVER['HTTP_USER_AGENT']);
                if (!preg_match('/^.*MSIE.*\)$/i', $ua)) {
                    header("Content-type:$this->picture_mime");
                }
            }
            if (!$this->true_color) {
                exit($this->error['unavilable']);
            } else {
                $OUT($this->true_color, $this->dest_url);
                $OUT($this->true_color);
            }
        }
    }

    /*
    析构函数：释放图片
    */
    function __destruct()
    {
        /*释放图片*/
        imagedestroy($this->true_color);
        imagedestroy($this->picture_create);
    }
#end of class
}

$pic = new PSPicture('a.jpg');
$pic->turn_x();
$pic->show();
