<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

abstract class Ddm_Db_Schema_Grammar {
	/**
	 * @return string
	 */
	abstract public function getIdentifierQuote();

	/**
	 * @param string $type
	 * @param array $options
	 * @return string
	 */
	abstract public function getColumnType($type, array $options);

	/**
	 * @param Ddm_Db_Schema_Column $column
	 * @return string
	 */
	abstract protected function _compileAutoIncrement(Ddm_Db_Schema_Column $column);

	/**
	 * @param string $table
	 * @param Ddm_Db_Schema_Table $definition
	 * @param bool $ifNotExists
	 * @return string[]
	 */
	public function compileCreate($table, Ddm_Db_Schema_Table $definition, $ifNotExists){
		$columns = $definition->compileColumns();
		foreach($definition->getForeignKeys() as $foreign){
			$columns[] = $this->compileForeignKey($table, $foreign, $definition->getTablePrefix());
		}
		$columns = array_merge($columns, $this->compileCreateTableIndexes($table, $definition));

		$sql = 'CREATE TABLE'.($ifNotExists ? ' IF NOT EXISTS' : '').' '.$this->wrap($table)
			.' ('.implode(', ', $columns).')'.$this->_compileTableOptions($definition->getOptions());
		return array_merge(array($sql), $this->compileCreateIndexes($table, $definition, $ifNotExists));
	}

	/**
	 * @param string $table
	 * @param bool $ifExists
	 * @return string
	 */
	public function compileDrop($table, $ifExists){
		return 'DROP TABLE'.($ifExists ? ' IF EXISTS' : '').' '.$this->wrap($table);
	}

	/**
	 * @param string $from
	 * @param string $to
	 * @return string
	 */
	public function compileRename($from, $to){
		return 'ALTER TABLE '.$this->wrap($from).' RENAME TO '.$this->wrap($to);
	}

	/**
	 * @param string $table
	 * @return array
	 */
	abstract public function compileHasTable($table);

	/**
	 * @param string $table
	 * @return array
	 */
	abstract public function compileColumnListing($table);

	/**
	 * @return array
	 */
	abstract public function compileTableListing();

	/**
	 * @param string $table
	 * @param Ddm_Db_Schema_Table $definition
	 * @return string[]
	 */
	abstract public function compileAlter($table, Ddm_Db_Schema_Table $definition);

	/**
	 * @param bool $enable
	 * @return string
	 */
	abstract public function compileForeignKeyChecks($enable);

	/**
	 * @param Ddm_Db_Schema_Column $column
	 * @return string
	 */
	public function compileColumn(Ddm_Db_Schema_Column $column){
		$sql = $this->wrap($column->getName()).' '.$this->getColumnDefinitionType($column);
		if($column->isUnsigned()){
			$sql .= $this->_compileUnsigned($column);
		}

		if($column->isAutoIncrement()){
			$sql .= $this->_compileAutoIncrement($column);
		}else if(!$column->isNullable()){
			$sql .= ' NOT NULL';
		}

		if($column->hasDefault()){
			$sql .= ' DEFAULT '.$this->_compileDefault($column->getDefault());
		}
		return $sql;
	}

	/**
	 * @param Ddm_Db_Schema_Column $column
	 * @return string
	 */
	protected function getColumnDefinitionType(Ddm_Db_Schema_Column $column){
		return $this->getColumnType($column->getType(), $column->getOptions());
	}

	/**
	 * @param array $columns
	 * @param bool $hasAutoIncrementPrimary
	 * @return string|null
	 */
	public function compilePrimary(array $columns, $hasAutoIncrementPrimary){
		return 'PRIMARY KEY ('.implode(', ', $this->_wrapIdentifiers($columns)).')';
	}

