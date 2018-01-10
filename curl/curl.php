<?php
/**
 * @author:xiaojiang
 * curl 通用方法 。。get /post 传送数据
 */
class process{

  const GET = 0;
  const POST = 1;
  public $url;
  public $ch = null;
  private $type = 1;

  public function __construct( $url , $type = self::POST){
    $this->url = $url;
    $this->ch = curl_init();
    $this->type = $type;
  }

  //设置发送方式 0 get 1 post
  public function setType( $type  ){
    $this->tyep = $type;
  }
  //post 方式传递数据
  public function send( $param ){
    if( self::POST == $this->type)
      return $this->posts( $param );
    else
      return $this->gets( $param );
  }

  public function posts( $post_data ){

    curl_setopt( $this->ch, CURLOPT_URL, $this->url );
    curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt( $this->ch, CURLOPT_HEADER, 0 );
    curl_setopt($this->ch, CURLOPT_POST, 1);
    curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_data);
    $output = curl_exec( $this->ch );
    return $output;
  }

  public function gets( $get_data ){

    $url = $this->url.'?'.http_build_query($get_data);
    curl_setopt( $this->ch, CURLOPT_URL, $url );
    curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt( $this->ch, CURLOPT_HEADER, 0 );
    $output = curl_exec( $this->ch );
    return $output;

  }

}