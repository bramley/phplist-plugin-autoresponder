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
<?php
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
<form method="GET" action="">
    <input type="hidden" value=<?php echo $_GET['page']; ?>  name="page" id="page" />
    <input type="hidden" value=<?php echo $_GET['pi']; ?>  name="pi" id="pi" />
    <label>Filter by list
    <?php echo $filter; ?></label>
</form>
