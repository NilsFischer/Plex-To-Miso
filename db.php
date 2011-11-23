<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 */

/**
 * Database connection file
 *
 * PHP Version >= 5.1.6
 *
 * @package    SABNZBd
 * @subpackage Config
 * @copyright  2011 IATSTUTI
 * @author     Michael Dyrynda <michael@iatstuti.net>
 */
$dbh = mysql_connect( DB_HOST . ':' . DB_PORT, DB_USER, DB_PASS );
mysql_select_db( DB_NAME, $dbh );
