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
 * @copyright 2013-2018 Cameron Lerch
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 *
 * @link      http://brightflock.com
 */

namespace phpList\plugin\Autoresponder;

class Util
{
    public static function formatMinutes($mins)
    {
        if (is_int($years = $mins / 60 / 24 / 7 / 52)) {
            return $years . ' year' . ($years != 1 ? 's' : '');
        }

        if (is_int($weeks = $mins / 60 / 24 / 7)) {
            return $weeks . ' week' . ($weeks != 1 ? 's' : '');
        }

        if (is_int($days = $mins / 60 / 24)) {
            return $days . ' day' . ($days != 1 ? 's' : '');
        }

        if (is_int($hours = $mins / 60)) {
            return $hours . ' hour' . ($hours != 1 ? 's' : '');
        }

        return $mins . ' minute' . ($mins != 1 ? 's' : '');
    }
}
