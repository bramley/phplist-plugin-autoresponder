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
class Autoresponder_Controller_Process extends CommonPlugin_Controller
{
    private $dao;

    public function __construct()
    {
        $this->dao = new Autoresponder_DAO();
    }

    public function actionDefault()
    {
        global $plugins;

        if ($GLOBALS['commandline']) {
            ob_end_clean();
            echo ClineSignature();
            ob_start();
        }

        $messageIds = $this->dao->setPending();

        foreach ($messageIds as $mid) {
            foreach ($plugins as $plugin) {
                $plugin->messageReQueued($mid);
            }
        }

        if (count($messageIds) > 0) {
            echo sprintf('campaigns submitted: %s', implode(', ', $messageIds));
        } else {
            echo 'no campaigns submitted';
        }

        if ($GLOBALS['commandline']) {
            ob_flush();
        }
    }
}
