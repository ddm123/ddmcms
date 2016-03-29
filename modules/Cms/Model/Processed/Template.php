<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Cms_Model_Processed_Template {
	protected $_string = '';
	protected $_templateVars = array();
	protected $_addCssFiles = array();
	protected $_addJsFiles = array();
	protected $_styles = array();
	protected $_isGetStyle = false;

	/**
	 * @param string $string
	 * @param array $templateVars
	 */
	public function __construct($string,array $templateVars = array()){
		$this->_string = $string;
		if($templateVars)$this->_templateVars = $templateVars;
	}

	/**
	 * @return array
	 */
	public function getCssFiles(){
		return $this->_addCssFiles;
	}

	/**
	 * @return array
	 */
	public function getJsFiles(){
		return $this->_addJsFiles;
	}

	/**
	 * @return array
	 */
	public function getStyles(){
		return $this->_styles;
	}

	/**
	 * @param string $string
	 * @return Cms_Model_Processed_Template
	 */
	public function setString($string){
        $this->_string = $string;
		return $this;
    }

	/**
	 * @param array $templateVars
	 * @return Cms_Model_Processed_Template
	 */
	public function setTemplateVars(array $templateVars){
        $this->_templateVars = $templateVars;
		return $this;
    }

	/**
	 * @param bool $flag
	 * @return Cms_Model_Processed_Template
	 */
	public function isGetStyle($flag = 'get'){
		if($flag==='get')return $flag;

		$this->_isGetStyle = (bool)$flag;
		return $this;
	}

	public function processed(){
		if($this->_string){
			$result = $this->_string;
			if(preg_match_all('/\{\?(block|echo|addCss|addJs)(\s+.+?)?\?\}/i',$result,$matches,PREG_SET_ORDER)){
				foreach($matches as $matche){
					$replace = NULL;
					switch(strtolower($matche[1])){
						case 'addcss':
							$replace = isset($matche[2]) ? $this->_processedAddCss($matche[2]) : '';
							break;
						case 'addjs':
							$replace = isset($matche[2]) ? $this->_processedAddJs($matche[2]) : '';
							break;
						case 'block':
							$replace = isset($matche[2]) ? $this->_processedBlock($matche[2]) : NULL;
							break;
						case 'echo':
							$replace = isset($matche[2]) ? $this->_processedEcho($matche[2]) : NULL;
							break;
					}
					if($replace!==NULL)$result = str_replace($matche[0],$replace,$result);
				}
			}
			//找<style>...</style>
			if($this->_isGetStyle){
				$result = preg_replace_callback('/\s*<style([^>]*)>(.*?)<\/style>\s*/is',array($this,'_getStylesCallback'),$result);
			}

			return $result;
		}
		return NULL;
	}

	/**
	 * @param string $matcheParams
	 * @return string
	 */
	protected function _processedAddCss($matcheParams){
		if($matcheParams){
			$params = preg_split('/\s+(\w+)\s*=/',$matcheParams,NULL,PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
			foreach($params as $key=>$value){
				if($value=='file')$this->_addCssFiles[] = trim($params[$key+1],'"\'');
			}
		}
		return '';
	}

	/**
	 * @param string $matcheParams
	 * @return string
	 */
	protected function _processedAddJs($matcheParams){
		if($matcheParams){
			$params = preg_split('/\s+(\w+)\s*=/',$matcheParams,NULL,PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
			foreach($params as $key=>$value){
				if($value=='file')$this->_addJsFiles[] = trim($params[$key+1],'"\'');
			}
		}
		return '';
	}

	/**
	 * @param string $matcheParams
	 * @return string
	 */
	protected function _processedBlock($matcheParams){
		if($matcheParams){
			$params = preg_split('/\s+(\w+)\s*=/',$matcheParams,NULL,PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
			$p = array();
			foreach($params as $key=>$value){
				if(!isset($params[$key+1]))break;
				if($key%2==1)continue;

				$paramValue = trim($params[$key+1]);
				$isAllow = $this->_validMatcheParam($paramValue);
				if(preg_match('/^(["\'])([^\\1]*)\\1$/',$paramValue,$matches)){
					$p[$value] = array('value'=>$matches[2],'type'=>'string');
				}else if(preg_match('/^\(([^\)]*)\)$/',$paramValue,$matches)){
					if($isAllow){
						$matches[1] = preg_replace_callback('/\$(\w+)([^\$]*)/',array($this,'_parseTemplateVarsCallback'),$matches[1]);
						$p[$value] = array('value'=>$matches[1],'type'=>'method');
					}
				}else if($isAllow){
					$paramValue = preg_replace_callback('/\$(\w+)([^\$]*)/',array($this,'_parseTemplateVarsCallback'),$paramValue);
					$p[$value] = array('value'=>$paramValue,'type'=>'var');
				}
			}
			if(isset($p['class']) && preg_match('/^\w+$/',$p['class']['value']) && class_exists($p['class']['value'],true) && ($block = $this->_initBlock($p))){
				return $block->toHtml(true);
			}
		}
		return NULL;
	}

	/**
	 * @param string $matcheParams
	 * @return string
	 */
	protected function _processedEcho($matcheParams){
		if($this->_validMatcheParam($matcheParams)){
			$matcheParams = preg_replace_callback('/\$(\w+)([^\$]*)/',array($this,'_parseTemplateVarsCallback'),$matcheParams);
			eval('$value = '.$matcheParams.';');
			return (string)$value;
		}
		return NULL;
	}

	/**
	 * @param array $p
	 * @return Core_Block_Abstract
	 */
	protected function _initBlock($p){
		if(isset($this->_templateVars['template_object']) && count($block = explode('_Block_',$p['class']['value'],2))==2){
			unset($p['class']);
			$block = $this->_templateVars['template_object']->createBlock($block[0],$block[1]);
			foreach($p as $k=>$v){
				if($v['type']=='string')$block->$k = $v['value'];
				else if($v['type']=='method')eval('$block->'.$k.'('.$v['value'].');');
				else if($v['type']=='var')eval('$block->'.$k.' = '.$v['value'].';');
			}
			return $block;
		}
		return NULL;
	}

	/**
	 * @param string $paramValue
	 * @return boolean
	 */
	protected function _validMatcheParam($paramValue){
		//出于安全考虑不允许多个表达式,也不允许直接调用PHP的内置函数
		if(strpos(htmlspecialchars_decode($paramValue),';')!==false || preg_match('/\b(?:include|include_once|require|require_once)\b/i',$paramValue))return false;
		if(preg_match_all('/([^\w]{0,2})\s*\b\w+\s*\(/',$paramValue,$matches)){
			foreach($matches[1] as $s){
				if($s!='->' && $s!='::')return false;
			}
		}
		return true;
	}

	/**
	 * @param array $matches
	 * @return string
	 */
	protected function _parseTemplateVarsCallback($matches){
		return isset($this->_templateVars[$matches[1]]) ? '$this->_templateVars[\''.$matches[1].'\']'.$matches[2] : "'".addslashes($matches[0])."'";
	}

	/**
	 * @param array $matches
	 * @return string
	 */
	protected function _getStylesCallback($matches){
		$this->_styles[] = $matches[2];
		return '';
	}
}