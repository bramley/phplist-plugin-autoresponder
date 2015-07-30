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
    public $documentationUrl = 'https://resources.phplist.com/plugin/autoresponder_3.x';

    public function __construct()
    {
        $this->coderoot = dirname(__FILE__) . '/Autoresponder/';
        $this->version = (is_file($f = $this->coderoot . self::VERSION_FILE))
            ? file_get_contents($f)
            : '';
        parent::__construct();
    }

    public function adminmenu()
    {
        return $this->pageTitles;
    }

    public function dependencyCheck()
    {
        global $plugins;

        return array(
            'Common plugin installed' =>
                phpListPlugin::isEnabled('CommonPlugin') && 
                (strpos($plugins['CommonPlugin']->version, 'Git') === 0 || $plugins['CommonPlugin']->version >= '2015-03-23'),
            'PHP version 5.3.0 or greater' => version_compare(PHP_VERSION, '5.3') > 0,
        );
    }

    /**
     * Hook for when a message has been sent to a user
     * If the message is an autoresponder and a list has been specified then
     * add the user to that list
     *
     * @access  public
     * @param   int  $messageId the message id
     * @param   array  $userdata array of user data
     * @param   bool  $isTestMail whether sending a test email
     * @return  none
     */
    public function processSendSuccess($messageId, $userdata, $isTestMail)
    {
        if ($isTestMail) {
            return;
        }
        $model = new Autoresponder_Model;

        if (!($ar = $model->getAutoresponderForMessage($messageId))) {
            return;
        }

        $listId = $ar['addlistid'];
        $autoId = $ar['id'];

        if ($listId == 0) {
            return;
        }
        
        if ($model->addSubscriberToList($listId, $userdata['id'])) {
            addUserHistory(
                $userdata['email'],
                "Added to list automatically",
                "Added to list $listId by autoresponder $autoId, message $messageId"
            );
        }

    }
}
