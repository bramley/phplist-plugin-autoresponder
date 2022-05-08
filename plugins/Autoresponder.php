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

    private $dao;
    private $error_level;
    private $selectedSubscribers = array();

    public function __construct()
    {
        $this->error_level = E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT;
        $this->coderoot = __DIR__ . '/' . __CLASS__ . '/';

        parent::__construct();
        $this->version = (is_file($f = $this->coderoot . self::VERSION_FILE))
            ? file_get_contents($f)
            : '';
    }

    /**
     * Use this method as a hook to create the dao.
     */
    public function activate()
    {
        $depends = include $this->coderoot . 'depends.php';
        $container = new phpList\plugin\Common\Container($depends);
        $this->dao = $container->get('DAO');
        $this->logger = $container->get('Logger');
        $this->pageTitles = array(
            'manage' => s('Manage autoresponders'),
        );

        parent::activate();
    }

    public function adminmenu()
    {
        return $this->pageTitles;
    }

    public function dependencyCheck()
    {
        global $plugins;

        return array(
            'Common plugin v3.18.4 or later must be enabled' => (
                phpListPlugin::isEnabled('CommonPlugin')
                &&
                version_compare($plugins['CommonPlugin']->version, '3.18.4') >= 0
            ),
            'PHP version 5.4.0 or greater' => version_compare(PHP_VERSION, '5.4') > 0,
            'phpList version 3.3.2 or later' => version_compare(VERSION, '3.3.2') >= 0,
        );
    }

    /**
     * Hook for when process queue is run.
     * Submits any autoresponder campaigns that are pending and stores the subscribers to be sent.
     * Adjusts the finish sending date to avoid phplist stopping sending the campaign.
     *
     * @return none
     */
    public function processQueueStart()
    {
        global $plugins;

        $level = error_reporting($this->error_level);

        foreach ($this->dao->getAutoresponders() as $ar) {
            $subscriberIter = $this->dao->pendingSubscribers($ar['id']);
            $subscribers = array_column(iterator_to_array($subscriberIter), 'id');
            $messageId = $ar['mid'];
            $this->logger->debug(
                sprintf('autoresponder %d campaign %d subscribers ready %d', $ar['id'], $messageId, count($subscribers))
            );

            if (count($subscribers) == 0) {
                continue;
            }
            $this->logger->debug(print_r($subscribers, true));
            $submitted = $this->dao->submitCampaign($messageId);
            $rows = $this->dao->deleteNotSent($messageId, $subscribers);
            $this->logger->debug(sprintf('rows deleted %d', $rows));

            // Adjust the finish sending date to be well into the future
            $finish = $ar['finishSending'];
            $finishTime = mktime($finish['hour'], $finish['minute'], 0, $finish['month'], $finish['day'], $finish['year']);

            if ($finishTime < time() + (24 * 60 * 60)) {
                $newFinishTime = time() + DEFAULT_MESSAGEAGE;
                $finishInfo = getdate($newFinishTime);
                $finish = array(
                    'year' => $finishInfo['year'],
                    'month' => $finishInfo['mon'],
                    'day' => $finishInfo['mday'],
                    'hour' => $finishInfo['hours'],
                    'minute' => $finishInfo['minutes'],
                );
                setMessageData($messageId, 'finishsending', $finish);
                logevent(sprintf('Campaign %d finish sending set to %s', $messageId, date('Y-m-d H:i', $newFinishTime)));
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
     * @param array $subscribers
     *
     * @return BitArray
     */
    private function loadSubscribers(array $subscribers)
    {
        $highest = $this->dao->highestSubscriberId();
        $subscriberArray = BitArray::fromInteger($highest + 1);

        foreach ($subscribers as $subscriberId) {
            $subscriberArray[(int) $subscriberId] = 1;
        }

        return $subscriberArray;
    }
}
