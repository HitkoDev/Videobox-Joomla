<?php
/**	
 *	@author		HitkoDev http://hitko.eu/videobox
 *	@copyright	Copyright (C) 2016 HitkoDev All Rights Reserved.
 *	@license	http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *	@package	plg_videobox_html5 - HTML5 adapter for Videobox
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
 
class plgVideoboxHTML5 extends JPlugin {
	
	public function onLoadProcessors($config){
        HTML5Video::$pluginProps['h5Convert'] = $this->params->get('h5Convert', '0');
		return 'HTML5Video::getInstance';
	}
    
    public function renderVbPlayer($videobox){
        $app = JFactory::getApplication();
		$document = JFactory::getDocument();
        $req_video = JRequest::getVar('vb-video', false);
		if($app->isSite() && method_exists($document, 'addCustomTag') && $req_video){
            $template = $this->params->get('playerTpl', '');

            $autoplay = trim(JRequest::getVar('autoplay', ''));
            $title = trim(JRequest::getVar('title', ''));
            $s = trim(JRequest::getVar('start', ''));
            $e = trim(JRequest::getVar('end', ''));
            $c = trim(JRequest::getVar('color', ''));
            $start = 0;
            $end = 0;
            
            if(is_numeric(str_replace(':', '', $s))){
                $off = explode (':', $s);
                foreach($off as $off1){
                    $start = $start*60 + $off1;
                }
            }
            if(is_numeric(str_replace(':', '', $e))){
                $off = explode (':', $e);
                foreach($off as $off1){
                    $end = $end*60 + $off1;
                }
            }
            
            $video = null;
            $video = $videobox->getVideo(array('id' => $req_video, 'title' => $title, 'start' => $start, 'end' => $end));
            if($video){
                $thumb = $videobox->videoThumbnail($video, true);
                $_video = array(
                    'poster' => $thumb[0],
                    'url' => $video->getSourceUrl(),
                    'assets' => $videobox->config['assets_url'],
                    'title' => $video->getTitle(),
                    'type' => $video->type == 'a' ? 'vjs-audio' : '',
                    'start' => $video->start,
                    'end' => $video->end > $video->start ? $video->end : 0,
                    'auto' => $autoplay ? 1 : 0
                );
                if($c){
                    $overrides = file_get_contents($_GET['dev'] ? '/srv/htdocs/Videobox/dist/overrides.css' : $videobox->config['assets_path'] . 'css/overrides.min.css');
                        $_video['style_override'] = str_replace(array('#005723', '#84d1a4'), array('#' . $c, '#' . $c), $overrides);
                }
                $sources = '';
                foreach($video->getSourceFormats() as $source){
                    $sources .= '<source src="' . $_video['url'] . '.' . $source[0] . '" type="' . $source[1] . '">';
                }
                $_video['sources'] = $sources;
                echo($videobox->parseTemplate($template, $_video));
            }
            exit();
        }
    }
	
}

class HTML5Video extends VideoboxAdapter {
	
	public static $pluginProps = array();
    
    public static $vid = array(
        array('mp4', 'ogv', 'webm'), 
        array('video/mp4', 'video/ogg', 'video/webm')
    );
    public static $aud = array(
        array('mp3', 'oga', 'wav', 'webm'), 
        array('audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/webm')
    );
    public static $img = array(
        array('jpg', 'jpeg', 'png', 'gif'), 
        array(IMAGETYPE_JPEG, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF)
    ); 
	
	public static function getInstance($scriptProperties = array()){
		/*
         *	$scriptProperties['id'] - url ending with one of the known file extensions
         */
		$ext = pathinfo($scriptProperties['id']);
        $file = $ext['dirname'] . '/' . $ext['filename'];
        $ext = $ext['extension'];

        if(in_array(strtolower($ext), HTML5Video::$vid[0]) || in_array(strtolower($ext), HTML5Video::$aud[0])){
            $file = str_replace(rtrim(JURI::root(), '/'), '', $file);
            $local = true;
            if(substr($file, 0, 7) == 'http://' || substr($file, 0, 8) == 'https://' || substr($file, 0, 2) == '//') $local = false;
            $paths = array();
            if($local){
                $paths['urlFullWithPath'] = rtrim(JURI::root(), '/') . '/' . ltrim($file, '/');
                $paths['pathAbsoluteWithPath'] = rtrim(JPATH_ROOT, DS) . DS . ltrim(str_replace('/', DS, $file), DS);
            } else {
                $paths['urlFullWithPath'] = $file;
                $paths['pathAbsoluteWithPath'] = $file;
            }
            $scriptProperties['paths'] = $paths;
            $scriptProperties['local'] = $local;
            $scriptProperties['ext'] = $ext;
            $scriptProperties['scriptsDir'] = rtrim(JPATH_LIBRARIES, DS) . DS . 'videobox' . DS . 'scripts' . DS;
            return new HTML5Video(array_merge(self::$pluginProps, $scriptProperties));
        }
        return false;
	}
	
	function __construct($properties = array()) {
		parent::__construct($properties);
		if(in_array(strtolower($properties['ext']), self::$aud[0])) $this->type = 'a';
	}

	function getTitle($forced = false){
		if($forced && $this->title==''){
			return $this->properties['paths']['urlFullWithPath'] . '.' . $this->properties['ext'];
		} else {
			return $this->title; 
		}
	}
	
	function getThumb(){
		if($this->properties['local']){
			$orig = $this->properties['paths']['pathAbsoluteWithPath'] . '.' . $this->properties['ext'];
			$dest = $this->properties['paths']['pathAbsoluteWithPath'];
			if(!is_file($dest . '.jpg')) shell_exec($this->properties['scriptsDir'] . 'thumb_' . $this->type . '.sh ' . escapeshellarg($orig) . ' ' . escapeshellarg($dest));
			if(is_file($dest . '.jpg')) return array($dest . '.jpg', IMAGETYPE_JPEG);
		} 
        for($i = 0; $i < count(self::$img[0]) && $i < count(self::$img[1]); $i++){
            if($this->properties['local']){
                $dest = $this->properties['paths']['pathAbsoluteWithPath'];
                if(is_file($dest . '.' . self::$img[0][$i])) return array($dest . '.' . self::$img[0][$i], self::$img[1][$i]);
            } else {
                $dest = $this->properties['paths']['urlFullWithPath'];
                if(self::is_file_remote($dest . '.' . self::$img[0][$i])) return array($dest . '.' . self::$img[0][$i], self::$img[1][$i]);
            }
        }
		return '';
	}
	
	function getPlayerLink($autoplay = false){
		$props = array(
			'vb-video' => $this->id,
			'autoplay' => $autoplay ? 1 : 0
		);
		if($this->title) $props['title'] = $this->title;
		if($this->start > 0) $props['start'] = $this->splitOffset($this->start);
		if($this->end > 0) $props['end'] = $this->splitOffset($this->end);
        if($this->properties['color']) $props['color'] = $this->properties['color'];
		//return $this->properties['modx']->makeUrl($this->properties['modx']->resourceIdentifier, '', $props, 'full');
        return JRoute::_('index.php?' . http_build_query($props));
	}
	
	function getSourceUrl(){
		if($this->properties['local'] && $this->properties['h5Convert']){
			$orig = $this->properties['paths']['pathAbsoluteWithPath'] . '.' . $this->properties['ext'];
			$dest = $this->properties['paths']['pathAbsoluteWithPath'];
			if($this->type == 'a'){
				shell_exec($this->properties['scriptsDir'] . 'audio.sh ' . escapeshellarg($orig) . ' ' . escapeshellarg($dest) . ' > /dev/null 2>/dev/null &');
			} else {
				shell_exec($this->properties['scriptsDir'] . 'video.sh ' . escapeshellarg($orig) . ' ' . escapeshellarg($dest) . ' > /dev/null 2>/dev/null &');
			}
		}
		return $this->properties['paths']['urlFullWithPath'];
	}
	
	function getSourceFormats(){
		$ret = array();
		if($this->type == 'v'){
			for($i = 0; $i < count(self::$vid[0]) && $i < count(self::$vid[1]); $i++){
				if($this->properties['local']){
                    if(is_file($this->properties['paths']['pathAbsoluteWithPath'] . '.' . self::$vid[0][$i])) $ret[] = array(self::$vid[0][$i], self::$vid[1][$i]);
                } else {
                    if(self::is_file_remote($this->properties['paths']['urlFullWithPath'] . '.' . self::$vid[0][$i])) $ret[] = array(self::$vid[0][$i], self::$vid[1][$i]);
                }
			}
		}
		if($this->type == 'a'){
			for($i = 0; $i < count(self::$aud[0]) && $i < count(self::$aud[1]); $i++){
				if($this->properties['local']){
                    if(is_file($this->properties['paths']['pathAbsoluteWithPath'] . '.' . self::$aud[0][$i])) $ret[] = array(self::$aud[0][$i], self::$aud[1][$i]);
                } else {
                    if(self::is_file_remote($this->properties['paths']['urlFullWithPath'] . '.' . self::$aud[0][$i])) $ret[] = array(self::$aud[0][$i], self::$aud[1][$i]);
                }
			}
		}
		return $ret;
	}
    
    static function is_file_remote($url){
        stream_context_set_default(array(
            'http' => array(
                'method' => 'HEAD'
            )
        ));
        $headers = get_headers($url);
        stream_context_set_default(array(
            'http' => array(
                'method' => 'GET'
            )
        ));
        return self::parseHeaders($headers)['status'] == 200;
    }
    
    static function parseHeaders(array $headers, $header = null){
        $output = array();

        if ('HTTP' === substr($headers[0], 0, 4)) {
            list(, $output['status'], $output['status_text']) = explode(' ', $headers[0]);
            unset($headers[0]);
        }

        foreach ($headers as $v) {
            $h = preg_split('/:\s*/', $v);
            $output[strtolower($h[0])] = $h[1];
        }

        if (null !== $header) {
            if (isset($output[strtolower($header)])) {
                return $output[strtolower($header)];
            }

            return;
        }

        return $output;
    }
	
}