	/**
	 * @param string $table
	 * @param string $name
	 * @param array $columns
	 * @param bool $unique
	 * @param string $type
	 * @param bool $ifNotExists
	 * @return string
	 */
	public function compileIndex($table, $name, array $columns, $unique, $type = 'index', $ifNotExists = false){
		$indexType = $unique ? 'UNIQUE ' : ($type==='fulltext' ? 'FULLTEXT ' : ($type==='spatial' ? 'SPATIAL ' : ''));
		return 'CREATE '.$indexType.'INDEX'.($ifNotExists ? ' IF NOT EXISTS' : '').' '.$this->wrap($name)
			.' ON '.$this->wrap($table).' ('.implode(', ', $this->_wrapIdentifiers($columns)).')';
	}

	/**
	 * @param string $table
	 * @param Ddm_Db_Schema_Table $definition
	 * @return string[]
	 */
	protected function compileCreateTableIndexes($table, Ddm_Db_Schema_Table $definition){
		return array();
	}

	/**
	 * @param string $table
	 * @param Ddm_Db_Schema_Table $definition
	 * @param bool $ifNotExists
	 * @return string[]
	 */
	protected function compileCreateIndexes($table, Ddm_Db_Schema_Table $definition, $ifNotExists){
		$statements = array();
		foreach($definition->getIndexDefinitions($table) as $index){
			$statements[] = $this->compileIndex(
				$table,
				$index['name'],
				$index['columns'],
				$index['unique'],
				$index['type'],
				$ifNotExists
			);
		}
		return $statements;
	}

	/**
	 * @param string $table
	 * @param Ddm_Db_Schema_Foreign $foreign
	 * @param string $tablePrefix
	 * @return string
	 */
	public function compileForeignKey($table, Ddm_Db_Schema_Foreign $foreign, $tablePrefix){
		$columns = $foreign->getColumns();
		$references = $foreign->getReferences();
		$referencedTable = $foreign->getOnTable();
		if(!$columns || !$references || !$referencedTable || count($columns)!==count($references)){
			throw new InvalidArgumentException('A foreign key requires matching columns, references, and a referenced table.');
		}
		$name = $foreign->getName();
		if($name===NULL)$name = $table.'_'.implode('_', $columns).'_foreign';

		$sql = 'CONSTRAINT '.$this->wrap($name).' FOREIGN KEY ('.implode(', ', $this->_wrapIdentifiers($columns)).')'
			.' REFERENCES '.$this->wrap($tablePrefix.$referencedTable).' ('.implode(', ', $this->_wrapIdentifiers($references)).')';
		if($foreign->getOnDelete()!==NULL)$sql .= ' ON DELETE '.$foreign->getOnDelete();
		if($foreign->getOnUpdate()!==NULL)$sql .= ' ON UPDATE '.$foreign->getOnUpdate();
		return $sql;
	}

	/**
	 * @param string $identifier
	 * @return string
	 */
	public function wrap($identifier){
		$quote = $this->getIdentifierQuote();
		return $quote.str_replace($quote, $quote.$quote, $identifier).$quote;
	}

	/**
	 * @param array $identifiers
	 * @return array
	 */
	protected function _wrapIdentifiers(array $identifiers){
		$result = array();
		foreach($identifiers as $identifier)$result[] = $this->wrap($identifier);
		return $result;
	}

	/**
	 * @param Ddm_Db_Schema_Column $column
	 * @return string
	 */
	protected function _compileUnsigned(Ddm_Db_Schema_Column $column){
		return '';
	}

	/**
	 * @param array $options
	 * @return string
	 */
	protected function _compileTableOptions(array $options){
		if($options){
			throw new InvalidArgumentException('Table engine, charset, and collation options are not supported by this database.');
		}
		return '';
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	protected function _compileDefault($value){
		if($value instanceof Ddm_Db_Expression)return (string)$value;
		if($value===NULL)return 'NULL';
		if(is_bool($value))return $value ? '1' : '0';
		if(is_int($value) || is_float($value))return (string)$value;
		return "'".str_replace("'", "''", (string)$value)."'";
	}
}
