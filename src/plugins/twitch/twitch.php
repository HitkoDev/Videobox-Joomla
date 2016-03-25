<?php
/**	
 *	@author		HitkoDev http://hitko.eu/videobox
 *	@copyright	Copyright (C) 2016 HitkoDev All Rights Reserved.
 *	@license	http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *	@package	plg_videobox_twitch - Twitch adapter for Videobox
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program. If not, see <http://www.gnu.org/licenses/>
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted Access' );

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