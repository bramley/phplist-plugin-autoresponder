# Autoresponder Plugin #

## Description ##

This plugin allows you to send campaigns to subscribers of a list based on the time elapsed since that subscriber joined the list 
(a different actual time for each subscriber). For example, to send a newsletter to a subscriber 1 hour after he joined a particular list.

This is a conversion of the Autoresponder plugin from phplist 2.10.x to 3.0.x adapted to use the plugin architecture of phplist 3. 

See this topic in the phplist support forum <http://forums.phplist.com/viewtopic.php?f=7&t=38786> 
for details of how the plugin works.

The plugin adds an item to the Campaigns menu.

## Installation ##

### Dependencies ###

Requires php version 5.2 or later.

### Set the plugin directory ###
You can use a directory outside of the web root by changing the definition of `PLUGIN_ROOTDIR` in config.php.
The benefit of this is that plugins will not be affected when you upgrade phplist.

### Install through phplist ###
Install on the Plugins page (menu Config > Plugins) using the package URL `https://github.com/bramley/phplist-plugin-autoresponder/archive/master.zip`.

There is a bug that can cause a plugin to be incompletely installed on some configurations (<https://mantis.phplist.com/view.php?id=16865>). 
Check that these files are in the plugin directory. If not then you will need to install manually.

* the file Autoresponder.php
* the directory Autoresponder

### Install manually ###
Download the plugin zip file from <https://github.com/bramley/phplist-plugin-autoresponder/archive/master.zip>

Expand the zip file, then copy the contents of the plugins directory to your phplist plugins directory.
This should contain

* the file Autoresponder.php
* the directory Autoresponder

## Usage ##

### Create draft messages ###

Add some draft messages to use as Autoresponders. The plugin uses draft messages as the message that are
sent as an autoresponder. Be sure that you select some lists in your draft message. New members of the lists you selected
will get the autoresponder message.

### Create an autoresponder ###

On the autoresponder page, create an autoresponder. For each autoresponder created you will get a new user attribute. That
user attribute will be populated with the date the message was sent to the user. If the attribute is empty, the message has
not been sent.

### Create a cron job ###

The Process Autoresponders page needs to be run periodically to prepare the messages to be sent. To avoid having to do that manually you
can create a cron job to access that page at regular intervals. For example using php-cli the command will be similar to 
(but adjust for the directory in which phplist is installed)

    php /home/me/www/lists/admin/index.php -m Autoresponder -p process -c /home/me/www/lists/config/config.php

If you are using php-cgi or a command such as wget or curl then you will need to include the credentials of a phplist admin
in the command. The admin should be an ordinary admin with no privileges. For example using php-cgi the command will be similar to

    php /home/me/www/lists/admin/index.php pi=Autoresponder page=process login=yyyy password=zzzz
    
or using the wget command

    wget -O - -q -t 1 "http://www.mydomain.com/lists/admin/?pi=Autoresponder&page=process&login=yyyy&password=zzzz"

The process page selects those messages that now need to be sent and the subscribers to receive the messages.
The phplist process queue command still needs to be run in order to actually send the messages.

## Version history ##

    version     Description
    2013-12-12  Workaround for phplist bug - Mantis 16940
    2013-11-05  Removed key processing, added command line support
    2013-11-01  Added to GitHub
    2013-10-31  Initial version for phplist 3.0.x converted from 2.10 version
