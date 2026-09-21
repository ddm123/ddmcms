<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

/**
 * 表连接条件
 */
class Ddm_Db_JoinClause {
    /** @var string */
	protected $_type = 'inner';

    /** @var string */
	protected $_table = '';

    /** @var string|null */
	protected $_tableAlias = NULL;

    /** @var array<int, array> */
	protected $_conditions = array();

	/**
	 * @param string $table 已带表前缀的表名
	 * @param string $type
	 * @param string|null $alias 表别名
	 */
	public function __construct($table,$type = 'inner',$alias = NULL){
		$this->_table = $table;
		$this->_type = strtoupper($type);
		$this->_tableAlias = $alias;

        if(!in_array($this->_type, array('INNER','LEFT','RIGHT','OUTER','CROSS','NATURAL'), true)){
            throw new InvalidArgumentException('Invalid join type: '.$type);
        }
	}

	/**
	 * @param string|Closure $first
	 * @param string|null $operator
	 * @param string|null $second
	 * @param string $boolean
	 * @return Ddm_Db_JoinClause
	 */
	public function on($first,$operator = NULL,$second = NULL,$boolean = 'and'){
		if($first instanceof Closure)return $this->_nested($first,$boolean);
		if(func_num_args()===2){ $second = $operator; $operator = '='; }
		return $this->_add('on',$first,$operator,$second,$boolean);
	}

	/**
	 * @param string|Closure $first
	 * @param string|null $operator
	 * @param string|null $second
	 * @return Ddm_Db_JoinClause
	 */
	public function orOn($first,$operator = NULL,$second = NULL){
		if($first instanceof Closure)return $this->_nested($first,'or');
		if(func_num_args()===2){ $second = $operator; $operator = '='; }
		return $this->_add('on',$first,$operator,$second,'or');
	}

	/**
	 * @param string|Closure $column
	 * @param string|null $operator
	 * @param mixed $value
	 * @param string $boolean
	 * @return Ddm_Db_JoinClause
	 */
	public function where($column,$operator = NULL,$value = NULL,$boolean = 'and'){
		if($column instanceof Closure)return $this->_nested($column,$boolean);
		if(func_num_args()===2){ $value = $operator; $operator = '='; }
		return $this->_add('where',$column,$operator,$value,$boolean);
	}

	/**
	 * @param string|Closure $column
	 * @param string|null $operator
	 * @param mixed $value
	 * @return Ddm_Db_JoinClause
	 */
	public function orWhere($column,$operator = NULL,$value = NULL){
		if($column instanceof Closure)return $this->_nested($column,'or');
		if(func_num_args()===2){ $value = $operator; $operator = '='; }
		return $this->_add('where',$column,$operator,$value,'or');
	}

	/**
	 * @return string
	 */
	public function getType(){
		return $this->_type;
	}

	/**
	 * @return string
	 */
	public function getTable(){
		return $this->_table;
	}

	/**
	 * @return string|null
	 */
	public function getTableAlias(){
		return $this->_tableAlias;
	}

	/**
	 * @return array<int, array>
	 */
	public function getConditions(){
		return $this->_conditions;
	}

    /**
	 * @param string $type
	 * @param string|null $first
	 * @param string|null $operator
	 * @param string|null $second
	 * @param string $boolean
	 * @return Ddm_Db_JoinClause
	 */
	protected function _add($type,$first,$operator,$second,$boolean){
		$this->_conditions[] = array('type'=>$type,'first'=>$first,'operator'=>$operator,'second'=>$second,'boolean'=>$boolean);
		return $this;
	}

    /**
	 * @param Closure $callback
	 * @param string $boolean
	 * @return Ddm_Db_JoinClause
	 */
	protected function _nested($callback,$boolean){
		$join = new self($this->_table,$this->_type,$this->_tableAlias);
		$callback($join);
		$this->_conditions[] = array('type'=>'nested','join'=>$join,'boolean'=>$boolean);
		return $this;
	}

}
