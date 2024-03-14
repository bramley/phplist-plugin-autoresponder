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

namespace phpList\plugin\Autoresponder\Controller;

use CHtml;
use phpList\plugin\Autoresponder\DAO;
use phpList\plugin\Autoresponder\Populator;
use phpList\plugin\Autoresponder\Util;
use phpList\plugin\Common\Controller;
use phpList\plugin\Common\DAO\Lists as ListDao;
use phpList\plugin\Common\Listing;
use phpList\plugin\Common\PageLink;
use phpList\plugin\Common\PageURL;
use phpList\plugin\Common\Toolbar;

class Manage extends Controller
{
    private $dao;

    /**
     * Redirect to a specific page setting the session errors.
     *
     * @param string $page     target of redirect
     * @param array  $messages success or error messages to be saved in the session
     */
    private function redirect($page, $messages = [])
    {
        $location = $page ? $page : new PageURL();
        $_SESSION['autoresponder_errors'] = $messages;
        header('Location: ' . $location);

        exit;
    }

    private function isValidDelay($delay)
    {
        return preg_match('/^(\d+)\s+(minute|hour|day|week|year)s?$/', $delay, $matches)
            ? ['size' => $matches[1], 'unit' => $matches[2]]
            : false;
    }

    private function delayInMinutes($delayPeriod)
    {
        $size = $delayPeriod['size'];

        switch ($delayPeriod['unit']) {
            case 'minute':
                return $size;
            case 'hour':
                return $size * 60;
            case 'day':
                return $size * 60 * 24;
            case 'week':
                return $size * 60 * 24 * 7;
            case 'year':
                return $size * 60 * 24 * 7 * 52;
        }
    }

    /**
     * Creates array for use in select element
     * Includes an optional specific message, for use when editing an autoresponder.
     *
     * @param int $mid additional message to include in the results
     *
     * @return array select list data, message id => description
     */
    private function messageListData($mid)
    {
        $listData = [];

        foreach ($this->dao->getPossibleMessages($mid) as $row) {
            $listData[$row['id']] = $row['subject'] . ' (' . $row['list_names'] . ')';
        }

        return $listData;
    }

    private function delayData()
    {
        $data = array();

        for ($i = 5; $i < 60; $i += 5) {
            $data[$i] = Util::formatMinutes($i);
        }

        for ($i = 1; $i < 24; ++$i) {
            $data[60 * $i] = Util::formatMinutes(60 * $i);
        }

        for ($i = 1; $i < 7; ++$i) {
            $data[1440 * $i] = Util::formatMinutes(1440 * $i);
        }

        for ($i = 1; $i < 52; ++$i) {
            $data[10080 * $i] = Util::formatMinutes(10080 * $i);
        }

        for ($i = 1; $i < 6; ++$i) {
            $data[524160 * $i] = Util::formatMinutes(524160 * $i);
        }

        return $data;
    }

    /**
     * Displays a filter by list and listing of autoresponders.
     *
     * @param array $params Additonal parameters, currently only error messages
     *
     * @return string The generated HTML
     */
    private function displayAutoresponders(array $params = array())
    {
        global $plugins;

        $listId = (isset($_GET['listfilter']) && ctype_digit($_GET['listfilter']))
            ? $_GET['listfilter']
            : 0;
        $listSelect = CHtml::dropDownList(
            'listfilter',
            $listId,
            array(0 => s('All')) + $this->dao->getArListNames(),
            array('class' => 'autosubmit')
        );
        $listing = new Listing($this, new Populator($this->dao, $listId));
        $listing->pager->setItemsPerPage([10, 25], 10);

        if (isset($_SESSION['autoresponder_errors'])) {
            $errors = $_SESSION['autoresponder_errors'];
            unset($_SESSION['autoresponder_errors']);
        } else {
            if (isset($params['errors'])) {
                $errors = $params['errors'];
            } else {
                $errors = array();
            }
        }
        $toolbar = new Toolbar($this);
        $toolbar->addExternalHelpButton($plugins['Autoresponder']->documentationUrl);
        $vars = array(
            'errors' => $errors,
            'filter' => $listSelect,
            'listing' => $listing->display(),
            'toolbar' => $toolbar->display(),
        );

        return $this->render(__DIR__ . '/../View/listingview.tpl.php', $vars);
    }

    /**
     * Displays the autoresponder form for adding and amending an autoresponder
     * When adding default values are provided
     * When editing the existing autoresponder values are used, and the message id cannot
     * be changed.
     *
     * @param array $params Defaults for a new ar or current values for an existing ar
     *
     * @return string The generated HTML for the form
     */
    private function displayform($params)
    {
        $delayData = $this->delayData();

        if ($params['mins'] > 0) {
            if (isset($delayData[$params['mins']])) {
                $minsSelected = $params['mins'];
                $delay = '';
            } else {
                $minsSelected = 0;
                $delay = Util::formatMinutes($params['mins']);
            }
        } else {
            $minsSelected = 0;
            $delay = '';
        }

        $listSelect = CHtml::dropDownList(
            'addlist',
            $params['addlistid'],
            array_column(iterator_to_array($this->listDao->listsForOwner(0)), 'name', 'id'),
            array('prompt' => s('Select ...'))
        );

        if (isset($_SESSION['autoresponder_errors'])) {
            $errors = $_SESSION['autoresponder_errors'];
            unset($_SESSION['autoresponder_errors']);
        } else {
            $errors = array();
        }

        $mid = $params['mid'];
        $options = array('prompt' => 'Select ...');

        $cancel = new PageLink(new PageURL(null), 'Cancel', array('class' => 'button'));
        $vars = array(
            'description' => CHtml::textField('description', $params['description']),
            'messages' => CHtml::dropDownList('mid', $mid, $this->messageListData($mid), array('prompt' => s('Select ...'))),
            'mins' => CHtml::dropDownList('mins', $minsSelected, $delayData, array('prompt' => s('Select ...'))),
            'delay' => CHtml::textField('delay', $delay),
            'lists' => $listSelect,
            'newOnly' => CHtml::checkbox('new', $params['new']),
            'submit' => CHtml::submitButton($params['title'], array('name' => 'submit')),
            'cancel' => $cancel,
            'title' => $params['title'],
            'errors' => $errors,
        );

        return $this->render(__DIR__ . '/../View/formview.tpl.php', $vars);
    }

