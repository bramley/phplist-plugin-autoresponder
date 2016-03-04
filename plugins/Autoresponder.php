<?php
/**
 * Autoresponder plugin for phplist.
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
 *
 * @author    Cameron Lerch (Sponsored by Brightflock -- http://brightflock.com)
 * @copyright 2013 Cameron Lerch
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 *
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
        'manage' => array('category' => 'campaigns'),
    );
    public $pageTitles = array(
        'manage' => 'Manage autoresponders',
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
            'Common plugin v3 installed' => (
                phpListPlugin::isEnabled('CommonPlugin')
                &&
                preg_match('/\d+\.\d+\.\d+/', $plugins['CommonPlugin']->version, $matches)
                &&
                version_compare($matches[0], '3') > 0
            ),
            'PHP version 5.3.0 or greater' => version_compare(PHP_VERSION, '5.3') > 0,
        );
    }

    public function cronJobs()
    {
        return array(
            array(
                'page' => 'process',
                'frequency' => 60,
            ),
        );
    }

    /**
     * Hook for when process queue is run
     * Submits any autoresponder campaigns that are pending.
     *
     * @return none
     */
    public function processQueueStart()
    {
        global $plugins;

        $level = error_reporting(-1);

        $dao = new Autoresponder_DAO();
        $messageIds = $dao->setPending();

        foreach ($messageIds as $mid) {
            foreach ($plugins as $plugin) {
                $plugin->messageReQueued($mid);
            }
        }
        error_reporting($level);
    }

    /**
     * Hook for when a message has been sent to a user
     * If the message is an autoresponder and a list has been specified then
     * add the user to that list.
     *
     * @param int   $messageId  the message id
     * @param array $userdata   array of user data
     * @param bool  $isTestMail whether sending a test email
     *
     * @return none
     */
    public function processSendSuccess($messageId, $userdata, $isTestMail = false)
    {
        if ($isTestMail) {
            return;
        }
        $dao = new Autoresponder_DAO();

        if (!($ar = $dao->getAutoresponderForMessage($messageId))) {
            return;
        }

        $listId = $ar['addlistid'];
        $autoId = $ar['id'];

        if ($listId == 0) {
            return;
        }

        if ($dao->addSubscriberToList($listId, $userdata['id'])) {
            addUserHistory(
                $userdata['email'],
                'Added to list automatically',
                "Added to list $listId by autoresponder $autoId, message $messageId"
            );
        }
    }

    /**
     * Hook for displaying fields when a message is viewed
     * If the message has an autoresponder then display a link to the autoresponder page.
     *
     * @param int   $messageId the message id
     * @param array $userdata  array of user data
     *
     * @return array|false caption and html to be added, or false if the
     *                     message does not have an autoresponder
     */
    public function viewMessage($messageId, array $data)
    {
        $dao = new Autoresponder_DAO();

        if (!($ar = $dao->getAutoresponderForMessage($messageId))) {
            return false;
        }
        $description = htmlspecialchars($ar['description']);
        $link = new CommonPlugin_PageLink(
            new CommonPlugin_PageURL('manage', array('pi' => 'Autoresponder')),
            "Autoresponder {$ar['id']}"
        );
        $html = <<<END
    $link
    <br />
    $description
END;

        return array('Autoresponder', $html);
    }
}
