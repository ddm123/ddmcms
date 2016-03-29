<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Object{
	protected $_data = array();
	protected $_origData = array();
	protected $_readOnlyAttribute = array();

	public function __construct(){
		if(($args = func_get_args()) && is_array($args[0])){
			if(isset($args[1]) && $args[1]===false){
				$this->addData($args[0]);
			}else{
				$this->parseData($args[0], isset($args[1])&&is_object($args[1])?$args[1]:$this);
			}
		}
	}

	public function __get($name){
		if(isset($this->$name) || property_exists($this,$name)){
			throw new Exception('Cannot access protected/private property '.get_class($this).'::$'.$name);
			return NULL;
		}
		return isset($this->_data[$name]) ? $this->_data[$name] : NULL;
	}

	public function __set($name, $value){
		if(isset($this->$name) || property_exists($this,$name)){
			throw new Exception('Cannot access protected/private property '.get_class($this).'::$'.$name);
		}else{
			if(isset($this->_readOnlyAttribute[$name])){
				throw new Exception("The \"$name\" attribute is read-only");
			}else{
				$this->$name = $value;
				$this->_data[$name] = $value;
			}
		}
    }

	/**
	 * 设置哪些属性是只读，所以你必须先给这些属性赋值后才调用该方法来指定为只读
	 * @param string|array $property
	 * @param bool $unset 是否取消只读状态
	 * @return Ddm_Object
	 */
	public function setReadOnlyProperty($property,$unset = false){
		if($unset){
			if(is_array($property))foreach($property as $value)unset($this->_readOnlyAttribute[$value]);
			else unset($this->_readOnlyAttribute[$property]);
		}else{
			if(is_array($property)){
				foreach($property as $value){
					$this->_readOnlyAttribute[$value] = true;
					unset($this->$value);
				}
			}else{
				$this->_readOnlyAttribute[$property] = true;
				unset($this->$property);
			}
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function getReadOnlyProperty(){
		return array_keys($this->_readOnlyAttribute,true);
	}

	/**
	 * 如果$name为一个数组,则递归设置每个数组元素都成为一个对象
	 * @param string|array $name
	 * @param mixed $value
	 * @return Ddm_Object
	 */
	public function setData($name,$value = NULL){
		if($name){
			if(is_array($name))$this->parseData($name);
			else{
				$this->_data[$name] = $value;
				$this->$name = $value;
			}
		}
    	return $this;
    }

	/**
	 * 该方法和setData()方法的区别仅仅是不递归设置每个数组元素都成为一个对象,其它都是一样的
	 * @param string|array $name
	 * @param mixed $value
	 * @return Ddm_Object
	 */
	public function addData($name,$value = NULL){
		if($name){
			if(is_array($name)){
				foreach($name as $key=>$val)$this->setData($key,$val);
			}else{
				$this->setData($name,$value);
			}
		}
		return $this;
	}

	/**
	 * @param string|null $name
	 * @param mixed $value
	 * @return Ddm_Object
	 */
	public function unsetData($name = NULL){
		if($name===NULL){
			foreach($this->_data as $key=>$value)$this->unsetData($key);
		}else{
			if(isset($this->_readOnlyAttribute[$name])){
				throw new Exception("The \"$name\" attribute is read-only");
			}else{
				unset($this->$name,$this->_data[$name]);
			}
		}
		return $this;
    }

	/**
	 * @param string $name
	 * @return bool
	 */
	public function issetData($name){
		return isset($this->_data[$name]) || array_key_exists($name,$this->_data);
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getData($name = NULL){
		return $name===NULL ? $this->toArray() : $this->$name;
	}

	/**
     * @param string $name
     * @return mixed
     */
    public function getOrigData($name = NULL){
        if($name===NULL)return $this->_origData;
        return isset($this->_origData[$name]) ? $this->_origData[$name] : NULL;
    }

	/**
     * @param string|null $name
     * @param mixed $value
	 * @param bool $cover 如果已经被赋值过了是否重新赋值
     * @return Varien_Object
     */
    public function setOrigData($name = NULL,$value = NULL,$cover = false){
        if($name===NULL || is_array($name)){
			$name===NULL and $name = $this->toArray();
            if($cover)$this->_origData = $name;
			else{
				foreach($name as $key=>$val){
					if(!isset($this->_origData[$key]))$this->setOrigData($key,$val,$cover);
				}
			}
        }
		else if($value===NULL)unset($this->_origData[$name]);
		else if($cover || !isset($this->_origData[$name]))$this->_origData[$name] = $value;
        return $this;
    }

	/**
	 * @return array
	 */
	public function toArray(){
		foreach($this->_data as $name=>$value){
			$value===$this->$name or $this->_data[$name] = $this->$name;
		}
		return $this->_data;
	}

	/**
	 * @return string
	 */
	public function __toString(){
		return print_r($this->toArray(), true);
	}

	/**
	 * 递归把所有值都转为$object对象
	 * @param array $data
	 * @param Ddm_Object $object
	 * @return Ddm_Object
	 * @throws Exception
	 */
	final public function parseData(array $data,$object = NULL){
		$result = $object===NULL ? $this : $object;
		if($data){
			$result->_data = array_merge($result->_data,$data);
			foreach($data as $key=>$value){
				if(isset($this->_readOnlyAttribute[$key])){
					throw new Exception("The \"$key\" attribute is read-only");
				}else{
					if(preg_match('/^[A-Za-z_]\w*$/',$key)){
						$result->$key = is_array($value) ? $this->parseData($value,new Ddm_Object()) : $value;
					}else{
						$result = array();//重置为一个数组,因为键名不能作为一个合法的属性名称
						foreach($data as $_key=>$_value){
							$result[$_key] = is_array($_value) ? $this->parseData($_value,new Ddm_Object()) : $_value;
						}
						break;//上面已经循环了，必须跳出来
					}
				}
			}
		}
		return $result;
    }
}
