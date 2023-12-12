<?php

$pluginsDir = dirname(__DIR__);

return [
    'phpList\plugin\Autoresponder\ControllerFactory' => $pluginsDir . '/Autoresponder/ControllerFactory.php',
    'phpList\plugin\Autoresponder\Controller\Manage' => $pluginsDir . '/Autoresponder/Controller/Manage.php',
    'phpList\plugin\Autoresponder\Controller\Pageaction' => $pluginsDir . '/Autoresponder/Controller/Pageaction.php',
    'phpList\plugin\Autoresponder\DAO' => $pluginsDir . '/Autoresponder/DAO.php',
    'phpList\plugin\Autoresponder\Populator' => $pluginsDir . '/Autoresponder/Populator.php',
    'phpList\plugin\Autoresponder\Util' => $pluginsDir . '/Autoresponder/Util.php',
];
