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

use Psr\Container\ContainerInterface;

/*
 * This file provides the dependencies for a dependency injection container.
 */

return [
    'phpList\plugin\Autoresponder\Controller\Manage' => function (ContainerInterface $container) {
        return new Controller\Manage(
            $container->get('DAO'),
            $container->get('phpList\plugin\Common\DAO\Lists')
        );
    },
    'phpList\plugin\Autoresponder\Controller\Pageaction' => function (ContainerInterface $container) {
        return new Controller\Pageaction();
    },
    'DAO' => function (ContainerInterface $container) {
        return new DAO(
            $container->get('phpList\plugin\Common\DB')
        );
    },
    'Logger' => function (ContainerInterface $container) {
        return \phpList\plugin\Common\Logger::instance();
    },
];
