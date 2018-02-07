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

namespace phpList\plugin\Autoresponder;

use phpList\plugin\Common;

class DAO extends Common\DAO
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
        }

        $r = Sql_Query("SHOW COLUMNS FROM {$this->tables['autoresponders']} LIKE 'description'");

        if (!(bool) Sql_Num_Rows($r)) {
            $sql = <<<END
                ALTER TABLE {$this->tables['autoresponders']}
                ADD COLUMN description VARCHAR(255) DEFAULT '' AFTER id
END;
            Sql_Query($sql);
        }
    }

    /*
     *  Public methods
     */
    public function __construct($db)
    {
        parent::__construct($db);

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

    public function submitCampaign($messageId)
    {
        Sql_Query(
            "UPDATE {$this->tables['message']}
            SET status = 'submitted'
            WHERE (status = 'sent' OR status = 'draft') AND id = $messageId"
        );

        return Sql_Affected_Rows() > 0;
    }

    public function pendingSubscribers($arId)
    {
        $q =
            "SELECT lu.userid AS id
            FROM {$this->tables['autoresponders']} ar
            INNER JOIN {$this->tables['message']} m ON ar.mid = m.id
            INNER JOIN {$this->tables['listmessage']} lm ON m.id = lm.messageid
            INNER JOIN {$this->tables['listuser']} lu ON lm.listid = lu.listid
            INNER JOIN {$this->tables['user']} u ON u.id = lu.userid AND u.confirmed = 1 AND u.blacklisted = 0
            LEFT JOIN {$this->tables['usermessage']} um ON lu.userid = um.userid AND um.messageid = m.id
            WHERE ar.id = $arId
            AND (ar.new = 0 || ar.new = 1 && lu.modified > ar.entered)
            AND (UNIX_TIMESTAMP(lu.modified) + (ar.mins * 60)) < UNIX_TIMESTAMP(now())
            AND (um.userid IS NULL OR um.status = 'not sent')
            GROUP BY lu.userid";

        return $this->dbCommand->queryAll($q);
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

    /**
     * Returns the fields for one autoresponder.
     *
     * @param int $id autoresponder id
     *
     * @return array
     */
    public function autoresponder($id)
    {
        $sql =
            "SELECT ar.*
            FROM {$this->tables['autoresponders']} ar
            WHERE ar.id = $id";

        return $this->dbCommand->queryRow($sql);
    }

    /**
     * Returns the fields for all autoresponders or for those autoresponders whose campaigns are sent to a specific list.
     * The result is cached as this method can be called several times.
     *
     * @param int $listId optional list id
     *
     * @return Iterator
     */
    public function getAutoresponders($listId = 0)
    {
        static $responders = null;

        if ($responders !== null) {
            return $responders;
        }
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
                l2.name AS addlist
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
        try {
            Sql_Query('BEGIN');

            $sql =
                "SELECT mid FROM {$this->tables['autoresponders']}
                WHERE id = $id";
            $mid = $this->dbCommand->queryOne($sql, 'mid');

            if ($mid) {
                $sql =
                    "UPDATE {$this->tables['message']}
                    SET status = 'draft'
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

    /**
     * Returns the highest value of id from the user table.
     *
     * @return int the highest value of id
     */
    public function highestSubscriberId()
    {
        $sql = <<<END
            SELECT MAX(id)
            FROM {$this->tables['user']}
END;

        return $this->dbCommand->queryOne($sql);
    }

    /**
     * Delete rows from the usermessage table that have status 'not sent'.
     *
     * @param array $messageid
     *
     * @return int the number of rows deleted
     */
    public function deleteNotSent($messageid)
    {
        $sql = <<<END
            DELETE FROM {$this->tables['usermessage']}
            WHERE status = 'not sent'
            AND messageid = $messageid
END;

        return $this->dbCommand->queryAffectedRows($sql);
    }

    /**
     * Set the userselection field to null for messages which are used in autoresponders.
     */
    public function upgradeMessageTable()
    {
        $sql = <<<END
            UPDATE {$this->tables['message']}
            SET userselection = null
            WHERE id IN (
                SELECT mid
                FROM {$this->tables['autoresponders']}
            )
END;

        return $this->dbCommand->queryAffectedRows($sql);
    }
}
