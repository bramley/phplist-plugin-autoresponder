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
 * @author    Duncan Cameron
 * @copyright 2015 Duncan Cameron
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 * @link      http://brightflock.com
 */
class Autoresponder_Populator implements CommonPlugin_IPopulator
{
    private $autoresponders;

    public function __construct($autoresponders)
    {
        $this->autoresponders = $autoresponders;
    }

    public function populate(WebblerListing $w, $start, $limit)
    {
        $w->setTitle('Autoresponders');

        foreach ($this->autoresponders as $item) {
            $enableLink = new CommonPlugin_PageLink(
                new CommonPlugin_PageURL(null, array('action' => 'enable', 'id' => $item['id'])),
                $item['enabled'] ? 'yes' : 'no'
            );
            $deleteLink = new CommonPlugin_PageLink(
                new CommonPlugin_PageURL(null, array('action' => 'delete', 'id' => $item['id'])),
                'delete',
                array('onclick' => "return confirm('Delete autoresponder {$item['id']}, are you sure?')")
            );
            $delay = Autoresponder_Util::formatMinutes($item['mins']);
            $key = $item['id'];
            $w->addElement($key, new CommonPlugin_PageURL(null, array('action' => 'edit', 'id' => $item['id'])));
            $w->addRow($key, 'Description', $item['description']);
            $w->addRowHtml(
                $key,
                'Campaign',
                new CommonPlugin_PageLink(
                    new CommonPlugin_PageURL('message', array('id' => $item['mid'])),
                    $item['mid'] . ' | ' . htmlspecialchars($item['subject'])
                )
            );
            $w->addRow(
                $key,
                'Autoresponder email will be sent',
                sprintf('%s after subscription to %s', $delay, $item['list_names'])
            );

            if ($item['addlist']) {
                $w->addRow($key, 'After sending, add subscriber to', $item['addlist']);
            }
            $w->addColumn($key, 'Added', $item['entered']);
            $w->addColumn($key, 'New only', $item['new'] ? 'yes' : 'no');
            $w->addColumnHtml($key, 'Enabled', $enableLink);
            $w->addColumnHtml($key, 'Delete', $deleteLink);

        }
        $w->addButton('Add', new CommonPlugin_PageURL(null, array('action' => 'add')));
    }

    public function total()
    {
        return count($this->autoresponders);
    }
}
