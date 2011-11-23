<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 */

/**
 * Configuration file
 *
 * PHP Version >= 5.3.4
 *
 * @package    PlexToMiso
 * @subpackage Configuration
 * @copyright  2011 IATSTUTI
 * @author     Michael Dyrynda <michael@iatstuti.net>
 */
// Database constants
define( 'DB_HOST', '' );
define( 'DB_PORT', '' );
define( 'DB_USER', '' );
define( 'DB_PASS', '' );
define( 'DB_NAME', '' );

// Generic configuration
define( 'BASEDIR', dirname( __FILE__ ) );
// Minimum checkin interval should be valid within MySQL's DATE_ADD function
define( 'MINIMUM_CHECKIN_INTERVAL', '' );
// Minimum percentage through media before checking in
define( 'MINIMUM_PERCENTAGE_BEFORE_CHECKIN', '' );

// Plex host config
define( 'PLEX_HOST', '' );
define( 'PLEX_PORT', '' );

// Miso config
define( 'MISO_CALLBACK_URL', '' );
define( 'MISO_TOKEN_FILE', '' );

// Miso user token
define( 'MISO_USER_ID', '' );
define( 'MISO_OAUTH_TOKEN', '' );
define( 'MISO_OAUTH_TOKEN_SECRET', '' );

// Social media constants
// These are _not_ boolean, they are strings
define( 'MISO_POST_TO_FACEBOOK', 'false' );
define( 'MISO_POST_TO_TWITTER', 'true' );

/*
 * We may want to overwrite social media defaults for different media types
 * These should be boolean true or false
 */
define( 'OVERWRITE_FOR_MOVIE', false );
define( 'OVERWRITE_FOR_TV', false );
