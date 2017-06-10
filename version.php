<?php

//  My plugin version YYYYMMDDXX
//  Parameter required
$plugin->version = 2017052406;

/**
 *  Specifies the minimum version number of moodle core that this plugin requires
 *  You can see Moodle core's version number in version.php located in Moodle root directory
 *  Parameterrecomended.
 */
$plugin->requires = 2014051200;     // Minimun moodle 2.7 is required

/**
 *  Component
 *  It is used during the installation and upgrade process for diagnostics and validation purposes
 *  to make sure the plugin code has been deployed to the correct location within the moodle code tree.
 *  Defined as: plugintype_pluginname
 *  Parameter required since moodle 3.0
 */

$plugin->component = 'local_wsbc';

/**
 *  Release
 *  Only human readable version name
 *  Parameter recomended.
 */

$plugin->release = 'v1.0.0';

/**
 *  Maturity
 *  Declares the maturity level of this plugin version
 *  This affects the available update notifications feature in moodle
 *  Paremeter recomended.
 */

$plugin->maturity = MATURITY_STABLE;    // This plugin is considered as ready for production sites