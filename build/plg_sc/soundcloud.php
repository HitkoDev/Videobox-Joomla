<?php
// no direct access
defined( '_JEXEC' ) or die;
JLoader::discover('Videobox', JPATH_LIBRARIES . '/videobox');
 
class plgVideoboxSoundCloud extends JPlugin {
	
	public function onLoadProcessors($config){
        TwitchVideo::$pluginProps['scVisual'] = $this->params->get('scVisual', '1');
		return 'SoundCloudVideo::getInstance';
	}
	
}

class SoundCloudVideo extends VideoboxAdapter {
	
	public static $pluginProps = array();
	
	public static function getInstance($scriptProperties = array()){
		/*
         *	$scriptProperties['id'] - link to the song (https://soundcloud.com/alestorm/shipwrecked)
         */
		if(strpos($scriptProperties['id'], 'soundcloud')!==false){
            return new SoundCloudVideo(array_merge(self::$pluginProps, $scriptProperties));
        }
        return false;
	}
    
    public $type = 'a';
	
	function getThumb(){
		$data = json_decode(file_get_contents('http://soundcloud.com/oembed?format=json&url=' . rawurlencode($this->id)), true);
		if($data){
			$data = explode('?', $data['thumbnail_url']);
			$img = $data[0];
			$im = @getimagesize($img);
			if($im !== false) return array($img, $im[2]);
		}
		return false;
	}
	
	function getPlayerLink($autoplay = false){
		$src = 'https://w.soundcloud.com/player/?url=' . rawurlencode($this->id) . '&show_artwork=true';
		if($autoplay) $src .= '&auto_play=true';
		if(isset($this->properties['color']) && $this->properties['color']) $src .= '&color=' . $this->properties['color'];
		if(isset($this->properties['scVisual']) && !$this->properties['scVisual']){
			$src .= '&visual=false';
		} else {
			$src .= '&visual=true';
		}
		return $src;
	}
	
}