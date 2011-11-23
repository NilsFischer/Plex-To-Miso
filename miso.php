<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 */

/**
 * Script to retrieve OAuth token and secrets for a user.
 *
 * PHP Version >= 5.3.4
 *
 * @package    PlexToMiso
 * @subpackage Authentication
 * @copyright  2011 IATSTUTI
 * @author     Michael Dyrynda <michael@iatstuti.net>
 */
require_once dirname( __FILE__) . '/common.php';

try {
    $miso = new OAuth( MISO_CONSUMER_KEY, MISO_CONSUMER_SECRET );

    $miso->enableDebug();

    $response = $miso->getRequestToken( MISO_TOKEN_REQUEST_URL, MISO_CALLBACK_URL );
    
    file_put_contents( MISO_TOKEN_FILE, $response['oauth_token'] . "\n" . $response['oauth_token_secret'] );
    
    print '<pre>';
    var_dump( $response );
    print '</pre>';

    header( 'Location: ' . MISO_AUTHORIZE_API . '/?oauth_token=' . $response['oauth_token'] );
} catch ( OAuthException $e ) {
    print '<pre>';
    var_dump( $e );
    print '</pre>';
}
