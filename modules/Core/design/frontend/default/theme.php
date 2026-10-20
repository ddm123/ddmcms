<?php
class Core_Design_Frontend_Default_Theme
{
	public function init(Core_Block_Frontend_Template $template)
	{
		$template->addCss('common.css','core');
		$template->addCss('style.css');
		$template->addJs('common.js','core');
	}
}

return 'Core_Design_Frontend_Default_Theme';
