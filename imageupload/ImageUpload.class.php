<?php
//视图表单

//支持多张图片上传 注意$field = 'image',表单name='image[]' multiple='multiple'
//水印位置1-6

class ImageUpload
{

    var $dir;            //附件存放物理目录
    var $time;           //自定义文件上传时间
    var $allow_types;    //允许上传附件类型
    var $field;          //上传控件名称
    var $maxsize;        //最大允许文件大小，单位为KB

    var $thumb_width;    //缩略图宽度
    var $thumb_height;   //缩略图高度

    var $watermark_file; //水印图片地址
    var $watermark_pos;  //水印位置
    var $watermark_trans;//水印透明度

    //构造函数
    //$types : 允许上传的文件类型 , $maxsize : 允许大小 ,  $field : 上传控件名称 , $time : 自定义上传时间
    function upload($types = 'jpg|png', $maxsize = 1024, $field = 'attach', $time = '')
    {
        $this->allow_types = explode('|', $types);
        $this->maxsize = $maxsize * 1024;
        $this->field = $field;
        $this->time = $time ? $time : time();
    }

    //设置并创建文件具体存放的目录
    //$basedir  : 基目录，必须为物理路径
    //$filedir  : 自定义子目录，可用参数{y}、{m}、{d}
    function set_dir($basedir, $filedir = '')
    {
        $dir = $basedir;
        !is_dir($dir) && @mkdir($dir, 0777);
        if (!empty($filedir)) {
            $filedir = str_replace(array('{y}', '{m}', '{d}'), array(date('Y', $this->time), date('m', $this->time), date('d', $this->time)), strtolower($filedir));//用string_replace把{y} {m} {d}几个标签进行替换
            $dirs = explode('/', $filedir);//拆目录
            foreach ($dirs as $d) {
                !empty($d) && $dir .= $d . '/';
                !is_dir($dir) && @mkdir($dir, 0777);//短路运算
            }
        }
        $this->dir = $dir;
    }

    //图片缩略图设置，如果不生成缩略图则不用设置
    //$width : 缩略图宽度 , $height : 缩略图高度
    function set_thumb($width = 0, $height = 0)
    {
        $this->thumb_width = $width;
        $this->thumb_height = $height;
    }

    //图片水印设置，如果不生成添加水印则不用设置
    //$file : 水印图片 , $pos : 水印位置 , $trans : 水印透明度
    function set_watermark($file, $pos = 6, $trans = 80)
    {
        $this->watermark_file = $file;
        $this->watermark_pos = $pos;
        $this->watermark_trans = $trans;
    }

    /*—————————————————————-
    执行文件上传，处理完返回一个包含上传成功或失败的文件信息数组，
    其中：name 为文件名，上传成功时是上传到服务器上的文件名，上传失败则是本地的文件名
    dir  为服务器上存放该附件的物理路径，上传失败不存在该值
    size 为附件大小，上传失败不存在该值
    flag 为状态标识，1表示成功，-1表示文件类型不允许，-2表示文件大小超出
    —————————————————————–*/
    function execute()
    {
        $files = array(); //成功上传的文件信息
        $field = $this->field;
        $keys = array_keys($_FILES[$field]['name']);
        $filedir = rtrim(str_replace('\\','/',$this->dir),'/') . '/';  //附件实际存放目录
        foreach ($keys as $key) {
            if (!$_FILES[$field]['name'][$key]) continue;

            $fileext = $this->fileext($_FILES[$field]['name'][$key]); //获取文件扩展名
            $filename = date('Ymdhis', $this->time) . mt_rand(10, 99) . '.' . $fileext; //生成文件名
            $filesize = $_FILES[$field]['size'][$key]; //文件大小

            //文件类型不允许
            if (!in_array($fileext, $this->allow_types)) {
                $files[$key]['name'] = $_FILES[$field]['name'][$key];
                $files[$key]['flag'] = -1;
                continue;
            }

            //文件大小超出
            if ($filesize > $this->maxsize) {
                $files[$key]['name'] = $_FILES[$field]['name'][$key];
                $files[$key]['name'] = $filesize;
                $files[$key]['flag'] = -2;
                continue;
            }

            $files[$key]['name'] = $filename;
            $files[$key]['dir'] = $filedir;
            $files[$key]['size'] = $filesize;
            //保存上传文件并删除临时文件
            if (is_uploaded_file($_FILES[$field]['tmp_name'][$key])) {
                move_uploaded_file($_FILES[$field]['tmp_name'][$key], $filedir .$filename);
                @unlink($_FILES[$field]['tmp_name'][$key]);
                $files[$key]['flag'] = 1;

                //对图片进行加水印和生成缩略图,这里演示只支持jpg和png(gif生成的话会没了帧的)
                if (in_array($fileext, array('jpg', 'png'))) {
                    if ($this->thumb_width) {
                        if ($this->create_thumb($filedir . $filename, $filedir . 'thumb_' . $filename)) {
                            $files[$key]['thumb'] = 'thumb_' . $filename;  //缩略图文件名
                        }
                    }
                    $this->create_watermark($filedir . $filename);
                }
            }
        }

        return $files;
    }

