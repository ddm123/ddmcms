<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Admin_Block_Admin_User_List extends Admin_Block_List_Abstract {
	protected $_loggedInAdminId = 0;

	/**
	 * @return Admin_Block_Admin_User_List
	 */
	protected function _initGrid(){
		$this->_grid = $this->_createGridBlock();
		$this->_grid->titleText = Ddm::getTranslate('admin')->translate('管理员');
		$this->_grid->defaultSort = 'id';//是$columnId, 而不是字段名
		$this->_grid->primaryKey = 'admin_id';
		$this->_grid->getFieldValueUrl = Ddm::getLanguage()->getUrl('*/*/get-field-value');
		$this->_grid->saveFieldValueUrl = Ddm::getLanguage()->getUrl('*/*/save-field-value');

		$select = Ddm_Db::getReadConn()->getSelect();
		$select->from(array('a'=>Ddm_Db::getTable('admin_user')));
		$this->_grid->setSelect($select);

		$this->_loggedInAdminId = Admin_Model_Admin::loggedInAdmin()->getId();
		$this->_grid->setDisableRowCallback(array($this,'isSelf'));

		return $this;
	}

	/**
	 * @return Admin_Block_Admin_User_List
	 */
	protected function _prepareColumns(){
		$this->getGrid()
			->addColumn('id',array(
				'label'=>'ID',
				'width'=>80,
				'type'=>'number',
				'field_name'=>'admin_id',
				'column'=>'a.admin_id',
				'sort'=>true
			))
			->addColumn('name',array(
				'label'=>Ddm::getTranslate('admin')->translate('用户名'),
				'type'=>'text',
				'field_name'=>'admin_name',
				'column'=>'a.admin_name',
				'search'=>true,
				'edit'=>true,
				'sort'=>true
			))
			->addColumn('description',array(
				'label'=>Ddm::getTranslate('admin')->translate('描述'),
				'field_name'=>'description',
				'column'=>'a.description',
				'search'=>true,
				'edit'=>true,
				'sort'=>true
			))
			->addColumn('loginnum',array(
				'label'=>Ddm::getTranslate('admin')->translate('登录次数'),
				'width'=>80,
				'type'=>'number',
				'field_name'=>'admin_loginnum',
				'column'=>'a.admin_loginnum',
				'search'=>true,
				'edit'=>true,
				'sort'=>true
			))
			->addColumn('logintime',array(
				'label'=>Ddm::getTranslate('admin')->translate('最后登录'),
				'type'=>'datetime',
				'field_name'=>'admin_logintime',
				'column'=>'a.admin_logintime',
				'search'=>true,
				'sort'=>true
			))
			->addColumn('is_active',array(
				'label'=>Ddm::getTranslate('core')->translate('启用'),
				'type'=>'bool',
				'field_name'=>'is_active',
				'column'=>'a.is_active',
				'search'=>true,
				'edit'=>true
			))
			->addColumn('edit_name',array(
				'label'=>Ddm::getTranslate('admin')->translate('允许修改用户名'),
				'type'=>'bool',
				'field_name'=>'edit_name',
				'column'=>'a.edit_name',
				'search'=>true,
				'edit'=>true
			))
			->addColumn('edit_pass',array(
				'label'=>Ddm::getTranslate('admin')->translate('允许修改密码'),
				'type'=>'bool',
				'field_name'=>'edit_pass',
				'column'=>'a.edit_pass',
				'search'=>true,
				'edit'=>true
			))
			->addColumn('action',array(
				'label'=>Ddm::getTranslate('admin')->translate('操作'),
				'width'=>100,
				'type'=>'action',
				'actions'=>array(
					array('label'=>Ddm::getTranslate('admin')->translate('修改'),'url'=>Ddm::getLanguage()->getUrl('*/*/edit',array('id'=>'{admin_id}')))
				)
			));
		return $this;
	}

	protected function _prepareMassaction(){
		$this->getGrid()
			->addAction('active',array(
				'url'=>Ddm::getLanguage()->getUrl('*/*/active',array('value'=>1)),
				'label'=>Ddm::getTranslate('core')->translate('启用')
			))
			->addAction('inactive',array(
				'url'=>Ddm::getLanguage()->getUrl('*/*/active',array('value'=>0)),
				'label'=>Ddm::getTranslate('core')->translate('禁用')
			));
		return parent::_prepareMassaction();
	}

	/**
	 * @param array $row
	 * @return bool
	 */
	public function isSelf($row){
		return $this->_loggedInAdminId==$row['admin_id'];
	}
}
