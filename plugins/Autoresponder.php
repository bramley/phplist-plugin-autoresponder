<?php
/**
 * Autoresponder plugin for phplist.
 *
 * This file is a part of Autoresponder Plugin.
 *
 * @category  phplist
 *
 * @author    Duncan Cameron, Cameron Lerch (Sponsored by Brightflock -- http://brightflock.com)
 * @copyright 2013-2018 Duncan Cameron
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 */
use chdemko\BitArray\BitArray;
use phpList\plugin\Common\PageLink;
use phpList\plugin\Common\PageURL;
use Iterator;

class Autoresponder extends phplistPlugin
{
    const VERSION_FILE = 'version.txt';
    const VERSION3_DATE = '2018-02-12';

    public $name = 'Autoresponder';
    public $enabled = true;
    public $authors = 'Cameron Lerch, Duncan Cameron';
    public $description = 'Provides an autoresponder';
    public $topMenuLinks = array(
        'manage' => array('category' => 'campaigns'),
    );
    public $documentationUrl = 'https://resources.phplist.com/plugin/autoresponder';
    public $coderoot;

    private $selectedSubscribers = array();
    private $error_level;

    public function __construct()
    {
        $this->error_level = E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT;
        $this->coderoot = __DIR__ . '/' . __CLASS__ . '/';

        parent::__construct();
        $this->version = (is_file($f = $this->coderoot . self::VERSION_FILE))
            ? file_get_contents($f)
            : '';
    }

    public function activate()
    {
        $this->pageTitles = array(
            'manage' => s('Manage autoresponders'),
        );
    }

    public function adminmenu()
    {
        return $this->pageTitles;
    }

    public function dependencyCheck()
    {
        global $plugins;

        return array(
            'Common plugin v3.7.0 or later installed' => (
                phpListPlugin::isEnabled('CommonPlugin')
                &&
                version_compare($plugins['CommonPlugin']->version, '3.7.0') >= 0
            ),
            'PHP version 5.4.0 or greater' => version_compare(PHP_VERSION, '5.4') > 0,
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
     * Use this method as a hook to create the dao.
     * Need to create autoloader because of the unpredictable order in which plugins are called.
     */
    public function sendFormats()
    {
        global $plugins;

        require_once $plugins['CommonPlugin']->coderoot . 'Autoloader.php';

        $depends = include $this->coderoot . 'depends.php';
        $container = new phpList\plugin\Common\Container($depends);
        $this->dao = $container->get('DAO');
        $this->logger = $container->get('Logger');

        return;
    }

    /**
     * Hook for when process queue is run.
     * Submits any autoresponder campaigns that are pending and stores the subscribers to be sent.
     *
     * @return none
     */
    public function processQueueStart()
    {
        global $plugins;

        $level = error_reporting($this->error_level);

        foreach ($this->dao->getAutoresponders() as $ar) {
            if (!$ar['enabled']) {
                continue;
            }
            $subscribers = $this->dao->pendingSubscribers($ar['id']);
            $messageId = $ar['mid'];
            $this->logger->debug(sprintf('%d %d', $messageId, count($subscribers)));

            if (count($subscribers) == 0) {
                continue;
            }
            $this->logger->debug("Campaign $messageId submitted");
            $submitted = $this->dao->submitCampaign($messageId);

            if ($submitted) {
                foreach ($plugins as $plugin) {
                    $plugin->messageReQueued($messageId);
                }
            }
            $this->selectedSubscribers[$messageId] = $this->loadSubscribers($subscribers);
        }
        error_reporting($level);
    }

    /**
     * Determine whether the campaign should be sent to a specific user.
     *
     * @param array $messageData the message data
     * @param array $userData    the user data
     *
     * @return bool
     */
    public function canSend($messageData, $userData)
    {
        $mid = $messageData['id'];

        return isset($this->selectedSubscribers[$mid])
            ? (bool) $this->selectedSubscribers[$mid][(int) $userData['id']]
            : true;
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

        if (!($ar = $this->dao->getAutoresponderForMessage($messageId))) {
            return;
        }
        $listId = $ar['addlistid'];
        $autoId = $ar['id'];

        if ($listId == 0) {
            return;
        }

        if ($this->dao->addSubscriberToList($listId, $userdata['id'])) {
            addUserHistory(
                $userdata['email'],
                s('Added to list automatically'),
                s('Added to list %d by autoresponder %d, message %d', $listId, $autoId, $messageId)
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
        if (!($ar = $this->dao->getAutoresponderForMessage($messageId))) {
            return false;
        }
        $description = htmlspecialchars($ar['description']);
        $link = new PageLink(
            new PageURL('manage', array('pi' => 'Autoresponder')),
            "Autoresponder {$ar['id']}"
        );
        $html = <<<END
    $link
    <br />
    $description
END;

        return array('Autoresponder', $html);
    }

    /**
     * Use this hook to delete the 'not sent' rows from the usermessage table
     * so that they will be re-evaluated.
     *
     * @param int $id the message id
     *
     * @return none
     */
    public function messageQueued($id)
    {
        $this->dao->deleteNotSent($id);
    }

    /**
     * The same processing as when queueing a message.
     *
     * @param int $id the message id
     *
     * @return none
     */
    public function messageReQueued($id)
    {
        $this->messageQueued($id);
    }

    /**
     * This hook is called just after a plugin has been upgraded.
     * If the plugin has been upgraded from 2.x then the message table needs to be upgraded.
     *
     * @param string $previousDate the date of installation of the previous plugin version
     *
     * @return bool always return true
     */
    public function upgrade($previousDate)
    {
        if ($previousDate < self::VERSION3_DATE) {
            $this->dao->upgradeMessageTable();
        }

        return true;
    }

    /**
     * Load subscribers into a BitArray.
     *
     * @param Iterator $subscribers
     *
     * @return BitArray
     */
    private function loadSubscribers(Iterator $subscribers)
    {
        $highest = $this->dao->highestSubscriberId();
        $subscriberArray = BitArray::fromInteger($highest + 1);

        foreach ($subscribers as $subscriber) {
            $subscriberArray[(int) $subscriber['id']] = 1;
        }

        return $subscriberArray;
    }
}
