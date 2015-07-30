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
class Autoresponder_Controller
{
    private $model;
    private $root;
    private $base_url;

    private function minutes($delay)
    {
        $now = time();
        return (strtotime($delay, $now) - $now) / 60;
    }

    public function __construct()
    {
        $this->model = new Autoresponder_Model();
        $this->root = AutoResponder_Util::pluginRoot('Autoresponder');
    }

    public function addRequest()
    {
        $errors = array();

        if (empty($_GET['mid'])) {
            $errors[] = 'A message must be selected';
        }

        if (!empty($_GET['delay'])) {
            $delay = trim($_GET['delay']);

            if (preg_match('/^\d+\s+(minute|hour|day|week|year)s?$/', $delay)) {
                $delayMinutes = $this->minutes($delay);
            } else {
                $errors[] = "Invalid delay value '$delay'";
            }
        } elseif (!empty($_GET['mins'])) {
            $delayMinutes = $_GET['mins'];
        } else {
            $errors[] = 'Select or enter delay value';
        }

        if ($errors) {
            return $errors;
        }
        $addListId = $_GET['addlist'] ? $_GET['addlist'] : 0;
        return $this->model->addAutoresponder($_GET['mid'], $delayMinutes, $addListId, empty($_GET['new']) ? 0 : 1)
            ? true : array('Was unable to add autoresponder');
    }

    public function deleteRequest()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;

        if (!$id) {
            return false;
        }

        return $this->model->deleteAutoresponder($id);
    }

    public function toggleEnabledRequest()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;

        if (!$id) {
            return false;
        }

        return $this->model->toggleEnabled($id);
    }

    public function process()
    {
        global $plugins;

        $this->model->setLastProcess();

        $messageIds = $this->model->setPending();

        foreach ($messageIds as $mid) {
            foreach ($plugins as $plugin) {
                $plugin->messageReQueued($mid);
            }
        }
        return count($messageIds);
    }

    public function adminView($params)
    {
        $vars = array(
            'params' => $params,
            'current' => $this->model->getAutoresponders(),
            'possible' => $this->model->getPossibleMessages(),
            'lists' => CHtml::dropDownList(
                'addlist',
                '',
                $this->model->getListNames(),
                array('prompt' => 'Select ...')
            ),
            'last_process' => $this->model->getLastProcess(),
            'process' => AutoResponder_Util::pluginURL('process', array('pi' => 'Autoresponder'))
        );

        return $this->view('admin', $vars);
    }

    private function view($name, $vars = array())
    {
        if (!is_file($this->root . $name . '.tpl.php')) {
            return null;
        }

        ob_start();
        extract($vars);
        require($this->root . $name . '.tpl.php');
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }
}
