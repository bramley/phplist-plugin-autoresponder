<?php
/**
 * Autoresponder plugin for phplist.
 *
 * This file is a part of Autoresponder Plugin.
 *
 * @category  phplist
 *
 * @author    Duncan Cameron
 * @copyright 2013-2018 Duncan Cameron
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 */
global $pagefooter;

$pagefooter[basename(__FILE__)] = <<<'END'
<script type="text/javascript">
$(function() {
    $('.autosubmit').change(function() {
        this.form.submit();
    });
});
</script>
END;
?>
<div class="ar-admin">
    <div>
    <?php echo $toolbar; ?>
    </div>
    <?php if ($errors) { ?>
        <div class="ar-errors" style="padding-top: 10px;">
            <div class="note">
                <?php foreach ($errors as $msg) { ?>
                    <?php echo htmlspecialchars($msg); ?><br/>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
    <div style='padding-top: 10px;'>
        <div class="panel">
            <div class="header"><h2><?= s('Filter')?></h2></div>
            <div class="content">
                <div class="ar-admin-current" style="padding-top: 10px;">
                    <form method="GET" action="">
                        <input type="hidden" value=<?php echo $_GET['page']; ?>  name="page" id="page" />
                        <input type="hidden" value=<?php echo $_GET['pi']; ?>  name="pi" id="pi" />
                        <label><?= s('Filter by list')?>
        <?php echo $filter; ?></label>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="ar-admin-current" style="padding-top: 10px;">
    <?php echo $listing; ?>
    </div>
</div>
