<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Image {
	protected $_image = NULL;

	public function __construct($imageLib = 'gd') {
		$className = 'Ddm_Image_'.ucfirst($imageLib);
		$this->_image = new $className();
	}

	/**
	 * 指定一个被修改的图片
	 * @param type $imageFile
	 * @return Ddm_Image_Interface
	 */
	public function setImageFile($imageFile){
		return $this->_image->setImageFile($imageFile);
	}

	/**
	 * 等比例缩放图片
	 * @param string $newImage 另存为这个文件
	 * @param int $w
	 * @param int $h
	 * @param string $bgcolor 背景填充颜色(十六进制颜色字符串值), 如果为true, 则为白色(#FFFFFF), 也可以是一个文件
	 * @param string $mask 附加这张图片在上面
	 * @param int|string $padding 相当于CSS的padding, 只有empty($bgcolor)为false时才有效
	 * @return Ddm_Image_Interface
	 */
	public function setimgsize($newImage, $w = 80, $h = 80, $bgcolor = true, $mask = '', $padding = 0){
		return $this->_image->setimgsize($newImage, $w, $h, $bgcolor, $mask, $padding);
	}

	/**
	 * 生成缩略图, 这个和 setimgsize() 方法不同, 这个会裁剪图片(取图片中间部分)
	 * @param string $newImage 另存为这个文件
	 * @param int $width
	 * @param int $height
	 * @param string $bgcolor
	 * @return Ddm_Image_Interface
	 */
	public function resizeImage($newImage, $width = 80, $height = 80, $bgcolor = 'FFFFFF'){
		return $this->_image->resizeImage($newImage, $width, $height, $bgcolor);
	}

	/**
	 * 给图片加水印
	 * @param string $watermark 可以是一个字符串(水印文字)或图片路径(图片水印)
	 * @param string $font 水印文字字体文件, 如果是使用文字作为水印, 该参数是必须的
	 * @param int $fontSize
	 * @param string $color
	 * @return Ddm_Image_Interface
	 */
	public function watermark($watermark = 'DDM', $font = '', $fontSize = 14, $color='FF0000'){
		return $this->_image->watermark($watermark, $font, $fontSize, $color);
	}
}