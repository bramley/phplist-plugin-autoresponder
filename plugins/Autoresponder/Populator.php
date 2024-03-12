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

use confirmButton;
use phpList\plugin\Common\ImageTag;
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
        $w->setElementHeading('Autoresponder');

        foreach (new \LimitIterator($this->dao->getAutoresponders($this->listId, false), $start, $limit) as $item) {
            $key = "{$item['id']} | {$item['description']}";

            if ($item['messageid']) {
                $w->addElement($key, new PageURL(null, array('action' => 'edit', 'id' => $item['id'])));
                $enabledColumn = new PageLink(
                    new PageURL(null, array('action' => 'enable', 'id' => $item['id'])),
                    $item['enabled'] ? new ImageTag('yes.png', s('Enabled')) : new ImageTag('no.png', s('Disabled'))
                );
                $messageRow = new PageLink(
                    new PageURL('message', array('id' => $item['mid'])),
                    $item['mid'] . ' | ' . htmlspecialchars($item['subject'])
                );
            } else {
                // the message used by the autoresponder does not exist
                $w->addElement($key);
                $enabledColumn = new ImageTag('no.png', s('Disabled'));
                $messageRow = s('Campaign %d does not exist', $item['mid']);
            }
            $w->addRowHtml($key, s('Campaign'), $messageRow);
            $w->addRow(
                $key,
                s('Autoresponder email will be sent'),
                s('%s after subscription to %s', Util::formatMinutes($item['mins']), $item['list_names'])
            );

            if ($item['addlist']) {
                $w->addRow($key, s('After sending, add subscriber to'), $item['addlist']);
            }
            $pending = $this->dao->pendingSubscribers($item['id']);
            $notReady = $this->dao->notReadySubscribers($item['id']);
            $totalSent = $this->dao->totalSentSubscribers($item['id']);
            $w->addRow(
                $key,
                s('Subscribers ready | not ready | already sent'),
                sprintf('%s | %s | %s', count($pending), count($notReady), $totalSent)
            );
            $w->addColumn($key, s('Added'), $item['entered']);
            $w->addColumn($key, s('New only'), $item['new'] ? s('yes') : s('no'));
            $w->addColumnHtml($key, s('Enabled'), $enabledColumn);
            $w->addColumnHtml($key, s('Delete'), $this->confirmDeleteButton($item['id']));
            $w->addColumnHtml($key, s('Reset'), $this->confirmResetButton($item['id']));
        }
        $w->addButton(s('Add'), htmlspecialchars(new PageURL(null, array('action' => 'add'))));
    }

    public function total()
    {
        return count($this->dao->getAutoresponders($this->listId, false));
    }

    private function confirmDeleteButton($id)
    {
        $button = new confirmButton(
            s('Delete autoresponder %d, are you sure?', $id),
            htmlspecialchars(new PageURL(null, ['action' => 'delete', 'id' => $id, 'redirect' => $_SERVER['REQUEST_URI']])),
            'Delete',
            'Delete autoresponder',
            'button'
        );

        return sprintf('<span class="delete">%s</span>', $button->show());
    }

    private function confirmResetButton($id)
    {
        $button = new confirmButton(
            s('Reset autoresponder %d. This is for testing the autoresponder and will remove rows from the usermessage and listuser tables, are you sure?', $id),
            htmlspecialchars(new PageURL(null, ['action' => 'reset', 'id' => $id, 'redirect' => $_SERVER['REQUEST_URI']])),
            'Reset',
            'Reset autoresponder',
            'button'
        );

        return sprintf('<span class="delete">%s</span>', $button->show());
    }
}
