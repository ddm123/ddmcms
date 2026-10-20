<?php
class Core_Design_Adminhtml_Default_Theme
{
	public function init(Admin_Block_Template $template)
	{
		$template->addCss('admin.css','admin');
		$template->addJs('common.js','core');
		$template->addJs('admin.js','admin');
		$template->addComboboxScript();
	}
}

return 'Core_Design_Adminhtml_Default_Theme';
