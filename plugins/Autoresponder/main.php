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
error_reporting(-1);
require_once(dirname(__FILE__) . '/Util.php');
require_once(dirname(__FILE__) . '/Model.php');
require_once(dirname(__FILE__) . '/Controller.php');

$errorMessages = array();

$controller = new Autoresponder_Controller();

if (isset($_GET['toggleEnabled']) && $_GET['toggleEnabled']) {
    if ($controller->toggleEnabledRequest()) {
        AutoResponder_Util::pluginRedirect();
    }

    $errorMessages[] = 'Was unable to toggle enabled status';
}
else if (isset($_GET['add']) && $_GET['add']) {
    if (($r = $controller->addRequest()) === true) {
        AutoResponder_Util::pluginRedirect();
    }

    $errorMessages[] = $r;
}
else if (isset($_GET['delete']) && $_GET['delete']) {
    if ($controller->deleteRequest()) {
        AutoResponder_Util::pluginRedirect();
    }

    $errorMessages[] = 'Was unable to delete autoresponder';
}

print $controller->adminView(array('errorMessages' => $errorMessages));
