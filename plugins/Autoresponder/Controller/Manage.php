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
class Autoresponder_Controller_Manage extends CommonPlugin_Controller
{
    private $model;

    /*
     *    Private methods
     */
    private function isValidDelay($delay)
    {
        return preg_match('/^\d+\s+(minute|hour|day|week|year)s?$/', $delay);
    }

    private function delayInMinutes($delay)
    {
        $now = time();
        return (strtotime($delay, $now) - $now) / 60;
    }

    /**
     * Creates array for use in select element
     * Includes an optional specific message, for use when editing an autoresponder.
     * 
     * @param int $mid additional message to include in the results
     * @return array select list data, message id => description
     * @access public
     */
    private function messageListData($mid)
    {
        return array_map(
            function ($item) {
                return $item['subject'] . ' (' . implode(',', $item['list_names']) . ')';
            },
            $this->model->getPossibleMessages($mid)
        );
    }

    private function delayData()
    {
        $data = array();

        for ($i = 5; $i < 60; $i += 5) {
            $data[$i] = Autoresponder_Util::formatMinutes($i);
        }

        for ($i = 1; $i < 24; $i++) {
            $data[60 * $i] = Autoresponder_Util::formatMinutes(60 * $i);
        }

        for ($i = 1; $i < 7; $i++) {
            $data[1440 * $i] = Autoresponder_Util::formatMinutes(1440 * $i);
        }

        for ($i = 1; $i < 52; $i++) {
            $data[10080 * $i] = Autoresponder_Util::formatMinutes(10080 * $i);
        }

        for ($i = 1; $i < 6; $i++) {
            $data[524160 * $i] = Autoresponder_Util::formatMinutes(524160 * $i);
        }
        return $data;
    }

    private function displayAutoresponders(array $params = array())
    {
        $listing = new CommonPlugin_Listing(
            $this,
            new Autoresponder_Populator($this->model->getAutoresponders())
        );

        if (isset($_SESSION['autoresponder_errors'])) {
            $errors = $_SESSION['autoresponder_errors'];
            unset($_SESSION['autoresponder_errors']);
        } else {
            if (isset($params['errorMessages'])) {
                $errors = $params['errorMessages'];
            } else {
                $errors = array();
            }
        }
        $vars = array(
            'errorMessages' => $errors,
            'listing' => $listing->display()
        );

        return $this->render(__DIR__ . '/../listingview.tpl.php', $vars);
    }

    /**
     * Displays the autoresponder form for adding and amending an autoresponder
     * When adding there are no default values.
     * When editing the existing autoresponder values are used, and the message id cannot
     * be changed.
     * 
     * @param array $params Values for an existing autoresponder
     * @param boolean $disable Whether to disable the message select list, when editing
     * an autoresponder
     * 
     * @return string The generated HTML for the form
     * @access public
     */
    private function displayform($params, $disable = false)
    {
        $options = array('prompt' => 'Select ...');

        if ($disable) {
            $options['disabled'] = 'disabled';
        }
        $mid = isset($params['mid']) ? $params['mid'] : 0;
        $messages = CHtml::dropDownList('mid', $mid, $this->messageListData($mid), $options);
        $delayData = $this->delayData();

        if (isset($params['mins'])) {
            if (isset($delayData[$params['mins']])) {
                $minsSelected = $params['mins'];
                $delay = '';
            } else {
                $minsSelected = 0;
                $delay = Autoresponder_Util::formatMinutes($params['mins']);
            }
        } else {
            $minsSelected = 0;
            $delay = '';
        }
            
        $mins = CHtml::dropDownList('mins', $minsSelected, $delayData, array('prompt' => 'Select ...' ));
        $delay = CHtml::textField('delay', $delay);
        $lists = CHtml::dropDownList(
            'addlist',
            isset($params['addlistid']) ? $params['addlistid'] : 0,
            $this->model->getListNames(),
            array('prompt' => 'Select ...' )
        );
        $newOnly = CHtml::checkbox('new', isset($params['new']) ? $params['new'] : 1);
        $submit = CHtml::submitButton($params['title'], array('name' => 'submit'));
        $cancel = new CommonPlugin_PageLink(new CommonPlugin_PageURL(null), 'Cancel', array('class' => 'button'));

        if (isset($_SESSION['autoresponder_errors'])) {
            $errors = $_SESSION['autoresponder_errors'];
            unset($_SESSION['autoresponder_errors']);
        } else {
            $errors = array();
        }

        $vars = array(
            'messages' => $messages,
            'mins' => $mins,
            'delay' => $delay,
            'lists' => $lists,
            'newOnly' => $newOnly,
            'submit' => $submit,
            'cancel' => $cancel,
            'title' => $params['title'],
            'errorMessages' => $errors,
        );
        return $this->render(__DIR__ . '/../formview.tpl.php', $vars);
    }

