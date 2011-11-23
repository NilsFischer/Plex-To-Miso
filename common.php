<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 */

/**
 * Common include file
 *
 * PHP Version >= 5.3.4
 *
 * @package    PlexToMiso
 * @copyright  2011 IATSTUTI
 * @author     Michael Dyrynda <michael@iatstuti.net>
 */
require_once dirname( __FILE__ ) . '/config.php';
require_once BASEDIR . '/defines.php';
require_once BASEDIR . '/db.php';

// Attempt to instantiate a new OAuth object for Miso
try {
    $miso = new OAuth( MISO_CONSUMER_KEY, MISO_CONSUMER_SECRET );
    $miso->setToken( MISO_OAUTH_TOKEN, MISO_OAUTH_TOKEN_SECRET );
} catch ( OAuthException $e ) {
    print $e->getLastResponse();
}
