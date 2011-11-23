<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 */

/**
 * Defines file
 *
 * PHP Version >= 5.3.4
 *
 * @package    PlexToMiso
 * @subpackage Configuration
 * @copyright  2011 IATSTUTI
 * @author     Michael Dyrynda <michael@iatstuti.net>
 */
// Miso URL
define( 'MISO_SITE', 'http://www.gomiso.com' );
define( 'MISO_TOKEN_REQUEST_URL', 'http://gomiso.com/oauth/request_token' );
define( 'MISO_TOKEN_ACCESS_URL', 'http://gomiso.com/oauth/access_token' );
define( 'MISO_AUTHORIZE_API', 'https://gomiso.com/oauth/authorize' );

// Miso endpoints
define( 'MISO_ENDPOINT_MEDIA_SEARCH', 'http://gomiso.com/api/oauth/v1/media.json' );
define( 'MISO_ENDPOINT_CHECKIN', 'http://gomiso.com/api/oauth/v1/checkins.json' );
define( 'MISO_ENDPOINT_USER', 'http://gomiso.com/api/oauth/v1/users/show.json' );

// Application key and secret
define( 'MISO_CONSUMER_KEY', 'gI2oXPlfUBfGhO7broqQ' );
define( 'MISO_CONSUMER_SECRET', 'LxFIkjKjwoxlkuc4V8hPC5niuq8cSHbWwFvdwSTe' );

// Media types
define( 'MEDIA_TYPE_TV_SHOW', 1 );
define( 'MEDIA_TYPE_MOVIE', 2 );

define( 'PLEX_PATH', 'xbmcCmds/xbmcHttp?command=GetCurrentlyPlaying' );
