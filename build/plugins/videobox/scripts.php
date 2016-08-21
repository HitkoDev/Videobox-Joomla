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

jimport('joomla.form.form');
jimport('joomla.plugin.helper');
jimport('joomla.registry.registry');

class plgsystemvideoboxInstallerScript {

    function install($parent) {
        $this->in_up($parent);
    }

    function update($parent) {
        $this->in_up($parent);
    }

    function in_up($parent) {

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName(array('extension_id', 'params')));
        $query->from($db->quoteName('#__extensions'));
        $query->where($db->quoteName('name') . ' LIKE \'plg_system_videobox\'');
        $db->setQuery($query);
        $result = $db->loadRow();

        if($result){
            $params = new JRegistry(json_encode(array('params' => json_decode($result[1], true))));
            $form = new JForm('helper_form');
            $form->repeat = true;
            $form->load($parent->get('manifest'), true, '//config');
            $form->bind($params);

            $data = $form->renderFieldset('basic');
            $html = new DOMDocument('1.0', 'UTF-8');
            $html->loadHTML('<!DOCTYPE html><meta http-equiv="Content-Type" content="text/html; charset=utf-8">'.$data);
            if($html){
                $xpath = new DOMXpath($html);
                $fields = $xpath->query("//*[starts-with(@name,'params')]");
                $newp = array();
                foreach($fields as $field){
                    $parts = $this->splitName($field->getAttribute('name'));
                    if($parts){
                        switch($field->tagName){
                            case 'textarea':
                                $this->set($newp, $parts, $field->textContent);
                                break;

                            case 'select':
                                $multi = $field->hasAttribute('multiple');
                                $val;
                                if($multi){
                                    $val = array();
                                    foreach($field->childNodes as $option){
                                        if($option->hasAttribute('selected')){
                                            $val[] = $option->getAttribute('value');
                                        }
                                    }
                                } else {
                                    $val = null;
                                    foreach($field->childNodes as $option){
                                        if($val === null || $option->hasAttribute('selected')){
                                            $val = $option->getAttribute('value');
                                        }
                                    }
                                } 
                                $this->set($newp, $parts, $val);
                                break;

                            default:
                                switch($field->getAttribute('type')){
                                    case 'checkbox':
                                    case 'radio':
                                        if($field->hasAttribute('checked')){
                                            $this->set($newp, $parts, $field->getAttribute('value'));
                                        }
                                        break;
                                    default:
                                        if($field->hasAttribute('value')){
                                            $this->set($newp, $parts, $field->getAttribute('value'));
                                        }
                                        break;
                                }
                                break;
                        }
                    }
                }
                $params = json_encode($newp['params']);

                $query = $db->getQuery(true);
                $fields = array(
                    $db->quoteName('params') . ' = ' . $db->quote($params)
                );

                $conditions = array(
                    $db->quoteName('extension_id') . ' = ' . $result[0]
                );

                $query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);
                $db->setQuery($query);
                $result = $db->execute();
            }
        }
    }

    function innerHTML($element) { 
        $innerHTML = ""; 
        $children  = $element->childNodes;

        foreach ($children as $child) { 
            $innerHTML .= $element->ownerDocument->saveHTML($child);
        }

        return $innerHTML; 
    } 

    function set(&$arr, $parts, $val){
        $_ar = &$arr;
        $i = 0;
        foreach($parts as $part){
            if($i == count($parts)-1){
                if($part){
                    $_ar[$part] = $val;
                } else {
                    $_ar[] = $val;
                }
            } else {
                if(!isset($_ar[$part])){
                    $_ar[$part] = array();
                }
                $_ar = &$_ar[$part];
                $i++;
            }
        }
    }

    function splitName($name){
        $parts = array();
        $pos = strpos($name, '[');
        if($pos){
            $parts[] = substr($name, 0, $pos);
            preg_match_all("/\[([^\[\]]*)\]/", $name, $matches);
            foreach($matches[1] as $match){
                $parts[] = $match;
            }
        } else {
            $parts[] = $name;
        }
        $n = count($parts);
        for($i = 1; $i < $n; $i++){
            var_dump($parts[$i], $parts[$i-1].'X');
            if($parts[$i] == $parts[$i-1].'X'){
                return false;
            }
        }
        var_dump($parts);
        return $parts;
    }

    function uninstall($parent) {

    }

    function preflight($type, $parent) {

    }

    function postflight($type, $parent) {

    }
}