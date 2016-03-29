<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Admin_Block_Admin_Group_List extends Admin_Block_List_Abstract {
	protected $_loggedInAdminGroups = array();

	/**
	 * @return Admin_Block_Admin_Group_List
	 */
	protected function _initGrid(){
		$this->_grid = $this->_createGridBlock();
		$this->_grid->titleText = Ddm::getTranslate('admin')->translate('管理员组');
		$this->_grid->primaryKey = 'group_id';
		$this->_grid->defaultSort = 'group_id';
		$this->_grid->getFieldValueUrl = '';
		$this->_grid->saveFieldValueUrl = Ddm::getLanguage()->getUrl('*/*/save-field-value');

		$select = Ddm_Db::getReadConn()->getSelect();
		$select->from(array('g'=>Ddm_Db::getTable('admin_group')));
		$this->_grid->setSelect($select);

		$this->_loggedInAdminGroups = Admin_Model_Admin::loggedInAdmin()->getGroups();
		$this->_grid->setDisableRowCallback(array($this,'isSelf'));

		return $this;
	}

	/**
	 * @return Admin_Block_Admin_Group_List
	 */
	protected function _prepareColumns(){
		$this->getGrid()
			->addColumn('group_id',array(
				'label'=>'ID',
				'width'=>80,
				'type'=>'number',
				'field_name'=>'group_id',
				'column'=>'g.group_id',
				'sort'=>true
			))
			->addColumn('group_name',array(
				'label'=>Ddm::getTranslate('admin')->translate('组名称'),
				'field_name'=>'group_name',
				'column'=>'g.group_name',
				'search'=>true,
				'edit'=>true,
				'sort'=>true
			))
			->addColumn('description',array(
				'label'=>Ddm::getTranslate('admin')->translate('描述'),
				'field_name'=>'description',
				'column'=>'g.description',
				'search'=>true,
				'edit'=>true,
				'sort'=>true
			))
			->addColumn('count',array(
				'label'=>Ddm::getTranslate('admin')->translate('用户数'),
				'width'=>80,
				'type'=>'number',
				'field_name'=>'count'
			))
			->addColumn('action',array(
				'label'=>Ddm::getTranslate('admin')->translate('操作'),
				'width'=>100,
				'type'=>'action',
				'actions'=>array(
					array('label'=>Ddm::getTranslate('admin')->translate('修改'),'url'=>Ddm::getLanguage()->getUrl('*/*/edit',array('id'=>'{group_id}')))
				)
			));
		return $this;
	}

	/**
	 * @return Admin_Block_Admin_Group_List
	 */
	protected function _addCount(){
		if($listData = $this->getGrid()->getListData()){
			$groupIds = array();
			foreach($listData as $row)$groupIds[] = $row['group_id'];

			$select = Ddm_Db::getReadConn()->getSelect();
			$select->from(array('g'=>Ddm_Db::getTable('admin_group')),array('group_id','count'=>'COUNT(*)'))
				->innerJoin(array('gu'=>Ddm_Db::getTable('admin_group_user')),"gu.group_id=g.group_id")
				->group('g.group_id')->where('g.group_id',array('in'=>$groupIds));
			$groupCounts = Ddm_Db::getReadConn()->fetchAll($select->__toString(),'group_id');

			foreach($listData as $key=>$row){
				$listData[$key]['count'] = isset($groupCounts[$row['group_id']]) ? isset($groupCounts[$row['group_id']]) : 0;
			}
			$this->getGrid()->setListData($listData);
		}
		return $this;
	}

	/**
	 * @param array $row
	 * @return bool
	 */
	public function isSelf($row){
		return isset($this->_loggedInAdminGroups[$row['group_id']]);
	}

	protected function _beforeToHtml() {
		parent::_beforeToHtml();
		$this->_addCount();
		return $this;
	}
}
