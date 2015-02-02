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
class Autoresponder extends phplistPlugin
{
    const VERSION_FILE = 'version.txt';

    public $name = 'Autoresponder';
    public $enabled = true;
    public $authors = 'Cameron Lerch, Duncan Cameron';
    public $description = 'Provides an autoresponder';
    public $commandlinePluginPages = array('process');
    public $topMenuLinks = array(
        'main' => array('category' => 'campaigns'),
        'process' => array('category' => 'campaigns')
    );
    public $pageTitles = array(
        'main' => 'Manage autoresponders',
        'process' => 'Process autoresponders'
    );

    public function adminmenu()
    {
        return $this->pageTitles;
    }

    public function __construct()
    {
        $this->coderoot = dirname(__FILE__) . '/Autoresponder/';
        $this->version = (is_file($f = $this->coderoot . self::VERSION_FILE))
            ? file_get_contents($f)
            : '';
        parent::__construct();
    }
}
