<?php

/**
 * Class ObjectData
 *
 * a class designed to easily access and manipulate data with on abject with getters and setters
 *
 */
class ObjectData{

	/**
	 * Basic for object variables
	 */
	protected $has_data = false;
	protected $obj;

	/**
	 * Did this script have errors?
	 */
	protected $_errors = array();
	protected $_last_error = NULL;

	/**
	 * @TODO result sets were written and never tested RapidMod.com Aug 15th 2015
	 */
	protected $_result_set = array();
	protected $_result_set_count = 0;
	protected $_result_set_current = 0;
	protected $_result_set_started = NULL;

	/**
	 * to grab and create singletons
	 */
	private static $single_instances = array();
	protected $_called_class;
	private $is_singleton = false;



	public function _get($key = false){
		if(!empty($key) ){
			if(isset($this->obj->{$key})){
				return $this->obj->{$key};
			}
			return false;
		}else{
			return $this->get_object();
		}
	}
	public function _set($k,$v){
		if(empty($k)){return false;}
		$this->has_data = true;
		return $this->obj->{$k} = $v;
	}


	/**
	 * @param $object
	 */
	public function buildObject($object){
		if(empty($object)){return false;}
		foreach($object as $key => $value){
			$this->_set($key,$value);
		}
		return $this->toArray();
	}

	public function buildResultSet($array){
		$this->_result_set = array();
		$this->_result_set_count = 0;
		$this->_result_set_current = 0;
		$this->_result_set_started = NULL;
		if(!is_array($array) || empty($array)){
			$this->last_error(get_called_class()."::buildResultSet(array) must not be empty");
			return false;
		}else{

			foreach ($array as $item) {
				if(is_array($item)){
					$this->_result_set[] = $item;
				}else{
					$this->last_error(get_called_class()
						."::buildResultSet(array) corrupted item: ".implode(" --- ",$item));
				}

			}
			if(empty($this->_result_set)){
				$this->last_error(get_called_class()
					."::buildResultSet(array) no data was stored");
				return false;
			}
			$this->_result_set_count = count($this->_result_set);
			return true;

		}
	}

	public function get_object(){return $this->obj;}


	public function getData($key = false){
		if($key){return $this->_get($key);}
		else{return $this->toArray();}
	}

	public function hasData(){
		return $this->has_data;
	}


	/**
	 * @return array|bool
	 * untested
	 */
	public function next(){
		if(empty($this->_result_set)){
			return false;
		}
		if(empty($this->_result_set_started)){
			$this->_result_set_started = 1;
		}else{
			$this->_result_set_current++;
		}

		if($this->_result_set_current > $this->_result_set_count){
			return false;
		}
		if(
			isset($this->_result_set[$this->_result_set_current])
			&&
			!empty($this->_result_set[$this->_result_set_current])
		){
			$this->buildObject($this->_result_set[$this->_result_set_current]);
			return $this->toArray();
		}else{
			return false;
		}
	}

	/**
	 * untested
	 * @param $array
	 * @return array
	 *
	 * this function will walk through an entire array and "normalize" all of the keys
	 * creating a slug like key with underscores as spaces splitting Camelcase words and
	 * stripping unwanted characters
	 *
	 */
	public function normalizeArrayKeys($array){
		$data = array();
		if(is_array($array) && !empty($array)){
			$Format = new RapidFormat();
			$i = 0;
			foreach($array as $k => $v){
				if(!is_numeric($k)){
					$kv = $Format->Slug(
						$Format->SplitCamelCase($k),"_",array("ignore_symbols"=>1)
					);
				}
				else{$kv = $k;}

				if(empty($kv)){ $kv = $i; $i++; }

				if(!is_array($v) && !empty($v)){ $data[$kv] = $v;}
				else{ $data[$kv] = $this->normalizeArrayKeys($v);}
			}
			ksort($data);
		}
		return $data;
	}

