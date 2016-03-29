<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Language_Block_Adminhtml_List extends Admin_Block_List_Abstract {
	/**
	 * @return Language_Block_Adminhtml_List
	 */
	protected function _initGrid(){
		$this->_grid = $this->_createGridBlock();
		$this->_grid->titleText = Ddm::getTranslate('language')->translate('网站语言');
		$this->_grid->defaultSort = 'position';//是$columnId, 而不是字段名
		$this->_grid->defaultDir = 'ASC';
		$this->_grid->primaryKey = 'language_id';
		$this->_grid->saveFieldValueUrl = Ddm::getUrl('*/*/save-field-value');

		$select = Ddm_Db::getReadConn()->getSelect();
		$select->from(array('a'=>Ddm_Db::getTable('language')),'*');
		$this->_grid->setSelect($select);

		return $this;
	}

	/**
	 * @return Language_Block_Adminhtml_List
	 */
	protected function _prepareColumns(){
		$actions = array(
			array('label'=>Ddm::getTranslate('admin')->translate('修改'),'url'=>Ddm::getLanguage()->getUrl('*/*/edit',array('id'=>'{language_id}'))),
			array(
				'label'=>Ddm::getTranslate('admin')->translate('删除'),
				'url'=>Ddm::getLanguage()->getUrl('*/*/delete',array('id'=>'{language_id}')),
				'onclick'=>'deleteLanguage(this,event)'
			)
		);
		if(Admin_Model_Admin::loggedInAdmin()->isAllow('language/language','translate')){
			$actions[] = array('label'=>Ddm::getTranslate('language')->translate('翻译'),'url'=>Ddm::getLanguage()->getUrl('*/adminhtml-translate',array('id'=>'{language_id}')));
		}
		$this->getGrid()
			->addColumn('id',array(
				'label'=>'ID',
				'width'=>80,
				'type'=>'number',
				'field_name'=>'language_id',
				'column'=>'a.language_id',
				'sort'=>true
			))
			->addColumn('code',array(
				'label'=>Ddm::getTranslate('language')->translate('语言代码'),
				'type'=>'text',
				'field_name'=>'language_code',
				'column'=>'a.language_code',
				'search'=>true,
				'edit'=>true,
				'sort'=>true
			))
			->addColumn('name',array(
				'label'=>Ddm::getTranslate('core')->translate('名称'),
				'type'=>'text',
				'field_name'=>'language_name',
				'column'=>'a.language_name',
				'search'=>true,
				'edit'=>true,
				'sort'=>true
			))
			->addColumn('enable',array(
				'label'=>Ddm::getTranslate('core')->translate('启用'),
				'type'=>'bool',
				'field_name'=>'is_enable',
				'column'=>'a.is_enable',
				'search'=>true,
				'edit'=>true
			))
			->addColumn('position',array(
				'label'=>Ddm::getTranslate('core')->translate('位置'),
				'type'=>'number',
				'field_name'=>'position',
				'column'=>'a.`position`',
				'edit'=>true,
				'sort'=>true
			))
			->addColumn('action',array(
				'label'=>Ddm::getTranslate('admin')->translate('操作'),
				'width'=>160,
				'type'=>'action',
				'actions'=>$actions
			));
		$this->getGrid()->addJs('function deleteLanguage(o,ev){doane(ev);'
			.'msg_box("'.Ddm::getTranslate('language')->translate('该操作不可撤消，确定要删除该语言？').'<br />'.Ddm::getTranslate('language')->translate('注：这也将会删除该语言的全部翻译').'",'.
				'"'.Ddm::getTranslate('core')->translate('提示').'",{'.
				'"'.Ddm::getTranslate('core')->translate('是').'":function(){this.window.close();window.location.href = o.href;},'.
				'"'.Ddm::getTranslate('core')->translate('否').'":function(){this.window.close();}'.
			'});}');

		return $this;
	}

	protected function _prepareMassaction(){
		parent::_prepareMassaction();
		$this->getGrid()->removeAction('delete');
		return $this;
	}
}
