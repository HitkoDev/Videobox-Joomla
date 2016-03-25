<?php
// no direct access
defined( '_JEXEC' ) or die;
JLoader::discover('Videobox', JPATH_LIBRARIES . '/videobox');
 
class plgVideoboxTwitch extends JPlugin {
	
	public function onLoadProcessors($config){
        TwitchVideo::$pluginProps['channelImage'] = $this->params->get('channelImage', 'logo');
		return 'TwitchVideo::getInstance';
	}
	
}

class TwitchVideo extends VideoboxAdapter {
	
	public static $pluginProps = array();
	
	public static function getInstance($scriptProperties = array()){
		/*
         *	$scriptProperties['id'] - Twitch channel or video url
         */
		if(preg_match("/twitch\.tv\/([^\/]+)\/v\/(\d+)/isu", $scriptProperties['id'], $matches) > 0){
            return new TwitchVideo(array_merge(self::$pluginProps, $scriptProperties, array('channel' => $matches[1], 'video' => $matches[2], 'id' => $matches[2])));
        }
        if(preg_match("/twitch\.tv\/([^\/]+)(\/.*)?/isu", $scriptProperties['id'], $matches) > 0){
            return new TwitchVideo(array_merge(self::$pluginProps, $scriptProperties, array('channel' => $matches[1], 'video' => '', 'id' => $matches[1])));
        }
        return false;
	}
    
    public $type = 'c';
    
    function __construct($properties = array()){
		$this->channel = $properties['channel'];
		$this->type = $properties['video'] ? 'v' : 'c';
		parent::__construct($properties);
	}

	function getTitle($forced = false){
		if($forced && $this->title == ''){
			return $this->type == 'v' ? 'https://www.twitch.tv/' . $this->channel . '/v/' . $this->id : 'https://www.twitch.tv/' . $this->channel;
		} else {
			return $this->title; 
		}
	}
	
	function getThumb(){
		$th = parent::getThumb();
		if($th !== false) return $th;
		$data = json_decode(file_get_contents('https://api.twitch.tv/kraken/' . ($this->type == 'v' ? 'videos/v' : 'channels/') . $this->id), true);
		$chImg = $this->properties['channelImage'];
		$img = $this->type == 'v' ? $data['thumbnails'][0]['url'] : $data[$chImg];
		$im = @getimagesize($img);
		if($im !== false) return array($img, $im[2]);
		return false;
	}
	
	function getPlayerLink($autoplay = false){
		$src = 'https://player.twitch.tv/?' . ($this->type == 'v' ? 'video=v' : 'channel=') . $this->id;
		if(!$autoplay) $src .= '&autoplay=false';
		if($this->start != 0) $src .= '&time=' . $this->splitOffset($this->start);
		return $src;
	}
	
}