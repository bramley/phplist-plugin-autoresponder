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
                <h4><?php echo $title; ?></h4>
            </div>
            <div class="content">
                <form id="ar-admin-new-form" method="post">
                        <div>
                            <label for="description"><?= s('Description')?></label>
                            <?php echo $description; ?>
                        </div>
                        <div>
                        <label for="mid"><?= s('Select the draft message to send')?></label>
                        <?php echo $messages; ?>
                        <p><?= s('Create further available messages by adding draft messages to phplist')?></p>
                        </div>
                        <div>
                        <label for="mins"><?= s('Select a delay')?></label>
                        <p><?= s('After a subscription, how long to wait until sending the autoresponder email to the subscriber')?></p>
                        <?php echo $mins; ?>
                        <?= s('Or enter another value (e.g. 16 days)')?>
                        <?php echo $delay; ?>
                        </div>
                        <div>
                        <label for="addlist"><?= s('Actions')?></label>
                        <p><?= s('Add each subscriber to another list when the autoresponder has been sent')?></p>
                        <?php echo $lists; ?>
                        </div>
                    <fieldset>
                        <?php echo $newOnly; ?>
                        <span><?= s('Only send to new users (keep checked unless you know what you are doing!)')?></span>
                    </fieldset>
                        <?php echo $submit; ?>
                        <?php echo $cancel; ?>
                </form>
            </div>
        </div>
    </div>
</div>
