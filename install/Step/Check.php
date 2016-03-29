<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Step_Check extends Step_Abstract {
	protected $_template = 'check.phtml';
	protected $_checkResult = NULL;

	/**
	 * @return Step_Check
	 */
	public function check(){
		if($this->_checkResult===NULL){
			$this->_checkResult = array();
			if(!extension_loaded('mysqli') && !extension_loaded('mysql')){
				$this->_checkResult[] = '你需要安装mysqli或者mysql的PHP扩展, 可以安装其中任意一种';
			}
			if(!extension_loaded('gd')){
				$this->_checkResult[] = '你需要安装GD扩展库, 应用于图像处理';
			}
			//if(!extension_loaded('mbstring')){
			//	$this->_checkResult[] = '你需要安装mbstring扩展库, 应用于处理中文字符';
			//}
			if(!extension_loaded('curl')){
				$this->_checkResult[] = '你需要安装CURL扩展库';
			}

			if(!is_dir(SITE_ROOT.'/data')){
				if(false===@mkdir($path,0777)){
					$this->_checkResult[] = '请在'.SITE_ROOT.'/目录下创建一个名为data的文件夹, 并设置有写入的权限';
				}
			}else{
				if(!file_put_contents(SITE_ROOT.'/data/~testCreateFile.txt', 'Please delete this file' ,LOCK_EX)){
					$this->_checkResult[] = SITE_ROOT.'/data 文件夹需要有写入的权限';
				}else if(!unlink(SITE_ROOT.'/data/~testCreateFile.txt')){
					$this->_checkResult[] = SITE_ROOT.'/data 文件夹需要有删除文件的权限';
				}
			}
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function getCheckResult(){
		return $this->_checkResult;
	}

	protected function _beforeRun() {
		$this->clearCache();
		$this->check();
		if(empty($this->_checkResult)){
			Ddm_Request::redirect('./index.php?step=setting');
			$this->_template = NULL;
		}else{
			$this->addTitle('检测您的PHP运行环境');
		}

		return parent::_beforeRun();
	}
}