	public function previous(){
		if( empty($this->_result_set) || ($this->_result_set_current < 1)){
			return false;
		}
		$this->_result_set_current = ($this->_result_set_current - 1);
		if($this->_result_set_current > $this->_result_set_count){
			return false;
		}
		if(
			isset($this->_result_set[$this->_result_set_current])
			&&
			!empty($this->_result_set[$this->_result_set_current])
		){
			$this->buildObject($this->_result_set[$this->_result_set_current]);
			return $this->toArray();
		}else{
			return false;
		}
	}

	public function setData($key,$value=false){
		if( (is_array($key) || is_object($key)) && !empty($key) ){
			foreach($key as $k=>$v){
				$this->_set($k,$v);
			}
			return $this->toArray();
		}else{
			return $this->_set($key,$value);
		}
	}


	/**
	 * @return array
	 *
	 * returns the results of $this->obj to an array
	 */
	public function toArray(){
		return (array)json_decode(json_encode($this->get_object()),true);
	}

	/**
	 *
	 */
	public function reset(){
		$this->obj= NULL;
		$this->has_data = false;
		return true;
	}

	public function result_count(){
		if(empty($this->_result_set) || ($this->_result_set_count < 1)){return NULL;}
		return (int)$this->_result_set_count;
	}

	public function resetResultSet(){
		$this->_result_set_count = (int)0;
		$this->_result_set = array();
		$this->resetResultSetPointer(0);
	}

	public function resetResultSetPointer($int = 0){
		$int = (int)$int;
		$this->_result_set_current = $int;
		$this->_result_set_started = NULL;
	}

	/**
	 * set data keys
	 */
	function setInt($k,$v){
		return $this->_set($k,(int)$v);
	}
	function setString($k,$v){
		return $this->_set($k,(string)$v);
	}
	function setSlug($k,$v){
		return $this->_set($k,RapidFormat::Slug($v));
	}
	/**
	 * @param $k
	 * @param $v //only supports us phone numbers
	 */
	function setPhone($k,$v){
		return $this->_set($k,RapidFormat::Phone($v));
	}

	/**
	 * @param $key
	 * @return string // us phone only
	 */
	function getDisplayPhone($key){return RapidFormat::PhoneDisplay($this->_get($key));}

	/**
	 * @return mixed
	 *
	 * @TODO: probably needs more attention
	 */
	public function user_ip(){
		return $_SERVER["REMOTE_ADDR"];
	}

	/**
	 * @param $string
	 * @return bool
	 * Quick and dirty to check if a string is json
	 */
	public function isJSON($string){
		if(empty($string) || is_array($string) || is_object($string)){return false;}
		$array = json_decode($string, true);
		return !empty($string) && is_string($string) && is_array($array) && !empty($array) && json_last_error() == 0;
	}

	public function jsonEncode($data){
		return json_encode($data);
	}

	public function throwError($error,$type = "application_error"){
		if(empty($error)){return false;}
		$this->_last_error = $error;
		$this->_errors[] = $error;
		$DevLog = new Model_DevLog();
		$DevLog->setData(
			array(
				"log_key" => $type,
				"log_value" => $error,
				"extra" => "IP: ".$this->user_ip()
			)
		);
		$DevLog->save();
	}

	public static function singleton(){
		$class = md5(get_called_class()); // late-static-bound class name
		if (!isset(self::$single_instances[$class])) {
			$data  = new static();
			$data->isSingleton(true);
			self::$single_instances[$class] = $data;
		}
		return self::$single_instances[$class];
	}

	public function isSingleton($set=false){
		if($set){
			$this->is_singleton = true;
		}
		return $this->is_singleton;
	}

	/**
	 * clone and wake up have been disabled do to the introduction of singleton
	 *
	 */
	protected function __clone() {
		if($this->isSingleton()){
			$this->throwError("Cannot clone singleton");
		}

	}

	public function __wakeup(){
		if($this->isSingleton()){
			$this->throwError("Cannot clone singleton");
		}
	}
}
?>