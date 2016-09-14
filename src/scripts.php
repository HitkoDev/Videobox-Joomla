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
jimport('joomla.version');

class pkg_videoboxInstallerScript {

    function install($parent) {
        $this->in_up($parent, 'install');
    }

    function update($parent) {
        $this->in_up($parent, 'update');
    }

    function in_up($parent, $type) {
        echo JText::_('PLG_SYSTEM_VIDEOBOX_INSTALL_DESCRIPTION');
    }

    function uninstall($parent) {

    }

    function preflight($type, $parent) {

        $jv = new JVersion();
        if(!$jv->isCompatible('3.6')) {
            $typing = 'installing';
            if($type == 'update') $typing = 'updating';
            Jerror::raiseWarning(null, 'Please update Joomla! to version 3.6 or later before ' . $typing . ' Videobox');
            return false;
        }

    }

    function postflight($type, $parent) {

    }
}