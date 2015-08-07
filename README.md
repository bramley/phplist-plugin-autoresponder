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

Requires the Common Plugin to be installed. You must install the plugin or upgrade to the latest version if it is already installed.
See <https://github.com/bramley/phplist-plugin-common>

### Set the plugin directory ###
The default plugin directory is `plugins` within the admin directory.

You can use a directory outside of the web root by changing the definition of `PLUGIN_ROOTDIR` in config.php.
The benefit of this is that plugins will not be affected when you upgrade phplist.

### Install through phplist ###
Install on the Plugins page (menu Config > Manage Plugins) using the package URL `https://github.com/bramley/phplist-plugin-autoresponder/archive/master.zip`.

In phplist releases 3.0.5 and earlier there is a bug that can cause a plugin to be incompletely installed on some configurations (<https://mantis.phplist.com/view.php?id=16865>). 
Check that these files are in the plugin directory. If not then you will need to install manually. The bug has been fixed in release 3.0.6.

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
can create a cron job to access that page at regular intervals. The command will be similar to this for phplist release 3.0.9
or later, or if you are using php-cli with an earlier release of phplist
(but adjust for the directory in which phplist is installed)

    php /home/me/www/lists/admin/index.php -m Autoresponder -p process -c /home/me/www/lists/config/config.php

Prior to phplist release 3.0.9, if you are using php-cgi then you will need to include the credentials of a phplist admin
in the command. The admin should be an ordinary admin with no privileges. For example using php-cgi the command will be similar to

    php /home/me/www/lists/admin/index.php pi=Autoresponder page=process login=yyyy password=zzzz
    
The process page selects those messages that now need to be sent and the subscribers to receive the messages.
The phplist process queue command still needs to be run in order to actually send the messages.

## Version history ##

    version         Description
    2.0.1+20150807  Fixes bug in not adding subscriber to another list
    2015-07-30      Added action to add subscribers to another list when campaign has been sent
    2015-02-15      Inform plugins when a campaign is requeued
    2015-02-02      Allow delay value to be entered directly, "process" now a separate page
    2013-12-12      Workaround for phplist bug - Mantis 16940
    2013-11-05      Removed key processing, added command line support
    2013-11-01      Added to GitHub
    2013-10-31      Initial version for phplist 3.0.x converted from 2.10 version
