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
class Autoresponder_Model
{
    private static $TABLE = 'autoresponders';

    /**
     * Create the database table
     * Upgrade the table by adding the addlistid column
     *
     * @access  private
     * @return  none
     */
    private function init()
    {
        if (!Sql_Table_exists($this->tables['autoresponders'])) {
            $r = Sql_Query(
                "CREATE TABLE {$this->tables['autoresponders']} (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    enabled BOOL NOT NULL,
                    mid INT(11) NOT NULL,
                    mins INT(11) NOT NULL,
                    addlistid INT(11) NOT NULL,
                    new BOOL NOT NULL,
                    entered DATETIME NOT NULL,
                    PRIMARY KEY(id)
                )"
            );
        }

        $r = Sql_Query("SHOW COLUMNS FROM {$this->tables['autoresponders']} LIKE 'addlistid'");

        if (!(bool)Sql_Num_Rows($r)) {
            $sql = <<<END
                ALTER TABLE {$this->tables['autoresponders']}
                ADD COLUMN addlistid INT(11) AFTER mins
END;
            Sql_Query($sql);
            logEvent('Autoresponder plugin table upgraded');
        }
    }

    private function getAttribute($id)
    {
        $res = Sql_Query(
            "SELECT * FROM {$this->tables['attribute']}
            WHERE name = 'autoresponder_$id'"
        );
        return Sql_Fetch_Array($res);
    }

    /*
     *  Public methods
     */
    public function __construct()
    {
        global $tables;
        global $table_prefix;

        $this->tables = $tables + array('autoresponders' => $table_prefix . 'autoresponders');
        $this->init();
    }

    /**
     * Gets available draft messages
     * Includes an additional specific message, for use when editing an autoresponder.
     * @param int $mid additional message to include in the results
     * @return array associative array indexed by message id
     * @access public
     */
    public function getPossibleMessages($mid)
    {
        $or = $mid ? "OR m.id = $mid" : '';
        $res = Sql_Query(
            "SELECT GROUP_CONCAT(DISTINCT lm.listid SEPARATOR ',') AS list_ids, m.id, m.subject
            FROM {$this->tables['message']} m
            INNER JOIN {$this->tables['listmessage']} lm ON m.id = lm.messageid
            LEFT JOIN {$this->tables['autoresponders']} ar ON m.id = ar.mid
            WHERE (status = 'draft' AND ar.id IS NULL AND lm.listid != 0)
            $or
            GROUP BY m.id"
        );

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

    public function toggleEnabled($id)
    {
        Sql_Query(
            "UPDATE {$this->tables['autoresponders']}
            SET enabled = !enabled
            WHERE id = $id"
        );

        return true;
    }

    public function setLastProcess()
    {
        global $tables;

        Sql_Query(
            "REPLACE INTO {$this->tables['config']}
            (item, value, editable)
            VALUES ('autoresponder_last_process', now(), 0)"
        );
    }

    public function getLastProcess()
    {
        return getConfig('autoresponder_last_process');
    }

    public function setPending()
    {
        $ars = $this->getAutoresponders();
        $messagesSubmitted = array();

        foreach ($ars as $ar) {
            if (!$ar['enabled']) {
                continue;
            }
            $attribute = $this->getAttribute($ar['id']);
            $qs = array();
        
            foreach (
                array(
                    'COUNT(*)',
                    $attribute['id'] . ' AS attributeid, lu.userid AS userid, now() AS value'
                ) as $select) {
                $q = "SELECT $select
                    FROM {$this->tables['autoresponders']} ar
                        INNER JOIN {$this->tables['message']} m ON ar.mid = m.id
                        INNER JOIN {$this->tables['listmessage']} lm ON m.id = lm.messageid
                        INNER JOIN {$this->tables['listuser']} lu ON lm.listid = lu.listid
                        INNER JOIN {$this->tables['user']} u ON u.id = lu.userid AND u.confirmed = 1 AND u.blacklisted = 0
                        LEFT JOIN {$this->tables['usermessage']} um ON lu.userid = um.userid AND um.messageid = m.id
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
                    Sql_Query("REPLACE INTO {$this->tables['user_attribute']} " . $qs[1]);

                    Sql_Query(
                        sprintf(
                            "UPDATE {$this->tables['message']}
                            SET status = 'submitted'
                            WHERE (status = 'sent' OR status = 'draft') AND id = %d",
                            $ar['mid']
                        )
                    );
                    $messagesSubmitted[] = $ar['mid'];
                }
                else {
                    Sql_Query(
                        "UPDATE {$this->tables['message']}
                        SET status = 'draft'
                        WHERE status = 'sent' AND id = {$ar['mid']}"
                    );
                }

                Sql_Query('COMMIT');
            }
            catch (Exception $e) {
                Sql_Query('ROLLBACK');
                return false;
            }
        }
        return $messagesSubmitted;
    }

    public function getListNames()
    {
        static $names = null;

        if ($names === null) {
            $names = array();

            $res = Sql_Query("SELECT id, name FROM {$this->tables['list']}");

            while (($row = Sql_Fetch_Array($res))) {
                $names[$row['id']] = $row['name'];
            }
        }
        return $names;
    }

    public function autoresponder($id)
    {
        $res = Sql_Query(
            "SELECT ar.*
            FROM {$this->tables['autoresponders']} ar
            WHERE ar.id = $id"
        );
        return Sql_Fetch_Array($res);
    }

    public function getAutoresponders()
    {
        static $responders = null;

        if ($responders === null) {
            $responders = array();
            $res = Sql_Query("
                SELECT ar.*, m.subject, GROUP_CONCAT(DISTINCT lm.listid SEPARATOR ',') AS list_ids
                FROM {$this->tables['autoresponders']} ar
                INNER JOIN {$this->tables['message']} m ON ar.mid = m.id
                INNER JOIN {$this->tables['listmessage']} lm ON m.id = lm.messageid
                WHERE lm.listid != 0
                GROUP BY ar.id
            ");

            $listNames = $this->getListNames();

            while (($row = Sql_Fetch_Array($res))) {
                // convert list ids to list names
                $row['list_ids'] = explode(',', $row['list_ids']);
                $row['list_names'] = array();

                foreach ($row['list_ids'] as $id) {
                    $row['list_names'][$id] = isset($listNames[$id]) ? $listNames[$id] : 'Unknown';
                }

                // include name of list to add to
                $addListId = $row['addlistid'];
                $row['addlist'] = isset($listNames[$addListId]) ? $listNames[$addListId] : '';

                $responders[$row['id']] = $row;
            }

            uasort(
                $responders,
                function ($a, $b) {
                    $aname = reset($a['list_names']);
                    $bname = reset($b['list_names']);
                    $r = strcmp($aname, $bname);
                    return ($r == 0) ? $a['mins'] - $b['mins'] : $r;
                }
             );
        }
        return $responders;
    }

    public function addAutoresponder($mid, $mins, $addListId, $new = 1)
    {
        try {
            Sql_Query('BEGIN');

            Sql_Query(
                "INSERT INTO {$this->tables['autoresponders']}
                (enabled, mid, mins, addlistid, new, entered)
                VALUES (1, $mid, $mins, $addListId, $new, now())"
            );

            $res = Sql_Query(
                sprintf("SELECT id FROM {$this->tables['autoresponders']} WHERE mid = %d", $mid));

            $row = Sql_Fetch_Array($res);

            Sql_Query(
                "INSERT INTO {$this->tables['attribute']}
                (name, type, listorder, default_value, required, tablename)
                VALUES ('autoresponder_{$row['id']}', 'hidden', 0, '', 0, 'autoresponder_{$row['id']}')"
            );

            $attribute = $this->getAttribute($row['id']);

            $selectionQuery = 
                "SELECT ua.userid
                FROM {$this->tables['user_attribute']} ua
                LEFT JOIN {$this->tables['usermessage']} um ON ua.userid = um.userid AND um.messageid = $mid
                WHERE ua.attributeid = {$attribute['id']} AND ua.value != '' AND ua.value IS NOT NULL AND um.userid IS NULL";

            Sql_Query(
                sprintf(
                    "UPDATE {$this->tables['message']}
                    SET userselection = '%s' WHERE id = %d",
                    sql_escape($selectionQuery),
                    $mid
                )
            );

            Sql_Query('COMMIT');
        }
        catch (Exception $e) {
            Sql_Query('ROLLBACK');
            return false;
        }
        return true;
    }

    public function updateAutoresponder($id, $mins, $addListId, $new)
    {
        Sql_Query(
            "UPDATE {$this->tables['autoresponders']}
            SET mins = $mins, addlistid = $addListId, new = $new
            WHERE id = $id"
        );
        return true;
    }

    public function deleteAutoresponder($id)
    {
        $attribute = $this->getAttribute($id);

        try {
            Sql_Query('BEGIN');

            $res = Sql_Query(
                "SELECT mid FROM {$this->tables['autoresponders']}
                WHERE id = $id"
            );

            $row = Sql_Fetch_Array($res);

            if ($row && isset($row['mid'])) {
                Sql_Query(
                    "UPDATE {$this->tables['message']}
                    SET status = 'draft', userselection = NULL
                    WHERE id = {$row['mid']}"
                );

                Sql_Query(
                    "DELETE FROM {$this->tables['usermessage']}
                    WHERE messageid = {$row['mid']}"
                );
            }

            Sql_query(
                "DELETE FROM {$this->tables['autoresponders']}
                WHERE id = $id"
            );

            if ($attribute) {
                Sql_query(
                    "DELETE FROM {$this->tables['attribute']}
                    WHERE id = {$attribute['id']}"
                );

                Sql_query(
                    "DELETE FROM {$this->tables['user_attribute']}
                    WHERE attributeid = {$attribute['id']}"
                );
            }

            Sql_Query('COMMIT');
        }
        catch (Exception $e) {
            Sql_Query('ROLLBACK');
            return false;
        }
        return true;
    }

    /**
     * Return the autoresponder, if there is one, for a message
     *
     * @access  public
     * @param   int  $messageId the message id
     * @return  array the fields for the autoresponder
     *          or false if there is no autoresponder for the message
     */
    public function getAutoresponderForMessage($messageId)
    {
        $row = Sql_Fetch_Assoc(
            Sql_Query(<<<END
                SELECT id, addlistid
                FROM {$this->tables['autoresponders']} a
                WHERE a.mid = $messageId
END
            )
        );
        return $row;
    }

    /**
     * Add a subscriber to a list
     *
     * @access  public
     * @param   int  $listId the list id
     * @param   int  $userId the user id
     * @return  int  the number of rows added, 0 or 1
     */
    public function addSubscriberToList($listId, $userId)
    {
        $res = Sql_Query(<<<END
            INSERT IGNORE INTO {$this->tables['listuser']}
            (listid, userid, entered)
            VALUES ($listId, $userId, now())
END
        );
        return Sql_Affected_Rows();
    }
}