    //创建缩略图,以相同的扩展名生成缩略图
    //$src_file : 来源图像路径 , $thumb_file : 缩略图路径
    function create_thumb($src_file, $thumb_file)
    {
        $t_width = $this->thumb_width;
        $t_height = $this->thumb_height;

        if (!file_exists($src_file)) return false;

        $src_info = getimagesize($src_file);

        //如果来源图像小于或等于缩略图则拷贝源图像作为缩略图,免去操作
        if ($src_info[0] <= $t_width && $src_info[1] <= $t_height) {
            if (!copy($src_file, $thumb_file)) {
                return false;
            }
            return true;
        }

        //按比例计算缩略图大小
        if (($src_info[0] - $t_width) > ($src_info[1] - $t_height)) {
            $t_height = ($t_width / $src_info[0]) * $src_info[1];
        } else {
            $t_width = ($t_height / $src_info[1]) * $src_info[0];
        }

        //取得文件扩展名
        $fileext = $this->fileext($src_file);

        switch ($fileext) {
            case 'jpg' :
                $src_img = imagecreatefromjpeg($src_file);
                break;
            case 'png' :
                $src_img = imagecreatefrompng($src_file);
                break;
            case 'gif' :
                $src_img = imagecreatefromgif($src_file);
                break;
        }

        //创建一个真彩色的缩略图像
        $thumb_img = @imagecreatetruecolor($t_width, $t_height);

        //ImageCopyResampled函数拷贝的图像平滑度较好，优先考虑
        if (function_exists('imagecopyresampled')) {
            @imagecopyresampled($thumb_img, $src_img, 0, 0, 0, 0, $t_width, $t_height, $src_info[0], $src_info[1]);
        } else {
            @imagecopyresized($thumb_img, $src_img, 0, 0, 0, 0, $t_width, $t_height, $src_info[0], $src_info[1]);
        }

        //生成缩略图
        switch ($fileext) {
            case 'jpg' :
                imagejpeg($thumb_img, $thumb_file);
                break;
            case 'gif' :
                imagegif($thumb_img, $thumb_file);
                break;
            case 'png' :
                imagepng($thumb_img, $thumb_file);
                break;
        }

        //销毁临时图像
        @imagedestroy($src_img);
        @imagedestroy($thumb_img);

        return true;

    }

    //为图片添加水印
    //$file : 要添加水印的文件
    function create_watermark($file, &$error = '')
    {

        //文件不存在则返回
        if (!file_exists($this->watermark_file) || !file_exists($file)) {
            $error = '文件不存在';
            return false;
        }
        if (!function_exists('getimagesize')) return false;

        //检查GD支持的文件类型
        $gd_allow_types = array();
        if (function_exists('imagecreatefromgif')) $gd_allow_types['image/gif'] = 'imagecreatefromgif';
        if (function_exists('imagecreatefrompng')) $gd_allow_types['image/png'] = 'imagecreatefrompng';
        if (function_exists('imagecreatefromjpeg')) $gd_allow_types['image/jpeg'] = 'imagecreatefromjpeg';

        //获取文件信息
        $fileinfo = getimagesize($file);
        $wminfo = getimagesize($this->watermark_file);

        if ($fileinfo[0] < $wminfo[0] || $fileinfo[1] < $wminfo[1]) return false;

        if (array_key_exists($fileinfo['mime'], $gd_allow_types)) {
            if (array_key_exists($wminfo['mime'], $gd_allow_types)) {

                //从文件创建图像
                $temp = $gd_allow_types[$fileinfo['mime']]($file);
                $temp_wm = $gd_allow_types[$wminfo['mime']]($this->watermark_file);

                //水印位置
                switch ($this->watermark_pos) {
                    case 1 :  //顶部居左
                        $dst_x = 0;
                        $dst_y = 0;
                        break;
                    case 2 :    //顶部居中
                        $dst_x = ($fileinfo[0] - $wminfo[0]) / 2;
                        $dst_y = 0;
                        break;
                    case 3 :  //顶部居右
                        $dst_x = $fileinfo[0];
                        $dst_y = 0;
                        break;
                    case 4 :  //底部居左
                        $dst_x = 0;
                        $dst_y = $fileinfo[1];
                        break;
                    case 5 :  //底部居中
                        $dst_x = ($fileinfo[0] - $wminfo[0]) / 2;
                        $dst_y = $fileinfo[1];
                        break;
                    case 6 :  //底部居右
                        $dst_x = $fileinfo[0] - $wminfo[0];
                        $dst_y = $fileinfo[1] - $wminfo[1];
                        break;
                    default : //随机
                        $dst_x = mt_rand(0, $fileinfo[0] - $wminfo[0]);
                        $dst_y = mt_rand(0, $fileinfo[1] - $wminfo[1]);
                }

                if (function_exists('ImageAlphaBlending')) imagealphablending($temp_wm, True); //设定图像的混色模式
                if (function_exists('ImageSaveAlpha')) imagesavealpha($temp_wm, True); //保存完整的 alpha 通道信息

                //为图像添加水印
                if (function_exists('imageCopyMerge')) {
                    imagecopymerge($temp, $temp_wm, $dst_x, $dst_y, 0, 0, $wminfo[0], $wminfo[1], $this->watermark_trans);
                } else {
                    imagecopymerge($temp, $temp_wm, $dst_x, $dst_y, 0, 0, $wminfo[0], $wminfo[1]);
                }

                //保存图片
                switch ($fileinfo['mime']) {
                    case 'image/jpeg' :
                        @imagejpeg($temp, $file);
                        break;
                    case 'image/png' :
                        @imagepng($temp, $file);
                        break;
                    case 'image/gif' :
                        @imagegif($temp, $file);
                        break;
                }
                //销毁零时图像
                @imagedestroy($temp);
                @imagedestroy($temp_wm);
            }
        }
    }

    //获取文件扩展名
    function fileext($filename)
    {
        return strtolower(substr(strrchr($filename, '.'), 1, 10));
    }
}

