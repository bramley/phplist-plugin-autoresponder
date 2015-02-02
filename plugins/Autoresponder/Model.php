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
class Autoresponder_Model {
    private static $TABLE = 'autoresponders';

    public function __construct() {
        self::init();
    }

    public function getPossibleMessages() {
        global $table_prefix;
        global $tables;

        $res = Sql_Query("SELECT GROUP_CONCAT(DISTINCT lm.listid SEPARATOR ',') AS list_ids, m.id, m.subject FROM " . $tables['message'] . " m " .
            "INNER JOIN " . $tables['listmessage'] . " lm ON m.id = lm.messageid " .
            "LEFT JOIN " . $table_prefix . self::$TABLE . " ar ON m.id = ar.mid " .
            "WHERE status = 'draft' AND ar.id IS NULL AND lm.listid != 0 " .
            "GROUP BY m.id");

        $messages = array();
        $listNames = $this->getListNames();

        while (($row = Sql_Fetch_Array($res))) {
            $row['list_ids'] = explode(',', $row['list_ids']);
            $row['list_names'] = array();

            foreach ($row['list_ids'] as $id) {
                $row['list_names'][$id] = isset($listNames[$id]) ? $listNames[$id] : 'Unknown';
            }

            $messages[$row['id']] = $row;
        }

        return $messages;
    }

    public function toggleEnabled($id) {
        global $table_prefix;

        Sql_Query(
            sprintf("UPDATE " . $table_prefix . self::$TABLE . " SET enabled = !enabled WHERE id = %d", $id));

        return true;
    }

    public function setLastProcess() {
        global $tables;

        Sql_Query("REPLACE INTO " . $tables['config'] . " (item, value, editable) values('autoresponder_last_process', now(), 0)");
    }

    public function getLastProcess() {
        return getConfig('autoresponder_last_process');
    }

    public function setPending() {
        global $table_prefix;
        global $tables;

        $ars = $this->getAutoresponders();
        $messagesSubmitted = array();

        foreach ($ars as $ar) {
            if (!$ar['enabled']) {
                continue;
            }

            $attribute = $this->getAttribute($ar['id']);

            $qs = array();
            $table = $table_prefix . self::$TABLE;

            foreach (array('COUNT(*)', $attribute['id'] . ' AS attributeid, lu.userid AS userid, now() AS value') as $select) {
                $q = "
                    SELECT $select
                    FROM $table ar
                        INNER JOIN {$tables['message']} m ON ar.mid = m.id
                        INNER JOIN {$tables['listmessage']} lm ON m.id = lm.messageid
                        INNER JOIN {$tables['listuser']} lu ON lm.listid = lu.listid
                        INNER JOIN {$tables['user']} u ON u.id = lu.userid AND u.confirmed = 1 AND u.blacklisted = 0
                        LEFT JOIN {$tables['usermessage']} um ON lu.userid = um.userid AND um.messageid = m.id
                        WHERE ar.id = {$ar['id']}";

                if ($ar['new']) {
                    $q .= " AND lu.modified > ar.entered";
                }

                $q .= " AND (UNIX_TIMESTAMP(lu.modified) + (ar.mins * 60)) < UNIX_TIMESTAMP(now())
                      AND um.userid IS NULL GROUP BY lu.userid";

                $qs[] = $q;
            }

            $res = Sql_Query($qs[0]);
            $row = Sql_Fetch_Row($res);

            try {
                Sql_Query('BEGIN');

                if ($row[0]) {
                    Sql_Query("REPLACE INTO " . $tables['user_attribute'] . " " . $qs[1]);

                    Sql_Query(
                        sprintf("UPDATE " . $tables['message'] . " SET status = 'submitted' WHERE (status = 'sent' OR status = 'draft') AND id = %d", $ar['mid']));
                    $messagesSubmitted[] = $ar['mid'];
                }
                else {
                    Sql_Query(
                        sprintf("UPDATE " . $tables['message'] . " SET status = 'draft' WHERE status = 'sent' AND id = %d", $ar['mid']));
                }

                Sql_Query('COMMIT');
            }
            catch (Exception $e) {
                Sql_Query('ROLLBACK');
                return false;
            }
        }

        return count($messagesSubmitted);
    }

    public function getListNames() {
        static $names = null;

        if ($names === null) {
            $names = array();

            global $tables;
            $res = Sql_Query("SELECT id, name FROM " . $tables['list']);

            while (($row = Sql_Fetch_Array($res))) {
                $names[$row['id']] = $row['name'];
            }
        }

        return $names;
    }

