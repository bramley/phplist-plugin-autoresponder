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

/**
 * This class is a concrete implementation of phpList\plugin\Common\ControllerFactoryBase.
 *
 * @category  phplist
 */

namespace phpList\plugin\Autoresponder;

use phpList\plugin\Common\Container;
use phpList\plugin\Common\ControllerFactoryBase;

class ControllerFactory extends ControllerFactoryBase
{
    /**
     * Custom implementation to create a controller using plugin and page.
     *
     * @param string $pi     the plugin
     * @param array  $params further parameters from the URL
     *
     * @return phpList\plugin\Common\Controller
     */
    public function createController($pi, array $params)
    {
        $depends = include __DIR__ . '/depends.php';
        $container = new Container($depends);
        $class = __NAMESPACE__ . '\\Controller\\' . ucfirst($params['page']);

        return $container->get($class);
    }
}
