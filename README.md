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

Requires phpList version 3.3.2 or later and php version 5.4 or later.

Also requires the Common Plugin v3.18.4 or later to be installed.
phplist now includes Common Plugin so you should only need to enable it on the Manage Plugins page.

### Install through phplist ###
Install on the Plugins page (menu Config > Manage Plugins) using the package URL\
`https://github.com/bramley/phplist-plugin-autoresponder/archive/master.zip`

## Usage ##
For guidance on usage see the plugin page within the phplist documentation site <https://resources.phplist.com/plugin/autoresponder>

## Version history ##

    version         Description
    3.4.0+20220421  Fix for error in deleting 'not sent' rows from the usermessage table
    3.3.2+20220218  Improve the display of field labels on the autoresponder form
    3.3.1+20200713  Revise README file
    3.3.0+20200517  Improve method of removing 'notsent' rows from usermessage table
    3.2.1+20191231  Add missing htmlspecialchars() on URLs for buttons
    3.2.0+20190405  Add reset function to aid testing an autoresponder
    3.1.0+20181222  Display autoresponders whose message has been deleted
    3.0.7+20181122  Correct conversion of delay period into minutes
    3.0.6+20180714  Added missing call to parent activate() method
    3.0.5+20180613  Remove another dependency on php 5.6
    3.0.4+20180517  Avoid dependency on php 5.6
    3.0.3+20180330  Reduce the level of error reporting
    3.0.2+20180225  Display the number of subscribers to which an autoresponder has been sent
    3.0.1+20180224  Display the number of subscribers not ready to send
    3.0.0+20180210  Rework method of selecting subscribers
    2.3.3+20170127  Improve handling of missing attribute
    2.3.2+20160527  Add class map for autoloading
    2.3.1+20160304  Fix bug in previous version
    2.3.0+20160304  Process autoresponders is now performed automatically
                    Display the number of ready subscribers for an autoresponder
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
