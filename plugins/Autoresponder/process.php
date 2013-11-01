<?php
/**
 * Autoresponder plugin for phplist
 * 
 * This plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * @category  phplist
 * @package   Autoresponder
 * @author    Cameron Lerch (Sponsored by Brightflock -- http://brightflock.com)
 * @copyright 2013 Cameron Lerch
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 * @link      http://brightflock.com
 */
 
/**
 * Call this script with wget from cron at regular intervals. For example:
 * 
 * wget -O - -q -t 1 http://yourphplistdomain.com/admin/plugins/Autoresponder/process.php?key=your_key
 * 
 * See the admin menu item for the exact url with your unique key
 */
 
require_once(dirname(__FILE__) . '/Util.php');
require_once(dirname(__FILE__) . '/Model.php');
require_once(dirname(__FILE__) . '/Controller.php');

$controller = new Autoresponder_Controller();

if (!($result = $controller->process())) {
    print "Could not process Autoresponders--invalid key?";
}
echo $result;
?>