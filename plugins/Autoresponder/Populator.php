<?php
/**
 * Autoresponder plugin for phplist.
 *
 * This file is a part of Autoresponder Plugin.
 *
 * @category  phplist
 *
 * @author    Duncan Cameron
 * @copyright 2015-2018 Duncan Cameron
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 */

namespace phpList\plugin\Autoresponder;

use phpList\plugin\Common\IPopulator;
use phpList\plugin\Common\PageLink;
use phpList\plugin\Common\PageURL;
use WebblerListing;

class Populator implements IPopulator
{
    public function __construct(DAO $dao, $listId)
    {
        $this->dao = $dao;
        $this->listId = $listId;
    }

    public function populate(WebblerListing $w, $start, $limit)
    {
        $w->setTitle('Autoresponders');

        foreach ($this->dao->getAutoresponders($this->listId) as $item) {
            $enableLink = new PageLink(
                new PageURL(null, array('action' => 'enable', 'id' => $item['id'])),
                $item['enabled'] ? s('yes') : s('no')
            );
            $prompt = s('Delete autoresponder %d, are you sure?', $item['id']);
            $deleteLink = new PageLink(
                new PageURL(null, array('action' => 'delete', 'id' => $item['id'])),
                s('Delete'),
                array('onclick' => "return confirm('$prompt')")
            );
            $delay = Util::formatMinutes($item['mins']);
            $key = $item['id'];
            $w->addElement($key, new PageURL(null, array('action' => 'edit', 'id' => $item['id'])));
            $w->addRow($key, s('Description'), $item['description']);
            $w->addRowHtml(
                $key,
                s('Campaign'),
                new PageLink(
                    new PageURL('message', array('id' => $item['mid'])),
                    $item['mid'] . ' | ' . htmlspecialchars($item['subject'])
                )
            );
            $w->addRow(
                $key,
                s('Autoresponder email will be sent'),
                s('%s after subscription to %s', $delay, $item['list_names'])
            );

            if ($item['addlist']) {
                $w->addRow($key, s('After sending, add subscriber to'), $item['addlist']);
            }
            $pending = $this->dao->pendingSubscribers($item['id']);
            $w->addRow($key, s('Subscribers ready to be sent'), count($pending));
            $w->addColumn($key, s('Added'), $item['entered']);
            $w->addColumn($key, s('New only'), $item['new'] ? 'yes' : 'no');
            $w->addColumnHtml($key, s('Enabled'), $enableLink);
            $w->addColumnHtml($key, s('Delete'), $deleteLink);
        }
        $w->addButton(s('Add'), new PageURL(null, array('action' => 'add')));
    }

    public function total()
    {
        return count($this->dao->getAutoresponders($this->listId));
    }
}
