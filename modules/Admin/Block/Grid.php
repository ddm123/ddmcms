<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Admin_Block_Grid extends Core_Block_Abstract {
	private $_select = NULL;//Ddm_Db_Select
	private $_startRow = 0;

	protected $_columns = array();
	protected $_gridUrl = NULL;
	protected $_gridSearchUrl = NULL;
	protected $_resetGridSearchUrl = NULL;
	protected $_searchColumns = array();
	protected $_listData = NULL;
	protected $_filterData = false;
	protected $_filterWhere = array();
	protected $_sortData = false;
	protected $_disableRowCallback = NULL;
	protected $_buttons = array();
	protected $_actions = array();
	protected $_listBlock = NULL;
	protected $_js = '';
	protected $_isShowTotalRecords = true;

	public $titleText = NULL;
	public $primaryKey = NULL;//表主键的字段名
	public $totalRows = NULL;
	public $defaultLimit = 20;
	public $defaultPage = 1;
	public $defaultSort = false;//是$this->_columns数据的键名,而不是表的字段名
	public $defaultDir = 'desc';
	public $defaultFilter = array('column_id'=>false,'keyword'=>false);//设定默认一开始就搜索的列
	public $idName = 'ids';
	public $emptyText = '';
	public $getFieldValueUrl = '';
	public $saveFieldValueUrl = '';

	/**
	 * @return Admin_Block_Grid
	 */
	public function init() {
		parent::init();
		$this->titleText = Ddm::getTranslate('core')->translate('无标题');
		$this->emptyText = Ddm::getTranslate('core')->translate('没有任何记录');
		$this->template = 'grid.phtml';
		$this->addButton('add',array('label'=>Ddm::getTranslate('admin')->translate('增加'),'href'=>Ddm::getLanguage()->getUrl('*/*/add'),'icon'=>'icon-plus'));
		return $this;
	}

	/**
	 * @param Admin_Block_List_Abstract $block
	 * @return Admin_Block_Grid
	 */
	public function setListBlock(Admin_Block_List_Abstract $block){
		$this->_listBlock = $block;
		return $this;
	}

	/**
	 * @return Admin_Block_List_Abstract
	 */
	public function getListBlock(){
		return $this->_listBlock;
	}

	/**
	 * @param string $columnId
	 * @param array $columnOption
	 * @param int $index
	 * @return Admin_Block_Grid
	 */
	public function addColumn($columnId, array $columnOption, $index = NULL){
		isset($columnOption['type']) or $columnOption['type'] = 'text';
		if(is_int($index)){
			if($index==0)$this->_columns = array($columnId=>$columnOption) + $this->_columns;
			else{
				$a = array_slice($this->_columns,0,$index,true);
				$b = array_slice($this->_columns,$index,NULL,true);
				$this->_columns = array_merge($a,array($columnId=>$columnOption),$b);
			}
		}else{
			$this->_columns[$columnId] = $columnOption;
		}
		if(!empty($columnOption['search']))$this->_searchColumns[$columnId] = $columnOption;
		return $this;
	}

	/**
	 * @param string $columnId
	 * @return array|null
	 */
	public function getColumn($columnId = NULL){
		if($columnId===NULL)return $this->_columns;
		return isset($this->_columns[$columnId]) ? $this->_columns[$columnId] : NULL;
	}

	/**
	 * @param string $id
	 * @param array $property
	 * @return Admin_Block_Grid
	 */
	public function addButton($id,array $property){
		$this->_buttons[$id] = $property;
		return $this;
	}

	/**
	 * @param string $id
	 * @param array $property
	 * @return Admin_Block_Grid
	 */
	public function updateButton($id,array $property){
		if(isset($this->_buttons[$id])){
			foreach($property as $key=>$value){
				if($value===NULL)unset($this->_buttons[$id][$key]);
				else $this->_buttons[$id][$key] = $value;
			}
		}
		return $this;
	}

	/**
	 * @param string $id
	 * @return Admin_Block_Grid
	 */
	public function removeButton($id = NULL){
		if($id===NULL)$this->_buttons = array();
		else if(isset($this->_buttons[$id]))unset($this->_buttons[$id]);
		return $this;
	}

	/**
	 * @param string $id
	 * @return array
	 */
	public function getButton($id = NULL){
		if($id===NULL)return $this->_buttons;
		return isset($this->_buttons[$id]) ? $this->_buttons[$id] : NULL;
	}

	/**
	 * @param string $id
	 * @return string
	 */
	public function getButtonHtml($id){
		$html = '';
		if(isset($this->_buttons[$id])){
			$html = '<a href="'.(isset($this->_buttons[$id]['href']) ? $this->_buttons[$id]['href'] : '#').'" class="btn';
			isset($this->_buttons[$id]['class']) and $html .= ' '.$this->_buttons[$id]['class'];
			$html .= '"';
			isset($this->_buttons[$id]['onclick']) and $html .= ' onclick="'.$this->_buttons[$id]['onclick'].'"';
			isset($this->_buttons[$id]['style']) and $html .= ' style="'.$this->_buttons[$id]['style'].'"';
			$html .= '>';
			isset($this->_buttons[$id]['icon']) and $html .= '<i class="icon '.$this->_buttons[$id]['icon'].'"></i> ';
			$html .= $this->_buttons[$id]['label'].'</a>';
		}
		return $html;
	}

	/**
	 * @param string $columnId
	 * @return array|null
	 */
	public function getSearchColumns($columnId = NULL){
		if($columnId===NULL)return $this->_searchColumns;
		return isset($this->_searchColumns[$columnId]) ? $this->_searchColumns[$columnId] : NULL;
	}

	/**
	 *
	 * @param callable $callback
	 * @return Admin_Block_Grid
	 */
	public function setDisableRowCallback($callback){
		$this->_disableRowCallback = $callback;
		return $this;
	}

	/**
	 * 你可以对选中的记录作出哪些操作
	 * @param array $action 格式: array('url'=>Post url,'label'=>Label[,'confirm'=>'string'])
	 * @return Admin_Block_Grid
	 */
	public function addAction($key,array $action){
		$this->_actions[$key] = $action;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getActions($key = NULL){
		return $key===NULL ? (($this->primaryKey && $this->idName) ? $this->_actions : array()) : (isset($this->_actions[$key]) ? $this->_actions[$key] : NULL);
	}

	/**
	 * @param string $key
	 * @return Admin_Block_Grid
	 */
	public function removeAction($key){
		if(isset($this->_actions[$key]))unset($this->_actions[$key]);
		return $this;
	}

	/**
	 * @param string $javascrip
	 * @return Admin_Block_Grid
	 */
	public function addJs($javascrip){
		$this->_js .= "\r\n".$javascrip;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getJs(){
		return $this->_js;
	}

	/**
	 * @param bool $flag
	 * @return Admin_Block_Grid
	 */
	public function setIsShowTotalRecords($flag){
		$this->_isShowTotalRecords = (bool)$flag;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getIsShowTotalRecords(){
		return $this->_isShowTotalRecords;
	}

	/**
	 * @param array $row
	 * @return boolean
	 */
	public function isDisableRow(array $row){
		if($this->_disableRowCallback){
			return call_user_func($this->_disableRowCallback,$row);
		}
		return false;
	}

	/**
	 * @param string $url
	 * @return Admin_Block_Grid
	 */
	public function setGridUrl($url){
		$this->_gridUrl = $url;
		return $this;
	}

	/**
	 * @param string $url
	 * @return Admin_Block_Grid
	 */
	public function setGridSearchUrl($url){
		$this->_gridSearchUrl = $url;
		return $this;
	}

	/**
	 * @param string $url
	 * @return Admin_Block_Grid
	 */
	public function setResetGridSearchUrl($url){
		$this->_resetGridSearchUrl = $url;
		return $this;
	}

	/**
	 * @param Ddm_Db_Select $select
	 * @return Admin_Block_Grid
	 */
	public function setSelect(Ddm_Db_Select $select){
		$this->_select = $select;
		return $this;
	}

	/**
	 * @param string $columnId
	 * @param string $keyword
	 * @return Admin_Block_Grid
	 */
	public function setDefaultFilter($columnId,$keyword){
		$this->defaultFilter['column_id'] = $columnId;
		$this->defaultFilter['keyword'] = $keyword;
		return $this;
	}

	/**
	 * @return Ddm_Db_Select
	 */
	public function getSelect(){
		return $this->_select;
	}

	/**
	 * @return string
	 */
	public function getGridUrl(){
		return $this->_gridUrl===NULL ? ($this->_gridUrl = Ddm::getLanguage()->getUrl('*/*/*',array('_current'=>true))) : $this->_gridUrl;
	}

	/**
	 * @return string
	 */
	public function getGridSearchUrl(){
		return $this->_gridSearchUrl===NULL ? ($this->_gridSearchUrl = Ddm::getLanguage()->getUrl('*/*/*')) : $this->_gridSearchUrl;
	}

	/**
	 * @return string
	 */
	public function getResetGridSearchUrl(){
		return $this->_resetGridSearchUrl===NULL ? ($this->_resetGridSearchUrl = Ddm::getLanguage()->getUrl('*/*/*')) : $this->_resetGridSearchUrl;
	}

	public function getTotalRows(){
		if($this->totalRows===NULL){
			if($this->_select){
				$this->_parseFilter()->_parseSort();
				$sel = clone $this->_select;
				$sel->resetColumns()->reset(Ddm_Db_Select::DISTINCT)->reset(Ddm_Db_Select::ORDER)->reset(Ddm_Db_Select::LIMIT)
					->columns(array('t'=>new Ddm_Db_Expression('COUNT(*)')));
				$this->totalRows = Ddm_Db::getReadConn()->fetchOne($sel->__toString(),true);
			}
		}
		return $this->totalRows;
	}

	/**
	 * @return array
	 */
	public function getListData(){
		if($this->_listData===NULL){
			if($this->_select){
				Ddm::dispatchEvent('grid_fetch_select_before',array('grid'=>$this,'select'=>$this->_select));
				if(($this->totalRows = (int)$this->getTotalRows())){
					$this->_parseFilter()->_parseSort();

					if($this->defaultLimit = (int)$this->defaultLimit){
						$pageLink = $this->createBlock('core','pagelink');/* @var $pageLink Core_Block_Pagelink */
						$this->defaultPage = (int)$this->defaultPage;
						if($this->defaultPage>1){
							$p = (int)Ddm_Request::get($pageLink->pageVarName);
							if(!$p)Ddm_Request::singleton()->setParam($pageLink->pageVarName,$this->defaultPage);
						}

						list($this->_startRow,$totalPage) = $pageLink->parseVars($this->defaultLimit,$this->getTotalRows());
						$this->_select->limit($this->_startRow,$this->defaultLimit);
						$pageLink->setTotalPage($totalPage);
						if($this->getIsShowTotalRecords()){
							$pageLink->beforeHtml = Ddm::getTranslate('core')->___('共有%s条记录',' <strong>'.$this->getTotalRows().' </strong>');
						}
						$this->addBlock($pageLink,'pagelink');
					}

					$this->_listData = Ddm_Db::getReadConn()->fetchAll($this->_select->__toString(),$this->primaryKey);
				}else{
					$this->_listData = array();
				}
			}else{
				throw new Exception('\'$select\' Property unassigned.');
			}
		}
		return $this->_listData;
	}

	/**
	 * @param array $listData
	 * @return Admin_Block_Grid
	 */
	public function setListData(array $listData){
		$this->_listData = $listData;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getStartNo(){
		return $this->_startRow+1;
	}

	/**
	 * @return array
	 */
	public function getSortData(){
		$this->_sortData or $this->_parseSort();
		return $this->_sortData;
	}

	/**
	 * @return array
	 */
	public function getFilterData(){
		$this->_filterData or $this->_parseFilter();
		return $this->_filterData;
	}

	/**
	 * @return array
	 */
	public function getFilterWhere(){
		return $this->_parseFilter()->_filterWhere;
	}

	public function numberType(array $row,array $column){
		$value = empty($column['value_callback']) ? $row[$column['field_name']]+0 : call_user_func($column['value_callback'],$row,$column);
		$html = '<td class="align-right"'.$this->_getEditHtml($row,$column).'>'.$value.'</td>';
		return $html;
	}

	public function textType(array $row,array $column){
		$value = empty($column['value_callback']) ? Ddm_String::singleton()->escapeHtml($row[$column['field_name']]) : call_user_func($column['value_callback'],$row,$column);
		$html = '<td'.$this->_getEditHtml($row,$column).'>'.$value.'</td>';
		return $html;
	}

	public function optionsType(array $row,array $column){
		if(empty($column['value_callback'])){
			$value = '&nbsp;';
			if(isset($column['options'])){
				$value = isset($column['options'][$row[$column['field_name']]]) ? Ddm_String::singleton()->escapeHtml($column['options'][$row[$column['field_name']]]) : '&nbsp;';
			}
		}else{
			$value = call_user_func($column['value_callback'],$row,$column);
		}
		$html = '<td'.$this->_getEditHtml($row,$column).'>'.$value.'</td>';
		return $html;
	}

	public function actionType(array $row,array $column){
		$html = '<td>';
		foreach($column['actions'] as $action){
			if(preg_match_all('/\{(\w+)\}/',$action['url'],$matches,PREG_SET_ORDER)){
				foreach($matches as $matche)
					if(isset($row[$matche[1]]))$action['url'] = str_replace($matche[0],$row[$matche[1]],$action['url']);
			}
			$html .= '<a href="'.$action['url'].'"';
			if(!empty($action['target']))$html .= ' target="'.$action['target'].'"';
			if(!empty($action['onclick']))$html .= ' onclick="'.$action['onclick'].'"';
			$html .= '>'.$action['label'].'</a> ';
		}
		$html .= '</td>';
		return $html;
	}

	public function datetimeType(array $row,array $column){
		$value = empty($column['value_callback']) ? Ddm::getHelper('core')->formatDateTime($row[$column['field_name']]) : call_user_func($column['value_callback'],$row,$column);
		$html = '<td'.$this->_getEditHtml($row,$column).'>'.$value.'</td>';
		return $html;
	}

	public function dateType(array $row,array $column){
		$value = empty($column['value_callback']) ? Ddm::getHelper('core')->formatDate($row[$column['field_name']]) : call_user_func($column['value_callback'],$row,$column);
		$html = '<td'.$this->_getEditHtml($row,$column).'>'.$value.'</td>';
		return $html;
	}

	public function currencyType(array $row,array $column){
		$value = empty($column['value_callback']) ? Ddm_String::singleton()->currency($row[$column['field_name']]) : call_user_func($column['value_callback'],$row,$column);
		$html = '<td class="align-right"'.$this->_getEditHtml($row,$column).'>'.$value.'</td>';
		return $html;
	}

	public function boolType(array $row,array $column){
		$value = empty($column['value_callback']) ? '<i class="icon icon-'.($row[$column['field_name']] ? 'ok' : 'remove').'"></i>' : call_user_func($column['value_callback'],$row,$column);
		$html = '<td class="align-center"'.$this->_getEditHtml($row,$column).'>'.$value.'</td>';
		return $html;
	}

	/**
	 * @param array $columnOption
	 * @param mixed $value
	 * @return mixed
	 */
	public function getCondition(array $columnOption,$value){
		if($value instanceof Ddm_Db_Expression || is_array($value))return $value;
		return $columnOption['type']=='currency' || $columnOption['type']=='number' || $columnOption['type']=='bool'
			? $value + 0
			: array('like'=>new Ddm_Db_Expression(Ddm_Db::getReadConn()->quote('%'.strtr($value,array('%'=>'\\%','_'=>'\\_')).'%')));
	}

	/**
	 * @param array $row
	 * @param array $column
	 * @return string
	 */
	protected function _getEditHtml(array $row,array $column){
		$html = '';
		if($this->primaryKey && isset($row[$this->primaryKey]) && !empty($column['edit'])){
			$html = ' modify="'.$column['field_name'].'|'.$column['type'].'|'.$row[$this->primaryKey].'" title="'.Ddm::getTranslate('admin')->translate('双击可修改').'"';
		}
		return $html;
	}

	/**
	 * @return Admin_Block_Grid
	 */
	protected function _parseFilter(){
		if(!$this->_filterData){
			$columnId = Ddm_Request::get('column',false,'') or $columnId = Ddm_Request::post('column',false,'');
			($keyword = Ddm_Request::get('keyword',false,''))==='' and $keyword = Ddm_Request::post('keyword',false,'');
			if(($columnId=='' || $keyword==='') && $this->defaultFilter['column_id'] && $this->defaultFilter['keyword']){
				$columnId = $this->defaultFilter['column_id'];
				$keyword = $this->defaultFilter['keyword'];
			}
			if($columnId!='' && $keyword!=='' && ($column = $this->getColumn($columnId))){
				$this->_applyFilter($column,$keyword);
			}
			$this->_filterData = array('column'=>$columnId,'keyword'=>$keyword);
		}
		return $this;
	}

	/**
	 * @return Admin_Block_Grid
	 */
	protected function _parseSort(){
		if(!$this->_sortData){
			$columnId = Ddm_Request::get('col',false,'') or $columnId = Ddm_Request::post('col',false,'');
			$dir = Ddm_Request::get('dir',false,'') or $dir = Ddm_Request::post('dir',false,'');
			$columnId=='' and $columnId = $this->defaultSort;
			$dir=='' and $dir = $this->defaultDir;
			if($columnId && ($column = $this->getColumn($columnId))){
				$_dir = strtoupper($dir);
				$_dir=='DESC' or $_dir = 'ASC';
				$fieldName = isset($column['column']) ? $column['column'] : $column['field_name'];
				if($this->_listBlock)$this->_listBlock->applySort($fieldName,$_dir);
				else if(isset($column['sort_callback']))call_user_func($column['sort_callback'],$this->_select,$fieldName,$_dir);
				else $this->_select->order("$fieldName $dir");
			}
			$this->_sortData = array('column'=>$columnId,'dir'=>$dir);
		}
		return $this;
	}

	/**
	 * @param array $column
	 * @param string|array $keyword
	 * @return Admin_Block_Grid
	 */
	protected function _applyFilter(array &$column,&$keyword){
		$columnName = isset($column['column']) ? $column['column'] : $column['field_name'];
		if(is_array($keyword)){
			$kw = array('from'=>isset($keyword['from']) ? $keyword['from'] : '','to'=>isset($keyword['to']) ? $keyword['to'] : '');
			if($column['type']=='datetime' || $column['type']=='date'){
				$kw['from'] = empty($kw['from']) ? '' : (string)Ddm::getHelper('core')->getDateTime()->stringToTime($kw['from']);
				$kw['to'] = empty($kw['to']) ? '' : (string)Ddm::getHelper('core')->getDateTime()->stringToTime($kw['to']);
			}else{
				$kw['from']==='' or ($kw['from'] *= 1);
				$kw['to']==='' or ($kw['to'] *= 1);
			}
			if($kw['from']!=='' && $kw['to']!=='')$value = array('between'=>$kw);
			else if($kw['from']!=='')$value = array('>='=>$kw['from']);
			else if($kw['to']!=='')$value = array('<='=>$kw['to']);
			else $value = 0;
			if($value){
				$this->_filterWhere[$columnName] = $value;
			}
		}else if(($keyword = trim($keyword))!==''){
			$this->_filterWhere[$columnName] = $keyword;
		}
		if($this->_filterWhere){
			if(isset($column['filter_callback']))
				call_user_func($column['filter_callback'],$this->_select,$columnName,$this->_filterWhere[$columnName]);
			else if($this->_listBlock)
				$this->_listBlock->applyFilter($column,$columnName,$this->_filterWhere[$columnName]);
			else
				$this->_select->where($columnName,$this->getCondition($column,$this->_filterWhere[$columnName]));
		}
		return $this;
	}
}
