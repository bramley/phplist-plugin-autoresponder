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
    private $dao;

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
                return $item['subject'] . ' (' . $item['list_names'] . ')';
            },
            $this->dao->getPossibleMessages($mid)
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

    /**
     * Displays a filter by list and listing of autoresponders
     * 
     * @param array $params Additonal parameters, currently only error messages
     * 
     * @return string The generated HTML
     * @access private
     */
    private function displayAutoresponders(array $params = array())
    {
        $listId = (isset($_GET['listfilter']) && ctype_digit($_GET['listfilter']))
            ? $_GET['listfilter']
            : 0;
        $listSelect = CHtml::dropDownList(
            'listfilter',
            $listId,
            array(0 => 'All') + $this->dao->getArListNames(),
            array('class' => 'autosubmit')
        );
        $listing = new CommonPlugin_Listing(
            $this,
            new Autoresponder_Populator($this->dao->getAutoresponders($listId))
        );

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
        $vars = array(
            'errors' => $errors,
            'filter' => $listSelect,
            'listing' => $listing->display()
        );
        return $this->render(__DIR__ . '/../listingview.tpl.php', $vars);
    }

    /**
     * Displays the autoresponder form for adding and amending an autoresponder
     * When adding default values are provided
     * When editing the existing autoresponder values are used, and the message id cannot
     * be changed.
     * 
     * @param array $params Defaults for a new ar or current values for an existing ar
     * @param boolean $disable Whether to disable the message select list, when editing
     * an autoresponder
     * 
     * @return string The generated HTML for the form
     * @access private
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
                $delay = Autoresponder_Util::formatMinutes($params['mins']);
            }
        } else {
            $minsSelected = 0;
            $delay = '';
        }

        $listSelect = CHtml::dropDownList(
            'addlist',
            $params['addlistid'],
            $this->dao->getListNames(),
            array('prompt' => 'Select ...' )
        );

        if (isset($_SESSION['autoresponder_errors'])) {
            $errors = $_SESSION['autoresponder_errors'];
            unset($_SESSION['autoresponder_errors']);
        } else {
            $errors = array();
        }

        $mid = $params['mid'];
        $options = array('prompt' => 'Select ...');

        $cancel = new CommonPlugin_PageLink(new CommonPlugin_PageURL(null), 'Cancel', array('class' => 'button'));
        $vars = array(
            'description' => CHtml::textField('description', $params['description']),
            'messages' => CHtml::dropDownList('mid', $mid, $this->messageListData($mid), array('prompt' => 'Select ...')),
            'mins' => CHtml::dropDownList('mins', $minsSelected, $delayData, array('prompt' => 'Select ...' )),
            'delay' => CHtml::textField('delay', $delay),
            'lists' => $listSelect,
            'newOnly' => CHtml::checkbox('new', $params['new']),
            'submit' => CHtml::submitButton($params['title'], array('name' => 'submit')),
            'cancel' => $cancel,
            'title' => $params['title'],
            'errors' => $errors,
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
            $values['title'] = 'Add Autoresponder';
            echo $this->displayform($values);
            return;
        }

        // accessed using POST
        $errors = array();

        if (empty($_POST['mid'])) {
            $errors[] = 'A message must be selected';
        }
        $delayMinutes = 0;

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
                $errors[] = 'Was unable to add autoresponder';
            }
        }

        if ($errors) {
            header('Location: ' . new CommonPlugin_PageURL(null, array('action' => 'add')));
            $_SESSION['autoresponder_form'] = array(
                'description' => $_POST['description'],
                'mid' => $_POST['mid'],
                'mins' => $delayMinutes,
                'addlistid' => $addListId,
                'new' => $newOnly
            );
        } else {
            $errors[] = 'Autoresponder added';
            header('Location: ' . new CommonPlugin_PageURL());
        }
        $_SESSION['autoresponder_errors'] = $errors;
        exit;
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
            $values['title'] = 'Amend Autoresponder';
            echo $this->displayform($values);
            return;
        }

        // accessed using POST
        $errors = array();

        if (empty($_POST['mid'])) {
            $errors[] = 'A message must be selected';
        }
        $delayMinutes = 0;

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
                $errors[] = 'Was unable to update autoresponder';
            }
        }

        if ($errors) {
            header('Location: ' . new CommonPlugin_PageURL(null, array('action' => 'edit', 'id' => $_GET['id'])));
            $_SESSION['autoresponder_form'] = array(
                'description' => $_POST['description'],
                'mid' => $_POST['mid'],
                'mins' => $delayMinutes,
                'addlistid' => $addListId,
                'new' => $newOnly
            );
        } else {
            $errors[] = "Autoresponder {$_GET['id']} amended";
            header('Location: ' . new CommonPlugin_PageURL());
        }
        $_SESSION['autoresponder_errors'] = $errors;
        exit;
    }

    protected function actionDelete()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;

        if ($id) {
            if ($this->dao->deleteAutoresponder($id)) {
                Autoresponder_Util::pluginRedirect();
            }
            $error = 'Was unable to delete autoresponder';
        } else {
            $error = 'A message id must be specified';
        }
        echo $this->displayAutoresponders(array('errors' => array($error)));
    }

    protected function actionEnable()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;

        if ($id) {
            if ($this->dao->toggleEnabled($id)) {
                Autoresponder_Util::pluginRedirect();
            }
            $error = 'Was unable to enable/disable autoresponder';
        } else {
            $error = 'A message id must be specified';
        }
        echo $this->displayAutoresponders(array('errors' => array($error)));
    }

    /*
     *    Public methods
     */
    public function __construct()
    {
        parent::__construct();
        $this->dao = new Autoresponder_DAO();
    }
}
