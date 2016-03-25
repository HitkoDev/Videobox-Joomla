<?php
// no direct access
defined( '_JEXEC' ) or die;
JLoader::discover('Videobox', JPATH_LIBRARIES . '/videobox');
 
class plgVideoboxVimeo extends JPlugin {
	
	public function onLoadProcessors($config){
		return 'VimeoVideo::getInstance';
	}
	
}

class VimeoVideo extends VideoboxAdapter {
	
	public static function getInstance($scriptProperties = array()){
		/*
		 *	$scriptProperties['id'] - one of the following:
		 *		- numeric Vimeo video ID
		 *		- link to the video (http://vimeo.com/4700344)
		 */
		if(is_numeric($scriptProperties['id'])){
			return new VimeoVideo($scriptProperties);
		}
		if(strpos($scriptProperties['id'], 'vimeo')!==false){
			preg_match('/vimeo.com\/([0-9]*?)/isU', $scriptProperties['id'], $v_urls);
			return new VimeoVideo(array_merge($scriptProperties, array('id' => $v_urls[1])));
		}
		return false;
	}
	
	function getTitle($forced = false){
		if($forced && $this->title==''){
			return 'http://vimeo.com/' . $this->id;
		} else {
			return $this->title; 
		}
	}
	
	function getThumb(){
		$th = parent::getThumb();
		if($th !== false) return $th;
		$data = unserialize(file_get_contents('http://vimeo.com/api/v2/video/' . $this->id . '.php'));
		$img = $data[0]['thumbnail_large'];
		$im = @getimagesize($img);
		if($im !== false) return array($img, $im[2]);
		return false;
	}
	
	function getPlayerLink($autoplay = false){
		$src = 'https://player.vimeo.com/video/' . $this->id . '?byline=0&portrait=0';
		$src .= '&autoplay=' . ($autoplay ? '1' : '0');
		if(isset($this->properties['color']) && $this->properties['color']) $src .= '&color=' . $this->properties['color'];
		if($this->start != 0) $src .= '#t=' . $this->splitOffset($this->start);
		return $src;
	}
	
}