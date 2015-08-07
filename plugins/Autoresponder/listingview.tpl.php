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
    <?php if ($errorMessages) { ?>
        <div class="ar-errors" style="padding-top: 10px;">
            <div class="note">
                <?php foreach ($errorMessages as $msg) { ?>
                    <?php echo $msg; ?><br/>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
    <div class="ar-admin-current" style="padding-top: 10px;">
        <div class="content">
            <?php echo $listing; ?>
        </div>
    </div>
</div>
