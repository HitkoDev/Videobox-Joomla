<?php
/**	
 *	@author		HitkoDev http://hitko.eu/videobox
 *	@copyright	Copyright (C) 2016 HitkoDev All Rights Reserved.
 *	@license	http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *	@package	lib_videobox - Videobox
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

define('DS', DIRECTORY_SEPARATOR);

jimport('joomla.html.pagination'); 

class VideoboxVideobox {

    public $config = array();
    public $gallery = -1;
    private $pages = array();

    private $cache = array();

    function __construct(array &$config = array()){
        $this->setConfig($config);

        if(isset($_GET['vblimitstart'])){
            $p = explode(',', rawurldecode($_GET['vblimitstart']));
            foreach($p as $page){
                $this->pages[] = (int) $page;
            }
        }
    }

    public static function active($var) {
        return $var && is_array($var) && isset($var['expires']) && ($var['expires'] == 0 || $var['expires'] > time());
    }

    function setConfig(array &$config = array()){
        $this->config = array_merge(array(
            'assets_url' => rtrim(JURI::base(), '/') . '/libraries/videobox/',
            'assets_path' => rtrim(JPATH_LIBRARIES, DS) . DS . 'videobox' . DS,
            'core_path' => rtrim(JPATH_LIBRARIES, DS) . DS . 'videobox' . DS,
        ), $config);

        $this->config['cache'] = true;
        $this->config['cacheFile'] = rtrim(JPATH_CACHE, DS) . DS . 'vb-cache';

        $this->cache = file_exists($this->config['cacheFile']) ? unserialize(file_get_contents($this->config['cacheFile'])) : false;
        if(!$this->cache) $this->cache = array();
        $this->cache = array_filter($this->cache, 'VideoboxVideobox::active');

        $this->setColor('color', '00a645');
        $this->setColor('tColor', '005723');
        $this->setColor('hColor', '84d1a4');
        $this->setColor('bgColor', '00a645');

        $this->processors = null;
    }

    function setColor($name, $default){
        $this->config[$name] = trim(str_replace('#', '', $this->config[$name]));
        if(strlen($this->config[$name]) != 6) $this->config[$name] = '';
        if(!$this->config[$name]) $this->config[$name] = $default;
    }

    function getProcessors(){
        if($this->processors) return $this->processors;

        JPluginHelper::importPlugin('videobox');
        $dispatcher = JEventDispatcher::getInstance();
        $this->processors = $dispatcher->trigger('onLoadProcessors', array($this->config));

        return $this->processors;
    }

    function getVideo(array $props = array()){
        $prop = array_merge($this->config, $props);
        foreach($this->getProcessors() as $processor){
            $v = call_user_func($processor, $prop);
            if($v) return $v;
        }
        return false;
    }

    function loadAssets(){
        JHtml::stylesheet('libraries/videobox/css/videobox.min.css');
        JHtml::script('libraries/videobox/js/videobox.bundle.js');

        $styleOverride = str_replace(array('.vb-overrides-wrap', '#005723', '#84d1a4'), array('', '#' . $this->config['tColor'], '#' . $this->config['hColor']), file_get_contents($this->config['assets_path'] . 'css' . DS . 'overrides.min.css'));

        $document = JFactory::getDocument();
        $document->addStyleDeclaration($styleOverride);
    }

    function setCache($key, $data){
        $key = md5($key);
        if(!$this->config['cache']) return;
        if(!isset($this->cache[$key])){
            $this->cache[$key] = array(
                'time' => 0,
                'data' => $data
            );
        } else {
            $this->cache[$key]['time'] = 0;
            $this->cache[$key]['data'] = $data;
        }
        file_put_contents($this->config['cacheFile'], serialize($this->cache));
    }

    function getCache($key){
        $key = md5($key);
        if($this->config['cache'] && isset($this->cache[$key])) return $this->cache[$key]['data'];
        return null;
    }

    function parseTemplate($tpl, $properties = array()){
        $rpl = true;
        $d = 0;
        while($rpl){
            $d++;
            preg_match_all("/\[\[\s*\+\s*([^\]\s]*)\s*\]\]/ism", $tpl, $matches, PREG_SET_ORDER);
            $tags = array();
            foreach($matches as $match){
                if(!isset($tags[$match[0]])){
                    if(isset($properties[ $match[1] ])){
                        $tags[$match[0]] = $properties[ $match[1] ];
                    } elseif(isset($this->config[ $match[1] ])){
                        $tags[$match[0]] = $this->config[ $match[1] ];
                    } 
                }
            }
            $n = 0;
            $tpl = str_replace(array_keys($tags), array_values($tags), $tpl, $n);
            $rpl = $n > 0 && $d < 10;
        }
        return preg_replace("/\[\[\s*\+\s*([^\]\s]*)\s*\]\]/ism", "", $tpl);
    }

    function getPage(){
        if(isset($this->pages[$this->gallery])) return $this->pages[$this->gallery];
        return 0;
    }

    function htmldec($string){
        return str_replace(array('&lt;', '&gt;', '&quot;'), array('<', '>', '"'), $string);
    }

    function htmlenc($string){
        return str_replace(array('<', '>', '"'), array('&lt;', '&gt;', '&quot;'), $string);
    }

    function videoThumbnail($video, $no_border = false, $n = 0) {
        // Prevent infinite loop
        if($n > 1) return '';

        $tWidth = $this->config['tWidth'];
        $tHeight = $this->config['tHeight'];
        
        // Get name suffixes
        $name = '';
        if($no_border){
            $name .= '-no_border';
        } else {
            $name .= '-'.$tWidth.'-'.$tHeight;
        }

        // If $video is a VideoboxAdapter object, get its data, otherwise get nobg data
        $isNobg = false;
        if($video instanceof VideoboxAdapter){
            $nobg = 'nobg_' . ($video->type == 'a' ? 'audio' : 'video');
            $hash = md5($video->id . $name);
            $img = $video->getThumb();
        } else {
            // Video is a NOBG name
            $nobg = $video;
            $hash = md5($video . $name . '-' . $this->config['bgColor']);
            $img = array($this->config['assets_path'] . 'img'. DS . $nobg.'.png', IMAGETYPE_PNG);
            $isNobg = true;
        }

        if(!is_dir($this->config['assets_path'] . 'cache')) mkdir($this->config['assets_path'] . 'cache');

        $target = $this->config['assets_path'] . 'cache'. DS . $hash.'.jpg';

        $img_hash = md5($target);

        $ret = $this->getCache($img_hash);
        if($ret) return $ret;

        try{
            $target_info = @getimagesize($target);
        } catch (Exception $ex){

        }
        if($target_info){
            $ret = array(JHTML::image('libraries/videobox/cache/'.$hash.'.jpg', '', null, false, true), $target_info[0], $target_info[1]);
            $this->setCache($img_hash, $ret);
            return $ret;
        }

        $tmpn = tempnam($this->config['assets_path'] . 'cache/', 'vb_');
        copy($img[0], $tmpn);

        if(!extension_loaded('imagick')){

            try {
                switch($img[1]){
                    case IMAGETYPE_JPEG: 
                        $src_img = imagecreatefromjpeg($tmpn);
                        break;
                    case IMAGETYPE_PNG: 
                        $src_img = imagecreatefrompng($tmpn);
                        break;
                    case IMAGETYPE_GIF: 
                        $src_img = imagecreatefromgif($tmpn);
                        break;
                    default:
                        unlink($tmpn);
                        return $this->videoThumbnail($nobg, $no_border, $n + 1);
                }
            } catch (Exception $e) {
                unlink($tmpn);
                return $this->videoThumbnail($nobg, $no_border, $n + 1);
            }
            if(!$src_img) return $this->videoThumbnail($nobg, $no_border, $n + 1);

            $imagedata = array(imagesx($src_img), imagesy($src_img));

            $b_t = 0;
            $b_b = 0;
            $b_l = 0;
            $b_r = 0;

            // Remove border added by video provider
            if($imagedata[0] && $imagedata[1]){

                if($imagedata[0]<=1920 && $imagedata[1]<=1080){

                    for($y = 3; $y < $imagedata[1]; $y++) {
                        for($x = 3; $x < $imagedata[0]; $x++) {
                            if($this->_chkB($this->_gdRGB($src_img, $x, $y))) break 2;
                        }
                        $b_t = $y;
                    }

                    for($y = $imagedata[1]-4; $y >= 0; $y--) {
                        for($x = 3; $x < $imagedata[0] - 3; $x++) {
                            if($this->_chkB($this->_gdRGB($src_img, $x, $y))) break 2;
                        }
                        $b_b = $imagedata[1] - 1 - $y;
                    }

                    for($x = 3; $x < $imagedata[0]; $x++) {
                        for($y = 3; $y < $imagedata[1]; $y++) {
                            if($this->_chkB($this->_gdRGB($src_img, $x, $y))) break 2;
                        }
                        $b_l = $x;
                    }

                    for($x = $imagedata[0]-4; $x >= 0; $x--) {
                        for($y = 3; $y < $imagedata[1]; $y++) {
                            if($this->_chkB($this->_gdRGB($src_img, $x, $y))) break 2;
                        }
                        $b_r = $imagedata[0] - 1 - $x;
                    }

                }

            } else {
                unlink($tmpn);
                return $this->videoThumbnail($nobg,  $no_border, $n + 1);
            }

            $imagedata[0] -= $b_l + $b_r;
            $imagedata[1] -= $b_t + $b_b;

            // Copy and crop
            if($no_border){
                $tWidth = $imagedata[0];
                $tHeight = $imagedata[1];
                $newimg = imagecreatetruecolor($tWidth, $tHeight);
                if($isNobg){
                    list($r, $g, $b) = sscanf($this->config['bgColor'], "%02x%02x%02x");
                    $bgColor = imagecolorallocate($newimg, $r, $g, $b);
                } else {
                    $bgColor = imagecolorallocate($newimg, 0, 0, 0);
                }
                imagefilledrectangle($newimg, 0, 0, $tWidth, $tHeight, $bgColor);
                imagecopyresampled($newimg, $src_img, 0, 0, $b_l, $b_t, $tWidth, $tHeight, $tWidth, $tHeight);
            } else {

                // Calculate new size and offset
                $new_w = $imagedata[0];
                $new_h = $imagedata[1];		

                $new_w = ($tHeight*$new_w) / $new_h;
                $new_h = $tHeight;
                if($new_w > $tWidth){
                    $new_h = ($tWidth*$new_h) / $new_w;
                    $new_w = $tWidth;
                }		

                $new_w = (int)$new_w;
                $new_h = (int)$new_h;
                $off_w = (int)(($tWidth - $new_w)/2);
                $off_h = (int)(($tHeight - $new_h)/2);
                $newimg = imagecreatetruecolor($tWidth, $tHeight);
                $black = imagecolorallocate($newimg, 0, 0, 0);
                imagefilledrectangle($newimg, 0, 0, $tWidth, $tHeight, $black);
                if($isNobg){
                    list($r, $g, $b) = sscanf($this->config['bgColor'], "%02x%02x%02x");
                    $bgColor = imagecolorallocate($newimg, $r, $g, $b);
                    imagefilledrectangle($newimg, $off_w, $off_h, $new_w + $off_w, $new_h + $off_h, $bgColor);
                }
                imagecopyresampled($newimg, $src_img, $off_w, $off_h, $b_l, $b_t, $new_w, $new_h, $imagedata[0], $imagedata[1]);
            }

            // Save the image and return
            imagejpeg($newimg, $target.'__', 95);
            imagedestroy($src_img);
            imagedestroy($newimg);

        } else {

            try {
                $imgM = @new Imagick($tmpn);
                $imagedata = array($imgM->getImageWidth(), $imgM->getImageHeight());
            } catch(Exception $ex) {
                $imagedata = array(0, 0);
            }

            $b_t = 0;
            $b_b = 0;
            $b_l = 0;
            $b_r = 0;

            // Remove border added by video provider
            if($imagedata[0] && $imagedata[1]){

                if($imagedata[0]<=1920 && $imagedata[1]<=1080){

                    for($y = 3; $y < $imagedata[1]; $y++) {
                        for($x = 3; $x < $imagedata[0]; $x++) {
                            if($this->_chkB($imgM->getImagePixelColor($x, $y)->getColor())) break 2;
                        }
                        $b_t = $y + 1;
                    }

                    for($y = $imagedata[1]-4; $y >= 0; $y--) {
                        for($x = 3; $x < $imagedata[0] - 3; $x++) {
                            if($this->_chkB($imgM->getImagePixelColor($x, $y)->getColor())) break 2;
                        }
                        $b_b = $imagedata[1] - $y;
                    }

                    for($x = 3; $x < $imagedata[0]; $x++) {
                        for($y = 3; $y < $imagedata[1]; $y++) {
                            if($this->_chkB($imgM->getImagePixelColor($x, $y)->getColor())) break 2;
                        }
                        $b_l = $x + 1;
                    }

                    for($x = $imagedata[0]-4; $x >= 0; $x--) {
                        for($y = 3; $y < $imagedata[1]; $y++) {
                            if($this->_chkB($imgM->getImagePixelColor($x, $y)->getColor())) break 2;
                        }
                        $b_r = $imagedata[0] - $x;
                    }

                }

            } else {
                unlink($tmpn);
                return $this->videoThumbnail($nobg, $no_border, $n + 1);
            }

            $imagedata[0] -= $b_l + $b_r;
            $imagedata[1] -= $b_t + $b_b;

            $imgM->cropImage($imagedata[0], $imagedata[1], $b_l, $b_t);
            if($no_border){
                $tWidth = $imagedata[0];
                $tHeight = $imagedata[1];
            } else {

                // Calculate new size and offset
                $new_w = $imagedata[0];
                $new_h = $imagedata[1];		

                $new_w = ($tHeight*$new_w) / $new_h;
                $new_h = $tHeight;
                if($new_w > $tWidth){
                    $new_h = ($tWidth*$new_h) / $new_w;
                    $new_w = $tWidth;
                }		

                $new_w = (int)$new_w;
                $new_h = (int)$new_h;
                $off_w = (int)(($tWidth - $new_w)/2);
                $off_h = (int)(($tHeight - $new_h)/2);

                if($isNobg){
                    $imgM->setImageBackgroundColor(new ImagickPixel('#'.$this->config['bgColor']));
                    $imgM->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
                    $imgM->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
                }
                $imgM->setImageBackgroundColor(new ImagickPixel("rgb(0, 0, 0)"));
                $imgM->resizeImage($new_w, $new_h, imagick::FILTER_CATROM, 1);
                $imgM->extentImage($tWidth, $tHeight, -$off_w, -$off_h);
            }
            $imgM->setImageFormat('jpeg');
            $imgM->setImageCompressionQuality(95);
            $imgM->stripImage();
            $imgM->writeImage($target.'__');

        }
        rename($target.'__', $target);
        $ret = array(JHTML::image('libraries/videobox/cache/'.$hash.'.jpg', '', null, false, true), $tWidth, $tHeight);
        $this->setCache($img_hash, $ret);
        unlink($tmpn);
        return $ret;
    }

    function pagination($total, $current, $perPage){

        $pager = new JPagination($total, $current*$perPage, $perPage);
        $pager->prefix = 'vb';
        $pages = $pager->getListFooter();

        $pref = '';
        $i = 0;
        for(; $i < $this->gallery; $i++) $pref .= (isset($this->pages[$i]) ? $this->pages[$i] : 0) . ',';
        $post = '';
        $i++;
        for(; $i < count($this->pages); $i++) $post .= ',' . (isset($this->pages[$i]) ? $this->pages[$i] : 0);

        $html = new DOMDocument();
        $html->loadHTML('<!DOCTYPE html><meta http-equiv="Content-Type" content="text/html; charset=utf-8">'.$pages);
        if($html){
            $xpath = new DOMXpath($html);
            foreach($xpath->query('//a[@href]') as $a){
                $path = $a->getAttribute('href');
                $qry = parse_url($path, PHP_URL_QUERY);
                parse_str($qry, $parts);
                if(isset($parts['vblimitstart'])){
                    $parts['vblimitstart'] /= $perPage;
                } else {
                    $parts['vblimitstart'] = 0;
                }
                $parts['vblimitstart'] = preg_replace("/(^,)|((?<=,),+)|((?<=0)0+)|((,|,0)+$)/m", '', $pref . $parts['vblimitstart'] . $post);
                if(!$parts['vblimitstart']) unset($parts['vblimitstart']);
                $nqry = http_build_query($parts);
                $path = str_replace($qry, $nqry, $path);
                $a->setAttribute('href', $path);
            }
            $pages = $html->saveHTML();
        }

        return $pages;
    }

    protected function _gdRGB($img, $x, $y){
        $rgb = imagecolorat($img, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        return array(
            'r' => $r,
            'g' => $g,
            'b' => $b,
            'a' => 0
        );
    }

    // calculate & check luminosity (black border detection)
    protected function _chkB($rgb){

        $var_R = ($rgb['r'] / 255);
        $var_G = ($rgb['g'] / 255);
        $var_B = ($rgb['b'] / 255);

        $var_R = ($var_R > 0.04045) ? pow((($var_R + 0.055)/1.055), 2.4) : $var_R/12.92;
        $var_G = ($var_G > 0.04045) ? pow((($var_G + 0.055)/1.055), 2.4) : $var_G/12.92;
        $var_B = ($var_B > 0.04045) ? pow((($var_B + 0.055)/1.055), 2.4) : $var_B/12.92;

        $y = $var_R * 0.2126 + $var_G * 0.7152 + $var_B * 0.0722;
        $y = ($y > 0.008856) ? pow($y, 1/3) : 7.787*$y;
        $y = 116*$y;

        return $y > 20;
    }
}