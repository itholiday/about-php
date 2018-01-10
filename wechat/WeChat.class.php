<?php 
/*
php5.6下运行正常，低版本需要去掉curl_setopt ( $ch, CURLOPT_SAFE_UPLOAD, false);
php5.3以前，curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);有1选项值
 */
class WeChat{
	private $_appid;
	private $_appsecret;
	private $_token;
	const QRCODE_TYPE_TEMP = 1;
	const QRCODE_TYPE_LIMIT = 2;
	const QRCODE_TYPE_LIMIT_STR = 3;
	private $_msg_template = array(
		'text' => '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[%s]]></Content></xml>',//文本回复XML模板
		'image' => '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[image]]></MsgType><Image><MediaId><![CDATA[%s]]></MediaId></Image></xml>',//图片回复XML模板
		'music' => '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[music]]></MsgType><Music><Title><![CDATA[%s]]></Title><Description><![CDATA[%s]]></Description><MusicUrl><![CDATA[%s]]></MusicUrl><HQMusicUrl><![CDATA[%s]]></HQMusicUrl><ThumbMediaId><![CDATA[%s]]></ThumbMediaId></Music></xml>',//音乐模板
		'news' => '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[news]]></MsgType><ArticleCount>%s</ArticleCount><Articles>%s</Articles></xml>',// 新闻主体
		'news_item' => '<item><Title><![CDATA[%s]]></Title><Description><![CDATA[%s]]></Description><PicUrl><![CDATA[%s]]></PicUrl><Url><![CDATA[%s]]></Url></item>',//某个新闻模板
		);
	public function __construct($id,$secret,$token){
		$this->_appid = $id;
		$this->_appsecret = $secret;
        $this->_token = $token;
	}
	/*
	菜单删除
	 */
	public function menuDelete(){
		$url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token='.$this->getAccessToken();
		$result = $this->_requestGet($url);
		$result_Obj = json_decode($result);
		if($result_Obj->errcode == 0){
			return true;
		}else{
			return false;
		}
	}
	/*
	设置菜单
	 */
	public function menuSet($menu){
		$url = ' https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$this->getAccessToken;
		$result = $this->_requestPost($url,$menu);
		$result_Obj = json_decode($result);
		if($result_Obj->errcode == 0){
			return true;
		}else{
			echo $result->errmsg,'<br>';
			return false;
		}
	}
	public function responseMSG(){
		//获取XML请求字符串,不是键值型数据
		$xml_str = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents("php://input");
		if(empty($xml_str)){
			die('');//空的话就不需要处理，结束脚本
		}
		//simpleXML解析数据
		libxml_disable_entity_loader(true);//防止xml注入攻击,定义实体写入数据会造成攻击。禁止xml实例解析
		$request_xml = simplexml_load_string($xml_str,'SimpleXMLElement',LIBXML_NOCDATA);//从字符串中获取simplexml对象。
		//判断该消息的类型，MsgType(event)
		switch ($request_xml->MsgType) {
			case 'event':
				//判断具体事件类型(关注、取消关注、等等)
				$event = $request_xml->event;
				if('subscribe'==$event){
					$this->_doSubscribe($request_xml);
				}elseif('CLICK'==$event){
					$this->_doClick($request_xml);
				}elseif('VIEW'==$event){
					$this->_doView($request->xml);
				}
				break;
			case 'text':
				$this->_doText($request_xml);break;
			case 'image':
			$this->_doImage($request_xml);break;
			case 'voice':
			$this->_doVoice($request_xml);break;
			case 'video':
			$this->_doVideo($request_xml);break;
			case 'shotvideo':
			$this->_doSortVideo($request_xml);break;
			case 'location':
			$this->_doLocation($request_xml);break;
			case 'link':
			$this->_Link($request_xml);break;
			default:
				# code...
				break;
		}
		
	}
	private function _doClick($request_xml){
		$content = '你点击了菜单，携带的KEY为：'.$request_xml->EventKey;//与菜单中定义的KEY值相同
		$this->_msgText($request_xml->FromUserName,$request_xml->ToUserName,$content);
	}
	private function _doView($request_xml){//链接跳转事件

	}
	//处理订阅(关注)事件
	private function _doSubscribe($request_xml){
		//利用消息发送，完成向关注用户打招呼的功能
		$text = '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[%s]]></Content></xml>';
		$content = '感谢关注云帆科技！';
		$response = sprintf($text,$request_xml->FromUserName,$request_xml->ToUserName,time(),$content);
		die($response);
	}
	private function _doText($request_xml){
		$content = $request_xml->Content;
		if('?' == $content){
			$request_content = '输入相应的序号获取相应资源'."\n".'[1]PHP'."\n".'[2]Java'."\n".'[3]C++';
		}elseif('1'==strtolower($content) || 'php'==strtolower($content)){
			$response_content = 'PHP工程培训：'."\n".'http://php.itcast.cn';
		}elseif('2'==strtolower($content) || 'java'==strtolower($content)){
			$response_content = 'Java工程培训：'."\n".'http://java.itcast.cn';
		}elseif('3'==strtolower($content) || 'c++'==strtolower($content)){
			$response_content = 'C++工程培训：'."\n".'http://c.itcast.cn';
		}elseif ('图片' == $content) {
			$id_list = array(
				'eLrmGKbhf5kS86A9bqzkLS8-45sWvqBwUv4Q7XDd-oAds44Ad9hxq9h-ShmRQLyJ',
				'0Fnq-gYU8zDugqxjPNywkhW5KSHXT6DdF-NGovaPfKry8grmheEVdEkdeY8qjZ--');
			$rand_index = mt_rand(0, count($id_list)-1);
			// 具体那张图片，应该由业务逻辑决定

			$this->_msgImage($request_xml->FromUserName, $request_xml->ToUserName, $id_list[$rand_index], true);
		}elseif ('新闻' == $content) {
			$item_list = array(
				array('title'=>'其实你该用母亲的方式回报母亲', 'desc'=>'母亲节快乐', 'picurl'=>'http://demo.itholiday.cn/weixin/1.png', 'url'=>'http://www.soso.com/'),
				array('title'=>'母亲节宠爱不手软，黄金秒杀豪礼特惠值到爆', 'desc'=>'母亲节快乐', 'picurl'=>'http://demo.itholiday.cn/weixin/2.png', 'url'=>'http://www.soso.com/'),
				array('title'=>'浅从财富管理视角看巴菲特思想', 'desc'=>'母亲节快乐', 'picurl'=>'http://demo.itholiday.cn/weixin/3.png', 'url'=>'http://www.soso.com/'),
				array('title'=>'广邀好友打气，赢取万元旅游金', 'desc'=>'母亲节快乐', 'picurl'=>'http://demo.itholiday.cn/weixin/4.png', 'url'=>'http://www.soso.com/'),
				);
			$this->_msgNews($request_xml->FromUserName, $request_xml->ToUserName, $item_list);
		}elseif('图片' == $content){
			$file = '1.jpg';
			$this->_msgImage($request_xml->FromUserName, $request_xml->ToUserName,$file);
		}elseif('音乐' == $content){
			$music_url = 'http://www.itholiday.cn/Insomnia.mp3';
			$hq_music_url = null;
			$thumb_media_id = 'dK6SbWseEPF7Umh8vAhMdDNSimhe8NCOHRwlJLSk2LdDeL29mj3VCJQtFs0nhNZt';
			$this->_msgMusic($request_xml->FromUserName, $request_xml->ToUserName,$music_url,$hq_music_url,$thumb_media_id,$title='测试音乐',$desc='这是一段测试音乐');
		}
		else {
			// 通过小黄鸡，响应给微信用户
			$keyword = urlencode($content);
			$url = 'http://api.qingyunke.com/api.php?key=free&appid=0&msg='.$keyword; 
			$response = $this->_requestGet($url,false);
			$responseObj = json_decode($response,0);
			$response_content = $responseObj->content;
			$respoense_content = str_replace('{br}',"\n",$response_content);
		}
		$this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $response_content);
	}
	private function _doImage($request_xml){
		$content = '你上传的图片的Media_ID:'.$request_xml->MediaId;
		$this->_msgText($request_xml->FromUserName,$request_xml->ToUserName,$content);
	}
	// private function _doVoice($request_xml){
		
	// }
	// private function _doVideo($request_xml){
		
	// }
	// private function _doShotVideo($request_xml){
		
	// }
	private function _doLocation($request_xml){
	$content = '你的坐标为,经度:'.$request_xml->Location_Y.',纬度:'.$request_xml->Location_X . "\n" . '你所在的位置为：' . $request_xml->Label;
	//$this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $content);
	// 利用位置获取信息
	//百度LBS圆形范围查询：
	$url = 'http://api.map.baidu.com/place/v2/search?query=%s&page_size=10&page_num=0&scope=1&location=%s&radius=%s&output=%s&ak=%s';
	$query = '银行';
	$location = $request_xml->Location_X.','.$request_xml->Location_Y;
	$radius = 2000;
	$output = 'json';
	$ak = 'gI97idXpeeILEKeLE8YBsF3r5O7cWNkk';
	$url = sprintf($url,urlencode($query),$location,$radius,$output,$ak);
	$result = $this->_requestGet($url,false);
	$resultObj = json_decode($result);
	foreach($resultObj->results as $re){
		$r['name'] = $re->name;
		$r['address'] = $re->address;
		$re_list[] = implode('-',$r);
	}
	$re_str = implode("\n",$re_list);
	$this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $re_str);
	}
	private function _doLink($request_xml){
		
	}
	//发送文本信息
	private function _msgText($to,$from,$content){
		$response = sprintf($this->_msg_template['text'],$to,$from,time(),$content);
		die($response);
	}
	/*
	发送图片，$file是上传的图片文件
	 */
	private function _msgImage($to,$from,$file){
		//上传图片到微信公众服务器，获取mediaID
		$resultObj = $this->uploadTmp($file,'image');
		//拼凑发送图片类消息的xml文件imagexml
		 $response = sprintf($this->_msg_template['image'],$to,$from,time(),$resultObj->media_id);
		 echo $response;//die($response);
	}
	//发送音乐
	private function _msgMusic($to,$from,$music_url,$hq_music_url,$thumb_media_id,$title='',$desc=''){
		$response = sprintf($this->_msg_template['music'],$to,$from,time(),$title,$desc,$music_url,$hq_music_url,$thumb_media_id);
		die($response);
	}
	//发送图文信息
	private function _msgNews($to,$from,$item_list=array()){
		//拼凑文章
		$item_str = '';
		foreach($item_list as $item){
			$item_str .= sprintf($this->_msg_template['news_item'],$item['title'],$item['desc'],$item['picurl'],$item['url'] );
		}
		//拼凑图文部分
		$response = sprintf($this->_msg_template['news'],$to,$from,time(),count($item_list),$item_str);
		die($response);
	}
	/*
	上传临时素材media
	 */
	public function uploadTmp($file,$type){
		$access_token = $this->getAccessToken();
		$url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$access_token.'&type='.$type;
		$data = array(
			'media'=>'@'.$file,
			);
		//表示文件地址，5.6以前版本正常使用
		//(5.6：Deprecated: curl_setopt(): The usage of the @filename API for file uploading is deprecated. Please use the CURLFile class instead in )
		//
		$result = $this->_requestPost($url,$data);//返回json包含MEDIA_ID
		$result_Obj = json_decode($result);
		return $result_Obj;
	}
	private function _requestGet($url,$ssl=true){
		//开curl
		$curl = curl_init();
		curl_setopt ( $curl, CURLOPT_SAFE_UPLOAD, false); //5.6以后需要此代码才能上传文件
		curl_setopt($curl, CURLOPT_URL, $url);
		$user_agent = isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36';
		curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
		curl_setopt($curl, CURLOPT_REFERER, TRUE);
		curl_setopt($curl,CURLOPT_TIMEOUT,10);//设置超时时间
		if($ssl){
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			//禁止curl从服务器端验证
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
			//检查服务器SSL证书中是否存在一个公用名(common name)
		}
		curl_setopt($curl, CURLOPT_HEADER, FALSE);//是否处理相应头
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);//是否返回相应结果
		//发出请求
		$response = curl_exec($curl);
		if($response == false){
			echo '<br>',curl_error($curl),'<br>';
		}
		return $response;
	}
	private function _requestPost($url,$data,$ssl=true){
		//开curl
		$curl = curl_init();
		curl_setopt ( $curl, CURLOPT_SAFE_UPLOAD, false); //5.6以后需要此代码才能上传文件
		curl_setopt($curl, CURLOPT_URL, $url);
		$user_agent = isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36';
		curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
		curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE);
		curl_setopt($curl,CURLOPT_TIMEOUT,10);//设置超时时间，防止死掉
		if($ssl){
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			//禁止curl从服务器端验证
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
			//检查服务器SSL证书中是否存在一个公用名(common name)
		}
		//处理post相关选项
		curl_setopt($curl,CURLOPT_POST,true);//post请求
		curl_setopt($curl,CURLOPT_POSTFIELDS,$data);//处理数据
		//处理响应结果
		curl_setopt($curl, CURLOPT_HEADER, FALSE);//是否处理相应头
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);//是否返回相应结果
		//发出请求
		$response = curl_exec($curl);
		if($response == false){
			echo '<br>',curl_error($curl),'<br>';
		}
		return $response;
	}
	// 获取access_token
	public function getAccessToken($token_file='./access_token'){
		//考虑过期问题，将获取的access_token存储
		$life_time = 7200;
		if(file_exists($token_file) && time()-filemtime($token_file)<$life_time){
			return file_get_contents($token_file);
		}
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->_appid}&secret={$this->_appsecret}";
		$result = $this->_requestGet($url);
		if(!$result){
			return false;
		}
		$result_obj = json_decode($result,0);
		file_put_contents($token_file, $result_obj->access_token);
		return $result_obj->access_token;
	}
		/**
		* [getQRCodeTicket description]
		* @param $content 内容
		* @param $type qr码类型
		* @param ￥expire 有效期，永久的不需要此参数
		* @return string ticket
	 */
	private function _getQRCodeTicket($content,$type=2,$expire=604800){
		$access_token = $this->getAccessToken();
		$url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=$access_token";
		$type_list = array(
				self::QRCODE_TYPE_TEMP => 'QR_SCENE',
				self::QRCODE_TYPE_LIMIT => 'QR_LIMIT_SCENE',
				self::QRCODE_TYPE_LIMIT_STR => 'QR_LIMIT_STR_SCENE'
			);
		$action_name = $type_list[$type];
		switch($type){
			case self::QRCODE_TYPE_TEMP:
				$data_arr['expire_seconds'] = $expire;
				$data_arr['action_name'] = $action_name;
				$data_arr['action_info']['scene']['scene_id'] = $content;
				break;
			case self::QRCODE_TYPE_LIMIT:
			case self::QRCODE_TYPE_LIMIT_STR:
				$data_arr['action_name'] = $action_name;
				$data_arr['action_info']['scene']['scene_id'] = $content;
				break;
		}
		//$data = '{"expire_seconds": '.$expire.', "action_name": "'.$action_name.'", "action_info": {"scene": {"scene_id": "'.$content.'"}}}';
		$data = json_encode($data_arr);
		$result = $this->_requestPost($url,$data);
		if(!$result){
			return false;
		}
		$result_obj = json_decode($result);
		return $result_obj->ticket;
	}
    	/*
	用于第一次验证url合法性
	 */
    public function valid()  
    {  
        $echoStr = $_GET["echostr"];  
  
        //valid signature , option  
        if($this->_checkSignature()){  
            echo $echoStr;  
            exit;  
        }  
    }  
    private function _checkSignature()  
    {  
        $signature = $_GET["signature"];  
        $timestamp = $_GET["timestamp"];  
        $nonce = $_GET["nonce"];      
                  
        $token = $this->_token;  
        $tmpArr = array($token, $timestamp, $nonce);  
        sort($tmpArr);  
        $tmpStr = implode( $tmpArr );  
        $tmpStr = sha1( $tmpStr );  
          
        if( $tmpStr == $signature ){  
            return true;  
        }else{  
            return false;  
        }  
    }  
    public function getQRCode($content, $file=NULL, $type=2, $expire=604800){
		$ticket = $this->_getQRCodeTicket($content, $type=2, $expire=604800);
		$url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=$ticket";
		$result = $this->_requestGet($url);//此时result就是图像内容
		//echo $result;//返回图片内容
		if ($file) {
			//file_put_contents($file, $result);
		} else {
			header('Content-Type: image/jpeg');
			echo $result;
		}
	}
}