    protected function actionDefault()
    {
        echo $this->displayAutoresponders();
    }

    protected function actionAdd()
    {
        // accessed using GET
        if (!isset($_POST['submit'])) {
            if (isset($_SESSION['autoresponder_form'])) {
                $values = $_SESSION['autoresponder_form'];
                unset($_SESSION['autoresponder_form']);
            } else {
                $values = array(
                    'description' => '',
                    'mid' => 0,
                    'mins' => 0,
                    'addlistid' => 0,
                    'new' => 1,
                );
            }
            $values['title'] = s('Add Autoresponder');
            echo $this->displayform($values);

            return;
        }

        // accessed using POST
        $errors = array();

        if (empty($_POST['mid'])) {
            $errors[] = s('A message must be selected');
        }
        $delayMinutes = 0;

        if (!empty($_POST['delay'])) {
            $delay = trim($_POST['delay']);

            if ($delayPeriod = $this->isValidDelay($delay)) {
                $delayMinutes = $this->delayInMinutes($delayPeriod);
            } else {
                $errors[] = s("Invalid delay value '%s'", $delay);
            }
        } elseif (!empty($_POST['mins'])) {
            $delayMinutes = $_POST['mins'];
        } else {
            $errors[] = s('Please select or enter delay value');
        }
        $addListId = $_POST['addlist'] ? $_POST['addlist'] : 0;
        $newOnly = empty($_POST['new']) ? 0 : 1;

        if (!$errors) {
            if (!$this->dao->addAutoresponder(
                $_POST['description'],
                $_POST['mid'],
                $delayMinutes,
                $addListId,
                $newOnly
            )) {
                $errors[] = s('Unable to add autoresponder');
            }
        }

        if ($errors) {
            $redirect = new PageURL(null, array('action' => 'add'));
            $_SESSION['autoresponder_form'] = array(
                'description' => $_POST['description'],
                'mid' => $_POST['mid'],
                'mins' => $delayMinutes,
                'addlistid' => $addListId,
                'new' => $newOnly,
            );
        } else {
            $redirect = null;
            $errors = [s('Autoresponder added')];
        }
        $this->redirect($redirect, $errors);
    }

    protected function actionEdit()
    {
        // accessed using GET
        if (!isset($_POST['submit'])) {
            if (isset($_SESSION['autoresponder_form'])) {
                $values = $_SESSION['autoresponder_form'];
                unset($_SESSION['autoresponder_form']);
            } else {
                $values = $this->dao->autoresponder($_GET['id']);
            }
            $values['title'] = s('Amend Autoresponder');
            echo $this->displayform($values);

            return;
        }

        // accessed using POST
        $errors = array();

        if (empty($_POST['mid'])) {
            $errors[] = s('A message must be selected');
        }
        $delayMinutes = 0;

        if (!empty($_POST['delay'])) {
            $delay = trim($_POST['delay']);

            if ($delayPeriod = $this->isValidDelay($delay)) {
                $delayMinutes = $this->delayInMinutes($delayPeriod);
            } else {
                $errors[] = s("Invalid delay value '%s'", $delay);
            }
        } elseif (!empty($_POST['mins'])) {
            $delayMinutes = $_POST['mins'];
        } else {
            $errors[] = s('Please select or enter delay value');
        }
        $addListId = $_POST['addlist'] ? $_POST['addlist'] : 0;
        $newOnly = empty($_POST['new']) ? 0 : 1;

        if (!$errors) {
            if (!$this->dao->updateAutoresponder(
                $_GET['id'],
                $_POST['description'],
                $delayMinutes,
                $addListId,
                $newOnly
            )) {
                $errors[] = s('Unable to update autoresponder');
            }
        }

        if ($errors) {
            $redirect = PageURL::createFromGet();
            $_SESSION['autoresponder_form'] = array(
                'description' => $_POST['description'],
                'mid' => $_POST['mid'],
                'mins' => $delayMinutes,
                'addlistid' => $addListId,
                'new' => $newOnly,
            );
        } else {
            $redirect = null;
            $errors = [s('Autoresponder %d amended', $_GET['id'])];
        }
        $this->redirect($redirect, $errors);
    }

    protected function actionDelete()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        $message = $id ?
            ($this->dao->deleteAutoresponder($id) ? s('Autoresponder %d deleted', $id) : s('Unable to delete autoresponder'))
            : s('An autoreponder id must be specified');
        $this->redirect(null, [$message]);
    }

    protected function actionReset()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        $message = $id ?
            ($this->dao->resetAutoresponder($id) ? s('Autoresponder %d reset', $id) : s('Unable to reset autoresponder'))
            : s('An autoreponder id must be specified');
        $this->redirect(null, [$message]);
    }

    protected function actionEnable()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        $message = $id ?
            ($this->dao->toggleEnabled($id) ? s('Autoresponder %d enabled/disabled', $id) : s('Unable to enable/disable autoresponder'))
            : s('An autoreponder id must be specified');
        $this->redirect(null, [$message]);
    }

    public function __construct(DAO $dao, ListDao $listDao)
    {
        parent::__construct();
        $this->dao = $dao;
        $this->listDao = $listDao;
    }
}
