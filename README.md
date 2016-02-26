# Autoresponder Plugin #

## Description ##

This plugin allows you to send a campaign to subscribers of a list at a specified period after each subscriber joined the list 
(sending at a different actual time for each subscriber).
For example, to send a newsletter to a subscriber 1 day after he joined the list.

This is a conversion of the Autoresponder plugin from phplist 2.10.x to phplist 3 being adapted to use the plugin architecture of phplist 3,
and being substantially enhanced.
The original plugin was developed by Cameron Lerch of Brightflock Inc. <http://brightflock.com/phplist-autoresponder> to whom credit
is due.

## Installation ##

### Dependencies ###

Requires php version 5.3 or later.

This plugin requires the Common Plugin v3 to also be installed, and will not work without that.
**You must install that plugin or upgrade to the latest version if it is already installed**.
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

##Usage##

For guidance on usage see the plugin page within the phplist documentation site <https://resources.phplist.com/plugin/autoresponder_3.x>

## Version history ##

    version         Description
    2.2.2+20160226  Leave campaign at sent status
    2.2.1+20151020  Internal change to avoid php strict warning
    2.2.0+20150821  Filter autoresponders, added description field
    2.1.0+20150811  Can now edit an autoresponder, improved layout of listing
    2.0.1+20150807  Fixes bug in not adding subscriber to another list
    2015-07-30      Added action to add subscribers to another list when campaign has been sent
    2015-02-15      Inform plugins when a campaign is requeued
    2015-02-02      Allow delay value to be entered directly, "process" now a separate page
    2013-12-12      Workaround for phplist bug - Mantis 16940
    2013-11-05      Removed key processing, added command line support
    2013-11-01      Added to GitHub
    2013-10-31      Initial version for phplist 3.0.x converted from 2.10 version
