<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Db_Schema_Column {
	/** @var string */
	protected $_name;

	/** @var string */
	protected $_type;

	/** @var array */
	protected $_options;

	/** @var bool */
	protected $_nullable = false;

	/** @var bool */
	protected $_unsigned = false;

	/** @var bool */
	protected $_autoIncrement = false;

	/** @var bool */
	protected $_primary = false;

	/** @var bool */
	protected $_unique = false;

	/** @var bool */
	protected $_index = false;

	/** @var bool */
	protected $_hasDefault = false;

	/** @var mixed */
	protected $_default;

	/** @var bool */
	protected $_change = false;

	/** @var Ddm_Db_Schema_Table|null */
	protected $_table;

	/**
	 * @param string $name
	 * @param string $type
	 * @param array $options
	 * @param Ddm_Db_Schema_Table|null $table
	 * @return void
	 */
	public function __construct($name, $type, array $options = array(), Ddm_Db_Schema_Table $table = NULL){
		$this->_name = $name;
		$this->_type = $type;
		$this->_options = $options;
		$this->_table = $table;
	}

	/**
	 * @param bool $value
	 * @return Ddm_Db_Schema_Column
	 */
	public function nullable($value = true){
		$this->_nullable = (bool)$value;
		return $this;
	}

	/**
	 * @return Ddm_Db_Schema_Column
	 */
	public function unsigned(){
		$this->_unsigned = true;
		return $this;
	}

	/**
	 * @return Ddm_Db_Schema_Column
	 */
	public function autoIncrement(){
		$this->_autoIncrement = true;
		$this->_primary = true;
		return $this;
	}

	/**
	 * @return Ddm_Db_Schema_Column
	 */
	public function primary(){
		$this->_primary = true;
		return $this;
	}

	/**
	 * @return Ddm_Db_Schema_Column
	 */
	public function unique(){
		$this->_unique = true;
		return $this;
	}

	/**
	 * @return Ddm_Db_Schema_Column
	 */
	public function index(){
		$this->_index = true;
		return $this;
	}

	/**
	 * @param string $comment
	 * @return Ddm_Db_Schema_Column
	 */
	public function comment($comment){
		$this->_options['comment'] = $comment;
		return $this;
	}

	/**
	 * @return Ddm_Db_Schema_Column
	 */
	public function first(){
		$this->_options['first'] = true;
		return $this;
	}

	/**
	 * @param string $column
	 * @return Ddm_Db_Schema_Column
	 */
	public function after($column){
		$this->_options['after'] = $column;
		return $this;
	}

	/**
	 * @return Ddm_Db_Schema_Column
	 */
	public function useCurrent(){
		return $this->defaultValue(new Ddm_Db_Expression('CURRENT_TIMESTAMP'));
	}

	/**
	 * @return Ddm_Db_Schema_Column
	 */
	public function useCurrentOnUpdate(){
		$this->_options['useCurrentOnUpdate'] = true;
		return $this;
	}

	/**
	 * @return Ddm_Db_Schema_Column
	 */
	public function change(){
		$this->_change = true;
		return $this;
	}

	/**
	 * @param string|null $table
	 * @param string|null $column
	 * @param string|null $indexName
	 * @return Ddm_Db_Schema_Foreign
	 */
	public function constrained($table = NULL, $column = 'id', $indexName = NULL){
		if(!$this->_table){
			throw new LogicException('The column is not attached to a schema table definition.');
		}
		if($table===NULL){
			$base = preg_replace('/_id$/', '', $this->_name);
			if(preg_match('/[^aeiou]y$/i', $base)){
				$table = substr($base, 0, -1).'ies';
			}else if(preg_match('/(s|x|z|ch|sh)$/i', $base)){
				$table = $base.'es';
			}else{
				$table = $base.'s';
			}
		}
		return $this->_table->foreign($this->_name, $indexName)->references($column)->on($table);
	}

	/**
	 * @return string
	 */
	public function getType(){
		return $this->_type;
	}

	/**
	 * @return array
	 */
	public function getOptions(){
		return $this->_options;
	}

	/**
	 * @return bool
	 */
	public function isChange(){
		return $this->_change;
	}

	/**
	 * @return bool
	 */
	public function isNullable(){
		return $this->_nullable;
	}

	/**
	 * @return bool
	 */
	public function isUnsigned(){
		return $this->_unsigned;
	}

	/**
	 * @return bool
	 */
	public function hasDefault(){
		return $this->_hasDefault;
	}

	/**
	 * @return mixed
	 */
	public function getDefault(){
		return $this->_default;
	}

	/**
	 * @param mixed $value
	 * @return Ddm_Db_Schema_Column
	 */
	public function defaultValue($value){
		$this->_default = $value;
		$this->_hasDefault = true;
		return $this;
	}

	/**
	 * Laravel风格的default别名
	 * @param mixed $value
	 * @return Ddm_Db_Schema_Column
	 */
	public function default($value){
		return $this->defaultValue($value);
	}

	/**
	 * @return string
	 */
	public function getName(){
		return $this->_name;
	}

	/**
	 * @return bool
	 */
	public function isPrimary(){
		return $this->_primary;
	}

	/**
	 * @return bool
	 */
	public function isUnique(){
		return $this->_unique;
	}

	/**
	 * @return bool
	 */
	public function hasIndex(){
		return $this->_index;
	}

	/**
	 * @return bool
	 */
	public function isAutoIncrement(){
		return $this->_autoIncrement;
	}
}