    public function getAutoresponders() {
        static $responders = null;

        if ($responders === null) {
            $responders = array();

            global $tables;
            global $table_prefix;

            $table = $table_prefix . self::$TABLE;
            $res = Sql_Query("
                SELECT ar.*, m.subject, GROUP_CONCAT(DISTINCT lm.listid SEPARATOR ',') AS list_ids
                FROM $table ar
                INNER JOIN {$tables['message']} m ON ar.mid = m.id
                INNER JOIN {$tables['listmessage']} lm ON m.id = lm.messageid
                WHERE lm.listid != 0
                GROUP BY ar.id
            ");

            $listNames = $this->getListNames();

            while (($row = Sql_Fetch_Array($res))) {
                $row['list_ids'] = explode(',', $row['list_ids']);
                $row['list_names'] = array();

                foreach ($row['list_ids'] as $id) {
                    $row['list_names'][$id] = isset($listNames[$id]) ? $listNames[$id] : 'Unknown';
                }

                $responders[$row['id']] = $row;
            }

            uasort($responders, 'autoresponder_sort');
        }

        return $responders;
    }

    public function addAutoresponder($mid, $mins, $new = 1) {
        global $tables;
        global $table_prefix;

        try {
            $table = $table_prefix . self::$TABLE;

            Sql_Query('BEGIN');

            Sql_Query(
                sprintf(
                    "INSERT INTO `$table`
                    (enabled, mid, mins, new, entered)
                    VALUES (1, %d, %d, %d, now())", $mid, $mins, $new
                )
            );

            $res = Sql_Query(
                sprintf("SELECT id FROM " . $table_prefix . self::$TABLE . " WHERE mid = %d", $mid));

            $row = Sql_Fetch_Array($res);

            Sql_Query("INSERT INTO " . $tables['attribute'] . " (name, type, listorder, default_value, required, tablename) VALUES (" .
                "'autoresponder_" . $row['id'] . "', 'hidden', 0, '', 0, 'autoresponder_" . $row['id'] . "')");

            $attribute = $this->getAttribute($row['id']);

            $selectionQuery = sprintf("SELECT ua.userid FROM " . $tables['user_attribute'] . " ua " .
                "LEFT JOIN " . $tables['usermessage'] . " um ON ua.userid = um.userid AND um.messageid = %d " .
                "WHERE ua.attributeid = " . $attribute['id'] . " AND ua.value != \"\" AND ua.value IS NOT NULL AND um.userid IS NULL", $mid);

            Sql_Query(
                sprintf("UPDATE " . $tables['message'] . " SET userselection = '%s' WHERE id = %d", $selectionQuery, $mid));

            Sql_Query('COMMIT');
        }
        catch (Exception $e) {
            Sql_Query('ROLLBACK');
            return false;
        }

        return true;
    }

    public function deleteAutoresponder($id) {
        global $tables;
        global $table_prefix;

        $attribute = $this->getAttribute($id);

        try {
            Sql_Query('BEGIN');

            $res = Sql_Query(
                sprintf("SELECT mid FROM " . $table_prefix . self::$TABLE . " WHERE id = %d", $id));

            $row = Sql_Fetch_Array($res);

            if ($row && isset($row['mid'])) {
                Sql_Query(
                    sprintf("UPDATE " . $tables['message'] . " SET status = 'draft', userselection = NULL WHERE id = %d", $row['mid']));

                Sql_Query(
                    sprintf("DELETE FROM " . $tables['usermessage'] . " WHERE messageid = %d", $row['mid']));
            }

            Sql_query(
                sprintf("DELETE FROM `" . $table_prefix . self::$TABLE . "` WHERE id = %d", $id));

            if ($attribute) {
                Sql_query(
                    sprintf("DELETE FROM " . $tables['attribute'] . " WHERE id = %d", $attribute['id']));

                Sql_query(
                    sprintf("DELETE FROM " . $tables['user_attribute'] . " WHERE attributeid = %d", $attribute['id']));
            }

            Sql_Query('COMMIT');
        }
        catch (Exception $e) {
            Sql_Query('ROLLBACK');
            return false;
        }

        return true;
    }

    public function getAttribute($id) {
        global $tables;

        $res = Sql_Query("SELECT * FROM " . $tables['attribute'] . " WHERE name = 'autoresponder_" . $id . "'");

        return Sql_Fetch_Array($res);
    }

    private static function init() {
        global $tables;
        global $table_prefix;

        $table = $table_prefix . self::$TABLE;

        if (!Sql_Table_exists($table)) {
            $r = Sql_Query(
                "CREATE TABLE $table (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    enabled BOOL NOT NULL,
                    mid INT(11) NOT NULL,
                    mins INT(11) NOT NULL,
                    new BOOL NOT NULL,
                    entered DATETIME NOT NULL,
                    PRIMARY KEY(id)
                )"
            );
        }
    }
}

function autoresponder_sort($a, $b) {
    $aname = reset($a['list_names']);
    $bname = reset($b['list_names']);

    if ($aname < $bname) {
        return -1;
    }

    if ($aname > $bname) {
        return 1;
    }

    if ($a['mins'] < $b['mins']) {
        return -1;
    }

    if ($a['mins'] > $b['mins']) {
        return 1;
    }

    return 0;
}