    /*
     *    Protected methods
     */
    protected function actionDefault()
    {
        echo $this->displayAutoresponders();
    }

    protected function actionAdd()
    {
        // accessed using GET
        if (!isset($_POST['submit'])) {
            echo $this->displayform(array('title' => 'Add Autoresponder'));
            return;
        }

        // accessed using POST
        $errors = array();

        if (empty($_POST['mid'])) {
            $errors[] = 'A message must be selected';
        }

        if (!empty($_POST['delay'])) {
            $delay = trim($_POST['delay']);

            if ($this->isValidDelay($delay)) {
                $delayMinutes = $this->delayInMinutes($delay);
            } else {
                $errors[] = "Invalid delay value '$delay'";
            }
        } elseif (!empty($_POST['mins'])) {
            $delayMinutes = $_POST['mins'];
        } else {
            $errors[] = 'Please select or enter delay value';
        }

        if (!$errors) {
            $addListId = $_POST['addlist'] ? $_POST['addlist'] : 0;

            if (!$this->model->addAutoresponder($_POST['mid'], $delayMinutes, $addListId, empty($_POST['new']) ? 0 : 1)) {
                $errors[] = 'Was unable to add autoresponder';
            }
        }

        if ($errors) {
            header('Location: ' . new CommonPlugin_PageURL(null, array('action' => 'add')));
        } else {
            $errors[] = 'Autoresponder added';
            header('Location: ' . new CommonPlugin_PageURL(null));
        }
        $_SESSION['autoresponder_errors'] = $errors;
        exit;
    }

    protected function actionEdit()
    {
        // accessed using GET
        if (!isset($_POST['submit'])) {
            $ar = $this->model->autoresponder($_GET['id']);
            echo $this->displayform(array('title' => 'Amend Autoresponder') + $ar, true);
            return;
        }

        // accessed using POST
        $errors = array();

        if (!empty($_POST['delay'])) {
            $delay = trim($_POST['delay']);

            if ($this->isValidDelay($delay)) {
                $delayMinutes = $this->delayInMinutes($delay);
            } else {
                $errors[] = "Invalid delay value '$delay'";
            }
        } elseif (!empty($_POST['mins'])) {
            $delayMinutes = $_POST['mins'];
        } else {
            $errors[] = 'Please select or enter delay value';
        }

        if (!$errors) {
            $addListId = $_POST['addlist'] ? $_POST['addlist'] : 0;

            if (!$this->model->updateAutoresponder(
                $_GET['id'],
                $delayMinutes,
                $addListId,
                empty($_POST['new']) ? 0 : 1)
            ) {
                $errors[] = 'Was unable to update autoresponder';
            }
        }

        if ($errors) {
            header('Location: ' . new CommonPlugin_PageURL(null, array('action' => 'edit', 'id' => $_GET['id'])));
        } else {
            $errors[] = "Autoresponder {$_GET['id']} amended";
            header('Location: ' . new CommonPlugin_PageURL(null));
        }
        $_SESSION['autoresponder_errors'] = $errors;
        exit;
    }

    protected function actionDelete()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;

        if ($id) {
            if ($this->model->deleteAutoresponder($id)) {
                Autoresponder_Util::pluginRedirect();
            }
            $error = 'Was unable to delete autoresponder';
        } else {
            $error = 'A message id must be specified';
        }
        echo $this->displayAutoresponders(array('errorMessages' => array($error)));
    }

    protected function actionEnable()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;

        if ($id) {
            if ($this->model->toggleEnabled($id)) {
                Autoresponder_Util::pluginRedirect();
            }
            $error = 'Was unable to enable/disable autoresponder';
        } else {
            $error = 'A message id must be specified';
        }
        echo $this->displayAutoresponders(array('errorMessages' => array($error)));
    }

    /*
     *    Public methods
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = new Autoresponder_Model();
    }
}
