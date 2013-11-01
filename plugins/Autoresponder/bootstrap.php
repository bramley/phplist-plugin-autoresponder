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

// We need to find the phplist web root
// XXX-CL we assume a relative plugin dir--does anyone really move their plugin dir?
while (!is_file('admin/init.php')) {
    if (!chdir('..')) {
        die('Cannot find phplist root');
    }
}

// XXX-CL Here's some ugly crap--mirror index.php's bootstrap. Unfortunately phplist has a poor plugin model and this is the
// least-hackish way I could find to make it work without patching phplist itself

// Start index.php-like bootstrap
require_once 'admin/commonlib/lib/unregister_globals.php';
require_once 'admin/commonlib/lib/magic_quotes.php';
require_once 'admin/init.php';

## none of our parameters can contain html for now
$_GET = removeXss($_GET);
$_POST = removeXss($_POST);
$_REQUEST = removeXss($_REQUEST);
$_SERVER = removeXss($_SERVER);

if (isset($_SERVER["ConfigFile"]) && is_file($_SERVER["ConfigFile"])) {
  include $_SERVER["ConfigFile"];
} elseif (isset($_ENV["CONFIG"]) && is_file($_ENV["CONFIG"])) {
  include $_ENV["CONFIG"];
} elseif (is_file("config/config.php")) {
  include "config/config.php";
} else {
  print "Error, cannot find config file\n";
  exit;
}

require_once 'admin/'.$GLOBALS["database_module"];

# load default english and language
require_once "texts/english.inc";
include_once "texts/".$GLOBALS["language_module"];

# Allow customisation per installation
if (is_file($GLOBALS["language_module"])) {
    include_once $GLOBALS["language_module"];
}

require_once "admin/defaultconfig.inc";
require_once 'admin/connect.php';
include_once "admin/languages.php";
include_once "admin/lib.php";

// End index.php-like bootstrap
?>