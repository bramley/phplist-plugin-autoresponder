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
    <?php if ($errors) { ?>
        <div class="ar-errors" style="padding-top: 10px;">
            <div class="note">
                <?php foreach ($errors as $msg) { ?>
                    <?php echo $msg; ?><br/>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
    <div class="ar-admin-new" style="padding-top: 10px;">
        <div class="panel" >
            <div class="header">
                <h2><?php echo $title; ?></h2>
            </div>
            <div class="content">
                <form id="ar-admin-new-form" method="post">
                        <label>Description:</label>
                        <?php echo $description; ?>
                        <label>Select the draft message to send:</label>
                        <?php echo $messages; ?>
                        <p>Create further available messages by adding draft messages to phplist</p>
                        <label>Select a delay:</label>
                        <p>Ater a subscription, how long to wait until sending the autoresponder email to the subscriber</p>
                        <?php echo $mins; ?>
                        <label>Or enter another value (e.g. 16 days):</label>
                        <?php echo $delay; ?>
                        <label>Actions:</label>
                        <p>Add each subscriber to another list when the autoresponder has been sent</p>
                        <?php echo $lists; ?>
                    <fieldset>
                        <?php echo $newOnly; ?>
                        <span>Only send to new users (keep checked unless you know what you are doing!)</span>
                    </fieldset>
                        <?php echo $submit; ?>
                        <?php echo $cancel; ?>
                </form>
            </div>
        </div>
    </div>
</div>
