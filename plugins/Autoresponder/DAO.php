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
use phpList\plugin\Common;

class Autoresponder_DAO extends Common\DAO
{
    /**
     * Create the database table
     * Upgrade the table by adding the addlistid column.
     *
     * @return none
     */
    private function init()
    {
        if (!Sql_Table_exists($this->tables['autoresponders'])) {
            $r = Sql_Query(
                "CREATE TABLE {$this->tables['autoresponders']} (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    description VARCHAR(255) DEFAULT '',
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

        if (!(bool) Sql_Num_Rows($r)) {
            $sql = <<<END
                ALTER TABLE {$this->tables['autoresponders']}
                ADD COLUMN addlistid INT(11) AFTER mins
END;
            Sql_Query($sql);
            logEvent('Autoresponder plugin table upgraded');
        }

        $r = Sql_Query("SHOW COLUMNS FROM {$this->tables['autoresponders']} LIKE 'description'");

        if (!(bool) Sql_Num_Rows($r)) {
            $sql = <<<END
                ALTER TABLE {$this->tables['autoresponders']}
                ADD COLUMN description VARCHAR(255) DEFAULT '' AFTER id
END;
            Sql_Query($sql);
            logEvent('Autoresponder plugin table upgraded');
        }
    }

    private function attributeName($arId)
    {
        return "autoresponder_$arId";
    }

    private function getAttribute($arId)
    {
        $name = $this->attributeName($arId);
        $res = Sql_Query(
            "SELECT * FROM {$this->tables['attribute']}
            WHERE name = '$name'"
        );

        return Sql_Fetch_Array($res);
    }

    /*
     *  Public methods
     */
    public function __construct()
    {
        parent::__construct(new Common\DB());

        $this->tables['autoresponders'] = $this->table_prefix . 'autoresponders';
        $this->init();
    }

    /**
     * Gets either all available draft messages or a specific message, used when editing an autoresponder.
     *
     * @param int $mid specific message id
     *
     * @return array associative array indexed by message id
     */
    public function getPossibleMessages($mid)
    {
        $where = $mid ? "m.id = $mid" : "status = 'draft' AND ar.id IS NULL AND lm.listid != 0";
        $res = Sql_Query(
            "SELECT
                GROUP_CONCAT(
                    DISTINCT l.name
                    ORDER BY l.name
                    SEPARATOR ','
                ) AS list_names,
                m.id,
                m.subject
            FROM {$this->tables['message']} m
            INNER JOIN {$this->tables['listmessage']} lm ON m.id = lm.messageid
            INNER JOIN {$this->tables['list']} l ON l.id = lm.listid
            LEFT JOIN {$this->tables['autoresponders']} ar ON m.id = ar.mid
            WHERE $where
            GROUP BY m.id"
        );

        $messages = array();

        while ($row = Sql_Fetch_Array($res)) {
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

    public function setPending()
    {
        $ars = $this->getAutoresponders();
        $messagesSubmitted = array();

        foreach ($ars as $ar) {
            if (!$ar['enabled']) {
                continue;
            }
            $attribute = $this->getAttribute($ar['id']);

            if (!$attribute) {
                logEvent("Attribute for autoresponder {$ar['id']} does not exist.");
                continue;
            }

            $qs = array();

            foreach (
                array(
                    'COUNT(*) AS number',
                    $attribute['id'] . ' AS attributeid, lu.userid AS userid, now() AS value',
                ) as $select) {
                $q =
                    "SELECT $select
                    FROM {$this->tables['autoresponders']} ar
                    INNER JOIN {$this->tables['message']} m ON ar.mid = m.id
                    INNER JOIN {$this->tables['listmessage']} lm ON m.id = lm.messageid
                    INNER JOIN {$this->tables['listuser']} lu ON lm.listid = lu.listid
                    INNER JOIN {$this->tables['user']} u ON u.id = lu.userid AND u.confirmed = 1 AND u.blacklisted = 0
                    LEFT JOIN {$this->tables['usermessage']} um ON lu.userid = um.userid AND um.messageid = m.id
                    WHERE ar.id = {$ar['id']}
                    AND (ar.new = 0 || ar.new = 1 && lu.modified > ar.entered)
                    AND (UNIX_TIMESTAMP(lu.modified) + (ar.mins * 60)) < UNIX_TIMESTAMP(now())
                    AND um.userid IS NULL
                    GROUP BY lu.userid";

                $qs[] = $q;
            }

            $numberReady = $this->dbCommand->queryOne($qs[0], 'number');

            if ($numberReady > 0) {
                try {
                    Sql_Query('BEGIN');

                    Sql_Query("REPLACE INTO {$this->tables['user_attribute']} " . $qs[1]);

                    Sql_Query(
                        "UPDATE {$this->tables['message']}
                        SET status = 'submitted'
                        WHERE (status = 'sent' OR status = 'draft') AND id = {$ar['mid']}"
                    );

                    if (Sql_Affected_Rows() > 0) {
                        $messagesSubmitted[] = $ar['mid'];
                    }
                    Sql_Query('COMMIT');
                } catch (Exception $e) {
                    Sql_Query('ROLLBACK');

                    return false;
                }
            }
        }

        return $messagesSubmitted;
    }

    public function getListNames()
    {
        static $names = null;

        if ($names === null) {
            $names = $this->dbCommand->queryColumn(
                "SELECT id, name FROM {$this->tables['list']}",
                'name',
                'id'
            );
        }

        return $names;
    }

    public function getArListNames()
    {
        static $names = null;

        if ($names === null) {
            $sql =
                "SELECT l.id, l.name
                FROM {$this->tables['list']} l
                JOIN {$this->tables['listmessage']} lm ON l.id = lm.listid
                JOIN {$this->tables['autoresponders']} ar ON ar.mid = lm.messageid";
            $names = $this->dbCommand->queryColumn($sql, 'name', 'id');
        }

        return $names;
    }

    public function autoresponder($id)
    {
        $sql =
            "SELECT ar.*
            FROM {$this->tables['autoresponders']} ar
            WHERE ar.id = $id";

        return $this->dbCommand->queryRow($sql);
    }

    public function getAutoresponders($listId = 0)
    {
        static $responders = null;

        if ($responders !== null) {
            return $responders;
        }
        $subQuery =
            "SELECT COUNT(DISTINCT lu2.userid)
            FROM {$this->tables['message']} m2
            INNER JOIN {$this->tables['listmessage']} lm2 ON m2.id = lm2.messageid
            INNER JOIN {$this->tables['listuser']} lu2 ON lm2.listid = lu2.listid
            INNER JOIN {$this->tables['user']} u2 ON u2.id = lu2.userid AND u2.confirmed = 1 AND u2.blacklisted = 0
            LEFT JOIN {$this->tables['usermessage']} um2 ON lu2.userid = um2.userid AND um2.messageid = m2.id
            WHERE m2.id = ar.mid
            AND (ar.new = 0 || ar.new = 1 && lu2.modified > ar.entered)
            AND (UNIX_TIMESTAMP(lu2.modified) + (ar.mins * 60)) < UNIX_TIMESTAMP(now())
            AND um2.userid IS NULL";

        $where = ($listId > 0)
            ? "lm.listid = $listId"
            : 'lm.listid != 0';
        $sql = <<<END
            SELECT
                ar.*,
                m.subject,
                GROUP_CONCAT(
                    DISTINCT CONCAT('"', l.name, '"')
                    ORDER BY l.name
                    SEPARATOR ', '
                ) AS list_names,
                l2.name AS addlist,
                ($subQuery) AS pending
            FROM {$this->tables['autoresponders']} ar
            INNER JOIN {$this->tables['message']} m ON ar.mid = m.id
            INNER JOIN {$this->tables['listmessage']} lm ON m.id = lm.messageid
            INNER JOIN {$this->tables['list']} l ON l.id = lm.listid
            LEFT JOIN {$this->tables['list']} l2 ON l2.id = ar.addlistid
            WHERE $where
            GROUP BY ar.id
            ORDER BY list_names, ar.mins
END;
        $responders = $this->dbCommand->queryAll($sql);

        return $responders;
    }

    public function addAutoresponder($description, $mid, $mins, $addListId, $new = 1)
    {
        try {
            Sql_Query('BEGIN');

            Sql_Query(
                "INSERT INTO {$this->tables['autoresponders']}
                (description, enabled, mid, mins, addlistid, new, entered)
                VALUES ('$description', 1, $mid, $mins, $addListId, $new, now())"
            );
            $arId = Sql_Insert_Id();
            $attrName = $this->attributeName($arId);

            Sql_Query(
                "INSERT INTO {$this->tables['attribute']}
                (name, type, listorder, default_value, required, tablename)
                VALUES ('$attrName', 'hidden', 0, '', 0, '$attrName')"
            );
            $attrId = Sql_Insert_Id();

            $selectionQuery =
                "SELECT ua.userid
                FROM {$this->tables['user_attribute']} ua
                LEFT JOIN {$this->tables['usermessage']} um ON ua.userid = um.userid AND um.messageid = $mid
                WHERE ua.attributeid = $attrId AND ua.value != '' AND ua.value IS NOT NULL AND um.userid IS NULL";

            Sql_Query(
                sprintf(
                    "UPDATE {$this->tables['message']}
                    SET userselection = '%s'
                    WHERE id = %d",
                    sql_escape($selectionQuery),
                    $mid
                )
            );

            Sql_Query('COMMIT');
        } catch (Exception $e) {
            Sql_Query('ROLLBACK');
            logEvent($e->getMessage());

            return false;
        }

        return true;
    }

    public function updateAutoresponder($id, $description, $mins, $addListId, $new)
    {
        $description = sql_escape($description);
        $sql =
            "UPDATE {$this->tables['autoresponders']}
            SET description = '$description', mins = $mins, addlistid = $addListId, new = $new
            WHERE id = $id";
        $count = $this->dbCommand->queryAffectedRows($sql);

        return true;
    }

    public function deleteAutoresponder($id)
    {
        $attribute = $this->getAttribute($id);

        try {
            Sql_Query('BEGIN');

            $sql =
                "SELECT mid FROM {$this->tables['autoresponders']}
                WHERE id = $id";
            $mid = $this->dbCommand->queryOne($sql, 'mid');

            if ($mid) {
                $sql =
                    "UPDATE {$this->tables['message']}
                    SET status = 'draft', userselection = NULL
                    WHERE id = $mid";
                $count = $this->dbCommand->queryAffectedRows($sql);

                $sql =
                    "DELETE FROM {$this->tables['usermessage']}
                    WHERE messageid = $mid";
                $count = $this->dbCommand->queryAffectedRows($sql);
            }

            $sql =
                "DELETE FROM {$this->tables['autoresponders']}
                WHERE id = $id";
            $count = $this->dbCommand->queryAffectedRows($sql);

            if ($attribute) {
                $sql =
                    "DELETE FROM {$this->tables['attribute']}
                    WHERE id = {$attribute['id']}";
                $count = $this->dbCommand->queryAffectedRows($sql);

                $sql =
                    "DELETE FROM {$this->tables['user_attribute']}
                    WHERE attributeid = {$attribute['id']}";
                $count = $this->dbCommand->queryAffectedRows($sql);
            }

            Sql_Query('COMMIT');
        } catch (Exception $e) {
            Sql_Query('ROLLBACK');

            return false;
        }

        return true;
    }

    /**
     * Return the autoresponder, if there is one, for a message.
     *
     * @param int $messageId the message id
     *
     * @return array the fields for the autoresponder
     *               or false if there is no autoresponder for the message
     */
    public function getAutoresponderForMessage($messageId)
    {
        $row = Sql_Fetch_Assoc(
            Sql_Query(<<<END
                SELECT id, addlistid, description
                FROM {$this->tables['autoresponders']} a
                WHERE a.mid = $messageId
END
            )
        );

        return $row;
    }

    /**
     * Add a subscriber to a list.
     *
     * @param int $listId the list id
     * @param int $userId the user id
     *
     * @return int the number of rows added, 0 or 1
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
