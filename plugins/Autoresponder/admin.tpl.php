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
?>
<div class="ar-admin">
    <?php if ($params['errorMessages']) { ?>
        <div class="ar-errors">
            <h1>ERRORS</h1>
            <hr />
            <ul>
            <?php foreach ($params['errorMessages'] as $msg) { ?>
                <li><strong><?php print $msg; ?></strong></li>
            <?php } ?>
            </ul>
        </div>
    <?php } ?>
    <div class="ar-admin-current">
        <h1>Configured Autoresponders</h1>
        <hr />
        <?php if ($current) { ?>
            <table width="100%" cellspacing="0" cellpadding="0" border="0">
                <tbody>
                    <tr>
                        <td><div class="listinghdelement">ID</div></td>
                        <td><div class="listinghdelement">Lists</div></td>
                        <td><div class="listinghdelement">Delay</div></td>
                        <td><div class="listinghdelement">Added</div></td>
                        <td><div class="listinghdelement">Subject</div></td>
                        <td><div class="listinghdelement">New Only</div></td>
                        <td><div class="listinghdelement">Enabled</div></td>
                        <td><div class="listinghdelement">Delete</div></td>
                    </tr>
                    <?php foreach ($current as $item) { ?>
                        <tr>
                            <td class="listingelement"><?php print $item['id']; ?></td>
                            <td class="listingelement"><?php print implode(',', $item['list_names']); ?></td>
                            <td class="listingelement"><?php print Autoresponder_Util::formatMinutes($item['mins']); ?></td>
                            <td class="listingelement"><?php print $item['entered']; ?></td>
                            <td class="listingelement"><?php print $item['subject']; ?></td>
                            <td class="listingelement"><?php print $item['new'] ? 'yes' : 'no'; ?></td>
                            <td class="listingelement"><a href="<?php print Autoresponder_Util::pluginURL(null, array('toggleEnabled' => 1, 'id' => $item['id'])); ?>"><?php print ($item['enabled'] ? 'yes' : 'no'); ?></a></td>
                            <td class="listingelement"><a href="<?php print Autoresponder_Util::pluginURL(null, array('delete' => 1, 'id' => $item['id'])); ?>">delete</a></td>
                        </tr>                
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <em>None added yet--add below</em>
        <?php } ?>
    </div>
    <div class="ar-admin-process">
        <h1>Process Autoresponders</h1>
        <hr />
        <form id="ar-admin-process-form" action="<?php print $process; ?>" method="post">
            <p>Click the button below to manually call the process script. See the INSTALL.txt for instructions on how to automate in cron.</p>
            <p>Last ran: <em><?php print $last_process ? $last_process : 'never'; ?></em></p>
            <input type="submit" value="Process Autoresponders">
        </form>
    </div>
    <div class="ar-admin-new">
        <h1>Add New Autoresponder</h1>
        <hr />
        <form id="ar-admin-new-form" method="get">
            <input type="hidden" name="page" value="<?php print $_GET['page']; ?>">
            <input type="hidden" name="pi" value="<?php print $_GET['pi']; ?>">
            <input type="hidden" name="add" value="1">
            <fieldset>
                <label>Select the draft message to send:</label>
                <select name="mid" id="mid">
                    <?php foreach ($possible as $item) { ?>
                        <option value="<?php print $item['id']; ?>"><?php print $item['subject']; ?> (<?php print implode(',', $item['list_names']); ?>)</option>
                    <?php } ?>
                </select>
                <p>Create available messages by adding draft messages to phplist</p>
            </fieldset>
            <fieldset>
                <label>Enter a delay:</label>
                <select name="mins" id="mins">
                    <?php for ($i = 5; $i < 60; $i += 5) { ?>
                        <option value="<?php print $i; ?>"><?php print Autoresponder_Util::formatMinutes($i); ?></option>
                    <?php } ?>
                    <?php for ($i = 1; $i < 24; $i++) { ?>
                        <option value="<?php print 60 * $i; ?>"><?php print Autoresponder_Util::formatMinutes(60 * $i); ?></option>
                    <?php } ?>
                    <?php for ($i = 1; $i < 7; $i++) { ?>
                        <option value="<?php print 1440 * $i; ?>"><?php print Autoresponder_Util::formatMinutes(1440 * $i); ?></option>
                    <?php } ?>
                    <?php for ($i = 1; $i < 52; $i++) { ?>
                        <option value="<?php print 10080 * $i; ?>"><?php print Autoresponder_Util::formatMinutes(10080 * $i); ?></option>
                    <?php } ?>
                    <?php for ($i = 1; $i < 6; $i++) { ?>
                        <option value="<?php print 524160 * $i; ?>"><?php print Autoresponder_Util::formatMinutes(524160 * $i); ?></option>
                    <?php } ?>
                </select>
                <p>Select how long to delay before sending the message to new users added to the message lists</p>
            </fieldset>
            <fieldset>
                <input type="checkbox" name="new" checked="checked" value="new"><span>Only send to new users (keep checked unless you know what you are doing!)</span>
            </fieldset>
            <br />
            <div style="width: 100%; text-align: right">
                <a href="http://brightflock.com/phplist-autoresponder">Autoresponder plugin sponsored by Brightflock</a>
            </div>
            <input type="submit" value="Add New Autoresponder">
        </form>
    </div>
</div>