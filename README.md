# Autoresponder Plugin #

## Description ##

This is a conversion of the Autoresponder plugin from phplist 2.10.x to 3.0.x. I have not made any changes to take advantage of the plugin
architecture for phplist 3.

See this topic in the phplist support forum <http://forums.phplist.com/viewtopic.php?f=7&t=38786> for details of how the plugin works.

There are also a few fixes/changes to the plugin. The most important one is the command to run the autoresponder from a cron job.
See install.txt for how to get the appropriate command. Now a phplist user and password need to be added to the command.

If you are using php cgi then the command will be something like:

    php /home/me/www/lists/admin/index.php pi=Autoresponder page=process key=xxxx login=yyyy password=zzzz

Otherwise you will need to use curl, wget or similar program with a URL like:

    http://www.mydomain.com/lists/admin/?pi=Autoresponder&page=process&key=xxxx&login=yyyy&password=zzzz

The plugin adds an item to the Campaigns menu.

## Installation ##

### Dependencies ###

Requires php version 5.2 or later.

### Set the plugin directory ###
You can use a directory outside of the web root by changing the definition of `PLUGIN_ROOTDIR` in config.php.
The benefit of this is that plugins will not be affected when you upgrade phplist.

### Install through phplist ###
Install on the Plugins page (menu Config > Plugins) using the package URL `https://github.com/bramley/phplist-plugin-autoresponder/archive/master.zip`.

### Install manually ###
Download the plugin zip file from <https://github.com/bramley/phplist-plugin-autoresponder/archive/master.zip>

Expand the zip file, then copy the contents of the plugins directory to your phplist plugins directory.
This should contain

* the file Autoresponder.php
* the directory Autoresponder

## Version history ##

    version     Description
    2013-10-31  Initial version for phplist 3.0.x converted from 2.10 version
    2013-11-01  Added to GitHub
