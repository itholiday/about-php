<?php
	class MySQL{
		private $host;
		private $port;
		private $user;
		private $pass;
		private $charset;
		private $db;
        private static $instance;
		/**
		 * 初始化数据库连接 
		 * @param array $config 数据库连接信息
		 */
		private function __construct($config){
			$this->host = isset($config['host']) ? $config['host'] : 'localhost';
			$this->port = isset($config['port']) ? $config['port'] : 3306;
			$this->user = isset($config['user']) ? $config['user'] : 'root';
			$this->pass = isset($config['pass']) ? $config['pass'] : '12345';
			$this->charset = isset($config['charset']) ? $config['charset'] : 'utf8';
			$this->db = isset($config['db']) ? $config['db'] : '';
			$this->my_connect();
			$this->my_set_charset();
			$this->my_select_db();
		}
		public function __destruct(){
			@mysql_close($this->link);
		}
		public function my_query($sql){
			if(!$res = mysql_query($sql)){
				$this->my_error();
			}
			return $res;
		}

        /**
         * 单例模式
         * @param $config
         * @return MySQL
         */
		public static function getInstance($config){
		    if(!is_array($config)){
		        echo '参数不正确';
            }else{
                if(!self::$instance instanceof self){
                    self::$instance = new self;
                }
                return self::$instance;
            }
        }
        private function __clone(){}

        /**
         * 设置允许查看的参数
         * @param $name
         */
		public function __get($name){
		    $allow_name = array('host','port','charset','db');
            if(in_array($name,$allow_name)){
                return $this->$name;
            }else{
                return false;
            }
        }

        /**
         * 设置参数
         * @param $name
         * @param $value
         */
        public function __set($name,$value){
            $allow_set = array('host','port','user','pass');
            if(in_array($name,$allow_set)){
                $this->$name = $value;
            }else{
                return false;
            }
        }
        public function __isset($name){
            $allow_set = array('host','port','user','pass','charset','db');
            if(in_array($name,$allow_set)){
                return true;
            }else{
                return false;
            }
        }
        public function __unset($name){
            return false;
        }
        public function __call($name,$args){
            return false;
        }
        public static function __callStatic($name, $arguments)
        {
            return false;
        }

        /**
         * 序列化
         * @return array
         */
        public function __sleep(){
            return array('localhost','port','user','pass','charset','db');
        }

        /**
         * 反序列化
         */
        public function __wakeup()
        {
            $this->my_connect();
            $this->my_set_charset();
            $this->my_select_db();
        }

        public function my_connect(){
			if(!$link = @mysql_connect("{$this->host}:{$this->port}",$this->user,$this->pass)){
				$this->my_error();
			}
			$this->link = $link;
		}
		private function my_set_charset(){
			mysql_set_charset($this->charset);
		}
		private function my_select_db(){
			if(!mysql_select_db($this->db)){
				$this->my_error();
			}
		}
		private function my_error(){
			echo '错误编号：' . mysql_errno(),'<br>';
			echo '错误信息：' . mysql_error(),'<br>';
			die;
		}
		/**
		 * 根据sql，查询数据，单条数据，一维数组
		 */
		public function fetchRow($sql){
			$res = my_query($sql);
			$row = mysql_fetch_assoc($res);
			mysql_free_result($res);
			return $row;
		}
		/**
		 * 单行单列
		 */
		public function fetchColumn($sql){
			$res = $this->my_query($sql);
			$row = mysql_fetch_row($res);
			mysql_free_result($res);
			return isset($row[0]) ? $row[0] : false;
		}
		/**
		 * 查询出所有数据,返回二维数组
		 * @return [type] [description]
		 */
		public function fetchAll($sql){
			$res = my_query($sql);
			$rows = array();
			while($row = mysql_fetch_row($res)){
				$rows[] = $row; 
			}
			mysql_free_result($res);
			return $rows;
		}
		/**
		 * 数据安全入库
		 * @param  数组 $_POST 表单数据
		 * @return [type]        [description]
		 */
		public function insert($table,$data){
			$sql = "insert into $table (";
			foreach ($data as $key => $value) {
				$sql .= "'$key'";
			}
			$sql = rtrim($sql,',') . ') values(';
			foreach ($data as $key => $value) {
				$sql .= "$value";
			}
			$sql = rtrim($sql,',') . ')';
			return my_query($sql);
		}
		/**
		 * $data = $db->my_select() 
		 * 所有数据
		 * @return [type] [description]
		 */
/*		public function select(){

		}*/
		/**
		 * 字段数据
		 * @param  [type] $fields [description]
		 * @return [type]         [description]
		 */
/*		public function fields($fields){ 
		    
		}*/
	}
