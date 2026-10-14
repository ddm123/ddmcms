<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Cache_Redis implements Ddm_Cache_Interface {
	protected $_options = array(
		'host'=>'127.0.0.1',
		'port'=>6379,
		'timeout'=>1,
		'database'=>0,
		'password'=>'',
		'key_prefix'=>'ddmcms:cache:'
	);

	/** @var resource */
	private $_connection = NULL;

	public function __construct(array $options){
		$this->_options = array_merge($this->_options, $options);
		if($this->_options['key_prefix']===NULL)$this->_options['key_prefix'] = '';
	}

	public function __destruct(){
		if(is_resource($this->_connection))fclose($this->_connection);
	}

	/**
	 * @param string $id
	 * @param string $data
	 * @param array $tags
	 * @param int $lifetime
	 * @return bool
	 */
	public function save($id, $data, array $tags = NULL, $lifetime = 0){
		$key = $this->_dataKey($id);
		if($lifetime){
			$result = $this->_command(array('SETEX', $key, (int)$lifetime+180, $data));
		}else{
			$result = $this->_command(array('SET', $key, $data));
		}
		if($result!=='OK')return false;

		if($tags){
			$this->_command(array_merge(array('SADD', $this->_itemTagsKey($id)), array_values($tags)));
			$this->_command(array_merge(array('SADD', $this->_tagNamesKey()), array_values($tags)));
			foreach($tags as $tag){
				$this->_command(array('SADD', $this->_tagKey($tag), $id));
			}
		}
		return true;
	}

	/**
	 * @param string $id
	 * @return string|false
	 */
	public function load($id){
		$result = $this->_command(array('GET', $this->_dataKey($id)));
		return $result===NULL ? false : $result;
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	public function remove($id){
		$tagKeys = $this->_command(array('SMEMBERS', $this->_itemTagsKey($id)));
		foreach($tagKeys as $tag){
			$this->_command(array('SREM', $this->_tagKey($tag), $id));
			if(!$this->_command(array('SCARD', $this->_tagKey($tag)))){
				$this->_command(array('SREM', $this->_tagNamesKey(), $tag));
			}
		}
		$this->_command(array('DEL', $this->_dataKey($id), $this->_itemTagsKey($id)));
		return true;
	}

	/**
	 * @param array $tags
	 * @return bool
	 */
	public function removeByTags(array $tags){
		$ids = $this->getIdByTags($tags);
		foreach($ids as $id){
			$this->remove($id);
		}
		foreach($tags as $tag){
			$this->_command(array('DEL', $this->_tagKey($tag)));
			$this->_command(array('SREM', $this->_tagNamesKey(), $tag));
		}
		return true;
	}

	/**
	 * @return bool
	 */
	public function clear(){
		$prefix = $this->_options['key_prefix'];
		if($prefix===''){
			$patterns = array('data:*', 'item-tags:*', 'tag:*', 'tag-names');
		}else{
			$patterns = array(str_replace(
				array('\\', '*', '?', '[', ']'),
				array('\\\\', '\\*', '\\?', '\\[', '\\]'),
				$prefix
			).'*');
		}

		foreach($patterns as $pattern){
			$cursor = '0';
			do{
				$result = $this->_command(array('SCAN', $cursor, 'MATCH', $pattern, 'COUNT', 500));
				$cursor = (string)$result[0];
				if($result[1]){
					$this->_command(array_merge(array('DEL'), $result[1]));
				}
			}while($cursor!=='0');
		}
		return true;
	}

	/**
	 * @param array $tags
	 * @return array
	 */
	public function getIdByTags(array $tags){
		$ids = array();
		foreach($tags as $tag){
			$tagKey = $this->_tagKey($tag);
			$tagIds = $this->_command(array('SMEMBERS', $tagKey));
			foreach($tagIds as $id){
				if($this->_command(array('EXISTS', $this->_dataKey($id)))){
					$ids[$id] = $id;
				}else{
					$this->_command(array('SREM', $tagKey, $id));
					$this->_command(array('SREM', $this->_itemTagsKey($id), $tag));
					if(!$this->_command(array('SCARD', $this->_itemTagsKey($id)))){
						$this->_command(array('DEL', $this->_itemTagsKey($id)));
					}
				}
			}
			if(!$this->_command(array('SCARD', $tagKey))){
				$this->_command(array('SREM', $this->_tagNamesKey(), $tag));
			}
		}
		return $ids;
	}

	/**
	 * @return array
	 */
	public function getAllTags(){
		$tags = $this->_command(array('SMEMBERS', $this->_tagNamesKey()));
		$result = array();
		foreach($tags as $tag){
			if($this->_command(array('SCARD', $this->_tagKey($tag)))){
				$result[] = $tag;
			}else{
				$this->_command(array('SREM', $this->_tagNamesKey(), $tag));
			}
		}
		return $result;
	}

	/**
	 * @param string $id
	 * @return string
	 */
	protected function _dataKey($id){
		return $this->_options['key_prefix'].'data:'.$id;
	}

	/**
	 * @param string $id
	 * @return string
	 */
	protected function _itemTagsKey($id){
		return $this->_options['key_prefix'].'item-tags:'.$id;
	}

	/**
	 * @param string $tag
	 * @return string
	 */
	protected function _tagKey($tag){
		return $this->_options['key_prefix'].'tag:'.sha1($tag);
	}

	/**
	 * @return string
	 */
	protected function _tagNamesKey(){
		return $this->_options['key_prefix'].'tag-names';
	}

	/**
	 * @param array $arguments
	 * @return mixed
	 */
	protected function _command(array $arguments){
		$this->_connect();
		$this->_writeCommand($arguments);
		return $this->_readResponse();
	}

	/**
	 * @return Ddm_Cache_Redis
	 */
	protected function _connect(){
		if(is_resource($this->_connection))return $this;

		$errno = 0;
		$errstr = '';
		$address = 'tcp://'.$this->_options['host'].':'.(int)$this->_options['port'];
		$this->_connection = @stream_socket_client(
			$address,
			$errno,
			$errstr,
			$this->_options['timeout'],
			STREAM_CLIENT_CONNECT
		);
		if(!$this->_connection){
			$this->_connection = NULL;
			throw new Exception('Unable to connect to Redis at '.$address.': '.$errstr, $errno);
		}

		$timeout = (float)$this->_options['timeout'];
		$seconds = (int)$timeout;
		$microseconds = (int)(($timeout-$seconds)*1000000);
		stream_set_timeout($this->_connection, $seconds, $microseconds);

		try{
			if($this->_options['password']!==''){
				$this->_writeCommand(array('AUTH', $this->_options['password']));
				if($this->_readResponse()!=='OK')throw new Exception('Redis AUTH failed');
			}
			if((int)$this->_options['database']!==0){
				$this->_writeCommand(array('SELECT', (int)$this->_options['database']));
				if($this->_readResponse()!=='OK')throw new Exception('Redis SELECT failed');
			}
		}catch(Exception $e){
			fclose($this->_connection);
			$this->_connection = NULL;
			throw $e;
		}
		return $this;
	}

	/**
	 * @param array $arguments
	 * @return Ddm_Cache_Redis
	 */
	protected function _writeCommand(array $arguments){
		$command = '*'.count($arguments)."\r\n";
		foreach($arguments as $argument){
			$argument = (string)$argument;
			$command .= '$'.strlen($argument)."\r\n".$argument."\r\n";
		}

		$length = strlen($command);
		$written = 0;
		while($written<$length){
			$result = @fwrite($this->_connection, substr($command, $written));
			if($result===false || $result===0){
				$this->_connectionError('Unable to write command to Redis');
			}
			$written += $result;
		}
		return $this;
	}

	/**
	 * @return mixed
	 */
	protected function _readResponse(){
		$line = $this->_readLine();
		$type = substr($line, 0, 1);
		$value = substr($line, 1);
		switch($type){
			case '+':
				return $value;
			case '-':
				throw new Exception('Redis error: '.$value);
			case ':':
				return (int)$value;
			case '$':
				$length = (int)$value;
				if($length<0)return NULL;
				$result = $this->_readBytes($length+2);
				if(substr($result, -2)!=="\r\n")$this->_connectionError('Invalid bulk response from Redis');
				return substr($result, 0, $length);
			case '*':
				$count = (int)$value;
				if($count<0)return NULL;
				$result = array();
				for($i=0; $i<$count; $i++){
					$result[] = $this->_readResponse();
				}
				return $result;
		}
		$this->_connectionError('Invalid response from Redis');
	}

	/**
	 * @return string
	 */
	protected function _readLine(){
		$line = @fgets($this->_connection);
		if($line===false)$this->_connectionError('Unable to read response from Redis');
		$this->_checkTimeout();
		if(substr($line, -2)!=="\r\n")$this->_connectionError('Invalid line from Redis');
		return substr($line, 0, -2);
	}

	/**
	 * @param int $length
	 * @return string
	 */
	protected function _readBytes($length){
		$result = '';
		while(strlen($result)<$length){
			$chunk = @fread($this->_connection, $length-strlen($result));
			if($chunk===false || $chunk==='')$this->_connectionError('Unable to read response from Redis');
			$result .= $chunk;
			$this->_checkTimeout();
		}
		return $result;
	}

	/**
	 * @return Ddm_Cache_Redis
	 */
	protected function _checkTimeout(){
		$metadata = stream_get_meta_data($this->_connection);
		if(!empty($metadata['timed_out']))$this->_connectionError('Timed out waiting for Redis');
		return $this;
	}

	/**
	 * @param string $message
	 */
	protected function _connectionError($message){
		if(is_resource($this->_connection))fclose($this->_connection);
		$this->_connection = NULL;
		throw new Exception($message);
	}
}
