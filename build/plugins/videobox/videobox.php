<?php
/**	
 *	@author		HitkoDev http://hitko.eu/videobox
 *	@copyright	Copyright (C) 2016 HitkoDev All Rights Reserved.
 *	@license	http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *	@package	plg_system_videobox - Videobox
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

class plgSystemVideobox extends JPlugin {

    private $sets;

    private function getSets(){
        if($this->sets) return $this->sets;

        $sets = json_decode(json_encode($this->params->get('property_sets', array())), true);

        $s2 = array();
        $def = false;
        foreach($sets as $set){
            $key = $set['key'];
            unset($set['key']);
            $s2[$key] = $set;
            if(!$def || $key == 'default') $def = $set;
        }
        $s2['default'] = $def ? $def : array();

        $s2['default']['color'] = $this->params->get('color', '');
        $s2['default']['tColor'] = $this->params->get('tColor', '');
        $s2['default']['hColor'] = $this->params->get('hColor', '');
        $s2['default']['bgColor'] = $this->params->get('bgColor', '');

        $this->sets = $s2;
        return $this->sets;
    }


    public function onBeforeCompileHead(){
        $app = JFactory::getApplication();
        $document = JFactory::getDocument();
        if($app->isSite() && method_exists($document, 'addCustomTag')){
            $videobox = new VideoboxVideobox();
            $sets = $this->getSets();
            $videobox->setConfig($sets['default']);
            $videobox->loadAssets();
        }   
    }

    public function onBeforeRender(){
        $app = JFactory::getApplication();
        $document = JFactory::getDocument();
        if($app->isSite() && method_exists($document, 'addCustomTag')){
            $videobox = new VideoboxVideobox();
            $sets = $this->getSets();
            $videobox->setConfig($sets['default']);

            JPluginHelper::importPlugin('videobox');
            $dispatcher = JEventDispatcher::getInstance();
            $players = $dispatcher->trigger('renderVbPlayer', array($videobox));
        }
    }

    public function onAfterRender(){
        $app = JFactory::getApplication();
        $document = JFactory::getDocument();

        $instances = array(
            'tags' => array(),
            'outputs' => array()
        );

        if($app->isSite() && method_exists($document, 'addCustomTag')){
            $videobox = new VideoboxVideobox();

            $content = JResponse::getBody();
            preg_match_all("/\{\s*videobox\s*([\@\?]?\s*[^`\}]*([^`\}]*`[^`]*?`)*)\s*\}(.*?){\s*\/\s*videobox\s*\}/ism", $content, $matches, PREG_SET_ORDER);

            $sets = $this->getSets();

            foreach($matches as $match){

                $open = trim(strip_tags(html_entity_decode($match[1])));
                $videos = trim(strip_tags(html_entity_decode($match[ count($match) - 1 ])));

                $set = '';
                $props = array();

                if($open){
                    $l = 0;
                    if($open{$l} == '@'){
                        $l++;   // skip the '@' character

                        preg_match('/[\s\?]/ism', $open, $m, PREG_OFFSET_CAPTURE, $l);  // whitespace or '?' character indicate the end of the property set key 

                        $r = count($m) > 0 ? $m[0][1] : strlen($open);  // if there's no white space or '?' character, set key ends at last character

                        $set = substr($open, $l, $r - $l);

                        $l = $r;    // skip property set name
                    }
                    if($l < strlen($open)){
                        preg_match('/\?\s*/ism', $open, $m, PREG_OFFSET_CAPTURE, $l); // properties start after the '?' character

                        if(count($m) > 0){
                            $l = strlen($m[0][0]) + $m[0][1];

                            preg_match_all("/\&\s*([^=`]*)\s*=\s*`([^`]*)`/ism", $open, $m, PREG_SET_ORDER, $l);    // extranct propertes (&key=`value`)

                            foreach($m as $prop) $props[ $prop[1] ] = $prop[2];
                        }
                    }
                }

                $props['videos'] = $videos;
                $instances['tags'][] = $match[0];
                $instances['outputs'][] = $this->generateOutput($videobox, array_merge($sets['default'], isset($sets[$set]) ? $sets[$set] : array(), $props));

            }
        }

        if(count($instances['tags']) > 0) JResponse::setBody(str_replace($instances['tags'], $instances['outputs'], JResponse::getBody()));
    }

    private function generateOutput($videobox, $scriptProperties){
        $videobox->setConfig($scriptProperties);
        $scriptProperties['color'] = $videobox->config['color'];

        $videos = explode('|,', $scriptProperties['videos']);

        $processors = $videobox->getProcessors();

        $vid = array();
        foreach($videos as $key => $video){
            $video = explode('|', $video);
            $title = '';
            if(isset($video[1])) $title = trim($video[1]);
            $title = $videobox->htmldec($title);
            $title = $videobox->htmlenc($title);
            $video = explode('#', $video[0]);
            $id = trim($video[0]);
            $start = 0;
            $end = 0;
            if(count($video) > 1){
                $video = explode('-', trim($video[count($video) - 1]));
                if(count($video) > 0 && is_numeric(str_replace(':', '', trim($video[0])))){
                    $off = explode (':', trim($video[0]));
                    foreach($off as $off1){
                        $start = $start*60 + $off1;
                    }
                }
                if(count($video) > 1 && is_numeric(str_replace(':', '', trim($video[1])))){
                    $off = explode (':', trim($video[1]));
                    foreach($off as $off1){
                        $end = $end*60 + $off1;
                    }
                }
            }
            $prop = array_merge($scriptProperties, array('id' => $id, 'title' => $title, 'start' => $start, 'end' => $end));

            $v = $videobox->getVideo(array('id' => $id, 'title' => $title, 'start' => $start, 'end' => $end));
            if($v) $vid[] = $v;

        }
        $videos = $vid;

        if(count($videos) < 1) return;

        if(!isset($scriptProperties['display']) || !$scriptProperties['display']) $scriptProperties['display'] = count($videos) > 1 ? $scriptProperties['multipleDisplay'] : $scriptProperties['singleDisplay'];
        if($scriptProperties['display'] == 'link') $scriptProperties['display'] = 'links';
        if($scriptProperties['display'] == 'links' && $scriptProperties['player'] == 'vbinline') $scriptProperties['player'] = 'videobox';
        $scriptProperties['display'] = $scriptProperties['display'];
        unset($scriptProperties['multipleDisplay']);
        unset($scriptProperties['singleDisplay']);

        $vbOptions = array();
        foreach($scriptProperties as $k => $v){
            if(substr($k, 0, 3) == "js.") {
                $parts = array_filter(array_map('trim', explode('.', $k)));
                $o = &$vbOptions;
                $i = 0;
                
                if(is_numeric($v)) $v = floatval($v);
                for($i = 1; $i < count($parts); $i++){
                    $part = $parts[$i];
                    if(is_numeric($part)) $part = floatval($part);
                    
                    if($i == count($parts) - 1){
                        $o[$part] = $v;
                    } else {
                        if(!isset($o[$part])) $o[$part] = array();
                        $o = &$o[$part];
                    }
                }
            }
        }
        $vbOptions['width'] = (float)$scriptProperties['pWidth'];
        $vbOptions['height'] = (float)$scriptProperties['pHeight'];
        if(isset($scriptProperties['style'])) $vbOptions['style'] = $scriptProperties['style'];
        if(isset($scriptProperties['class'])) $vbOptions['class'] = $scriptProperties['class'];

        if(count($videos) > 1){
            $tpl = $scriptProperties['display'] == 'links' ? $scriptProperties['linkTpl'] : $scriptProperties['thumbTpl'];
            $start = 0;
            $pagination = '';

            if($scriptProperties['display'] == 'gallery'){
                $videobox->gallery++;
                $start = $videobox->getPage();
                $scriptProperties['gallery_number'] = $videobox->gallery;
                $scriptProperties['gallery_page'] = $start;
                $pagination = $videobox->pagination(count($videos), $start, $scriptProperties['perPage']);
                $start = $start*$scriptProperties['perPage'];
            }

            if($scriptProperties['player'] == 'vbinline' && ($scriptProperties['display'] == 'gallery' || $scriptProperties['display'] == 'slider')){
                $scriptProperties['pWidth'] = $scriptProperties['tWidth'];
                $scriptProperties['pHeight'] = $scriptProperties['tHeight'];
                $vbOptions['width'] = (float)$scriptProperties['pWidth'];
                $vbOptions['height'] = (float)$scriptProperties['pHeight'];
            }

            ksort($scriptProperties);
            $propHash = 'Vb_gallery_' . md5(serialize($scriptProperties));
            $content = $videobox->getCache($propHash);
            if(!$content){
                $n = 0;
                $content = '';
                $props = array('vbOptions' => htmlspecialchars(json_encode($vbOptions)), 'rel' => $scriptProperties['player'], 'pWidth' => $scriptProperties['pWidth'], 'pHeight' => $scriptProperties['pHeight']);
                $filtered = array();
                foreach($videos as $video){
                    $n++;
                    if($start > 0 && $n <= $start) continue;
                    $filtered[] = array(
                        'title' => $video->getTitle(), 
                        'linkText' => $video->getTitle(true), 
                        'link' => $video->getPlayerLink(true), 
                        'thumb' => $videobox->videoThumbnail($video, $scriptProperties['display'] == 'flow'),
                    );
                    if($scriptProperties['display'] == 'gallery' && $n == ($start + $scriptProperties['perPage'])) break;
                }
                $maxR = 0;
                $maxW = $scriptProperties['tWidth'];
                foreach($filtered as $video){
                    $r = $video['thumb'][1]/$video['thumb'][2];
                    if($r > $maxR) $maxR = $r;
                }
                $minR = 0.6;
                foreach($filtered as $video){
                    $r = $video['thumb'][1]/($maxR*$video['thumb'][2]);
                    if($r && $r < $minR) $minR = $r;
                }
                $minR = 1 - log($minR);
                $n = 0;
                foreach($filtered as $video){
                    $v = $videobox->parseTemplate($tpl, array_merge($props, $video, array('thumb' => $video['thumb'][0], 'tWidth' => $video['thumb'][1], 'tHeight' => $video['thumb'][2])));
                    switch($scriptProperties['display']){
                        case 'links':
                            $v = ($n == 0 ? '' : $scriptProperties['delimiter']) . $v;
                            break;
                        case 'slider':
                            $r = $video['thumb'][1]/($maxR*$video['thumb'][2]);
                            $b = 0.25*$r*$maxW*$minR;
                            $v = $videobox->parseTemplate($scriptProperties['sliderItemTpl'], array('content' => $v, 'ratio' => $r, 'basis' => $b));
                            break;
                        default:
                            $scriptProperties['display'] = 'gallery';
                            $r = $video['thumb'][1]/($maxR*$video['thumb'][2]);
                            $b = 0.25*$r*$maxW*$minR;
                            $v = $videobox->parseTemplate($scriptProperties['galleryItemTpl'], array('content' => $v, 'ratio' => $r, 'basis' => $b));
                            break;
                    }
                    $n++;
                    $content .= $v;
                }
                $b = 0.25*$maxW*$minR;
                if($scriptProperties['display'] == 'gallery') for($n = 0; $n < 10; $n++){
                    $v = $videobox->parseTemplate($scriptProperties['galleryItemTpl'], array('ratio' => 1, 'basis' => $b));
                    $content .= $v;
                }
                $videobox->setCache($propHash, $content);
            }
            switch($scriptProperties['display']){
                case 'links':
                    return $content;
                case 'slider':
                    return $videobox->parseTemplate($scriptProperties['sliderTpl'], array('content' => $content, 'basis' => $scriptProperties['tWidth']/2));
                default:
                    return $videobox->parseTemplate($scriptProperties['galleryTpl'], array('content' => $content, 'pagination' => $pagination));
            }
        } else {
            $autoPlay = isset($scriptProperties['autoPlay']) && $scriptProperties['autoPlay'] && $scriptProperties['display'] == 'player' && (!isset($videobox->autoPlay) || !$videobox->autoPlay);
            $scriptProperties['autoPlay'] = $autoPlay;
            if($autoPlay) $videobox->autoPlay = true;
            ksort($scriptProperties);
            $propHash = 'Vb_video_' . md5(serialize($scriptProperties));
            $data = $videobox->getCache($propHash);
            if($data) return $data;
            $video = $videos[0];
            $props = array_merge(array('vbOptions' => htmlspecialchars(json_encode($vbOptions)), 'rel' => $scriptProperties['player'], 'pWidth' => $scriptProperties['pWidth'], 'pHeight' => $scriptProperties['pHeight'], 'tWidth' => $scriptProperties['tWidth'], 'tHeight' => $scriptProperties['tHeight']), array('title' => $video->getTitle(), 'link' => $video->getPlayerLink(in_array($scriptProperties['display'], array('box', 'link', 'links')) || $autoPlay), 'ratio' => (100*$scriptProperties['pHeight']/$scriptProperties['pWidth'])));
            switch($scriptProperties['display']){
                case 'links':
                    $props['linkText'] = isset($linkText) ? trim($linkText) : $video->getTitle(true);
                    $v = $videobox->parseTemplate($scriptProperties['linkTpl'], $props);
                    break;
                case 'box':
                    $thumb = $videobox->videoThumbnail($video);
                    $v = $videobox->parseTemplate($scriptProperties['boxTpl'], array_merge($props, array('thumb' => $thumb[0], 'tWidth' => $thumb[1], 'tHeight' => $thumb[2])));
                    break;
                default:
                    $v = $videobox->parseTemplate($scriptProperties['playerTpl'], $props);
                    break;
            }
            $videobox->setCache($propHash, $v);
            return $v;
        }
    }

}
