<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 */

/**
 * Quick script that polls the XBMC HTTP interface (designed for PlexApp but
 * may work with XBMC itself)
 *
 * If Plex is playing, we proceed to use Miso to try and identify the media
 * then checkin to that media. Ensure that you have used miso.php to first
 * retrieve your OAuth token and token secret.
 *
 * There are options to also post to Twitter and Facebook, if the Miso user
 * has these configured on their account.
 *
 * Upon successful checking, we database the entry so that we can prevent
 * additional load on Miso. Although it handles suspected duplicate checkins
 * it is better to handle these internally.
 *
 * PHP Version >= 5.3.4
 *
 * @package    PlexToMiso
 * @copyright  2011 IATSTUTI
 * @author     Michael Dyrynda <michael@iatstuti.net>
 * @link       http://www.plexapp.com
 * @link       http://www.gomiso.com
 */
require_once dirname( __FILE__ ) . '/../common.php';

$xbmcHttpUrl = sprintf( 'http://%s:%d/%s', PLEX_HOST, PLEX_PORT, PLEX_PATH );
$postToFacebook = MISO_POST_TO_FACEBOOK;
$postToTwitter = MISO_POST_TO_TWITTER;

// I'm opening a URL... it may time out... if it does, I don't want to hear about it.
$content = @file( $xbmcHttpUrl );

$playing = true;

// If the XBMC HTTP interface has responded
if ( is_array( $content ) && count( $content ) > 0 ) {
    // We receive a HTML list, loop through each list item
    foreach ( $content as $line ) {
        if ( stristr( $line, 'Nothing Playing' ) ) {
            $playing = false;
        }

        /*
         * This should give us the relevant key / value pairs
         *
         * We will only split off the first one as the value may very well
         * contain additional colons
         */
        if ( stristr( $line, ':' ) ) {
            list ( $key, $value ) = explode( ':', $line, 2 );

            $return[str_replace( '<li>', '', $key)] = trim( $value );
        }
    }

    // Try and identify what is playing, if it is playing (it may be paused)
    if ( isset( $return['PlayStatus'] ) && $return['PlayStatus'] !== 'Playing' ) {
        $playing = false;
    }

    // Try and identify the media if we are playing
    if ( $playing ) {
        $searchType     = null;
        $searchTitle    = null;
        $searchYear     = null;
        $comment        = null;
        $mediaID        = null;
        $episodeTitle   = null;
        $season         = null;
        $episode        = null;
        $releaseYear    = null;
        $comment        = 'via http://www.PlexApp.com';

        if ( isset( $return['Show Title'] ) ) {
            // This is a TV show
            $searchType = 'TvShow';
            $searchTitle = trim( $return['Show Title'] );

            // Strip year from the title, Miso doesn't seem to like that
            $searchTitle = trim( preg_replace( '/\(\d+\)/', '', $searchTitle ) );

            $season  = $return['Season'];
            $episode = $return['Episode'];
        } else if ( isset( $return['Title'] ) ) {
            // This is a Movie
            $searchType = 'Movie';
            $searchTitle = trim( $return['Title'] );

            // Strip year from the title, Miso doesn't seem to like that
            $searchTitle = trim( preg_replace( '/\(\d+\)/', '', $searchTitle ) );
        }

        if ( !is_null( $searchType ) && !is_null( $searchTitle ) ) {
            $searchYear = isset( $return['Year'] ) ? $return['Year'] : null;

            printf( "%s Identified %s as type %s\n", date( 'Y-m-d H:i:s' ), $searchTitle, $searchType );

            if ( !is_null( $season ) ) {
                printf( "%s Season %d, Episode %d\n", date( 'Y-m-d H:i:s' ), $season, $episode );
            }

            // Only checkin if we're past the minimum required point
            if ( $return['Percentage'] >= MINIMUM_PERCENTAGE_BEFORE_CHECKIN  ) {
                // Locate the Miso media_id for this item
                try {
                    if ( !is_null( $searchYear ) ) {
                        $searchTitle = sprintf( '%s (%d)', $searchTitle, $searchYear );
                    }

                    $miso->fetch(
                        MISO_ENDPOINT_MEDIA_SEARCH,
                        array(
                            'q'     => $searchTitle,
                            'kind'  => $searchType,
                        )
                    );

                    $response = json_decode( $miso->getLastResponse() );

                    // No results found
                    if ( count( $response ) == 0 ) {
                        printf( "%s No results from Miso\n", date( 'Y-m-d H:i:s' ) );
                        die();
                    }

                    if ( $searchType == 'Movie' ) {
                        if ( !is_null( $searchYear ) ) {
                            foreach ( $response as $movie ) {
                                if ( $movie->{'media'}->{'release_year'} == $searchYear
                                    || trim( $movie->{'media'}->{'title'} ) == $searchTitle ) {
                                    $mediaID = $movie->{'media'}->{'id'};
                                    $releaseYear = $movie->{'media'}->{'release_year'};
                                }
                            }
                        }

                        if ( OVERWRITE_FOR_MOVIE ) {
                            if ( $postToFacebook == 'false' ) $postToFacebook = 'true';
                            if ( $postToTwitter == 'false' ) $postToTwitter = 'true';
                        }
                    } else if ( $searchType == 'TvShow' ) {
                        if ( !is_null( $searchYear ) ) {
                            foreach ( $response as $show ) {
                                if ( $show->{'media'}->{'release_year'} == $searchYear ) {
                                    $mediaID = $show->{'media'}->{'id'};
                                    $releaseYear = $show->{'media'}->{'release_year'};
                                } else if ( $show->{'media'}->{'title'} == $searchTitle ) {
                                    $mediaID = $show->{'media'}->{'id'};
                                    $releaseYear = $searchYear;
                                }
                            }
                        } else {
                            $mediaID = $response[0]->{'media'}->{'id'};
                        }

                        if ( OVERWRITE_FOR_TV ) {
                            if ( $postToFacebook == 'false' ) $postToFacebook = 'true';
                            if ( $postToTwitter == 'false' ) $postToTwitter = 'true';
                        }
                    }

                    if ( !is_null( $mediaID ) ) {
                        printf( "%s Identified Miso media id %d\n", date( 'Y-m-d H:i:s' ), $mediaID );
                        // If we have been able to identify the playing media, check that we haven't recently checked into it
                        $recentlyCheckedIn = recentlyCheckedIn( $mediaID, $comment, $season, $episode );

                        // Skip if we have
                        if ( $recentlyCheckedIn ) {
                            printf(
                                "%s Skipping checkin as this is media was checked into less than %s ago\n",
                                date( 'Y-m-d H:i:s' ),
                                MINIMUM_CHECKIN_INTERVAL
                            );
                        } else {
                                printf( "%s Checkin will%sbe posted to Facebook\n", date( 'Y-m-d H:i:s' ), $postToFacebook == 'true' ? ' ' : ' not ' );
                                printf( "%s Checkin will%sbe posted to Twitter\n", date( 'Y-m-d H:i:s' ), $postToTwitter == 'true' ? ' ' : ' not ' );

                                // Attempt to checkin
                                $params = array(
                                    'media_id'  => $mediaID,
                                    'comment'   => $comment,
                                    'facebook'  => $postToFacebook,
                                    'twitter'   => $postToTwitter,
                                );

                                if ( !is_null( $season ) ) {
                                    $params['season_num']  = $season;
                                    $params['episode_num'] = $episode;
                                }

                                $miso->fetch(
                                    MISO_ENDPOINT_CHECKIN,
                                    $params,
                                    OAUTH_HTTP_METHOD_POST
                                );

                                $response = json_decode( $miso->getLastResponse() );
                               
                                $misoMediaID = getMediaID(
                                    $response->{'checkin'}->{'media_id'},
                                    $response->{'checkin'}->{'media_title'},
                                    isset( $response->{'checkin'}->{'poster_image_url'} )
                                    ? $response->{'checkin'}->{'poster_image_url'}
                                    : null,
                                    is_null( $releaseYear ) ? $releaseYear : '',
                                    $searchType
                                );

                                $episodeTitle = isset( $response->{'checkin'}->{'episode_title'} ) &&
                                                trim( $response->{'checkin'}->{'episode_title'} ) !== ''
                                                ? $response->{'checkin'}->{'episode_title'}
                                                : null;

                                if ( $misoMediaID != 0 ) {
                                    $dbSeason  = is_null( $season ) ? 'NULL' : $season;
                                    $dbEpisode = is_null( $episode ) ? 'NULL' : $episode;
                                    $dbEpTitle = is_null( $episodeTitle )
                                                 ? 'NULL'
                                                 : "'" . mysql_real_escape_string( $episodeTitle, $dbh ) . "'";

                                    // If successfully checked in, database the response for future checks
                                    $query = sprintf(
                                        "INSERT INTO `checkinLog` ( `misoCheckinID`,
                                                                    `checkinTimestamp`,
                                                                    `comment`,
                                                                    `userID`,
                                                                    `mediaID`,
                                                                    `episodeTitle`,
                                                                    `season`,
                                                                    `episode`,
                                                                    `postedToFacebook`,
                                                                    `postedToTwitter`
                                                                ) VALUES (
                                                                    %d,
                                                                    NOW(),
                                                                    '%s',
                                                                    %d,
                                                                    %d,
                                                                    %s,
                                                                    %s,
                                                                    %s,
                                                                    %d,
                                                                    %d
                                                                )",
                                        $response->{'checkin'}->{'id'},
                                        mysql_real_escape_string( $response->{'checkin'}->{'comment'}, $dbh ),
                                        $response->{'checkin'}->{'user_id'},
                                        $misoMediaID,
                                        $dbEpTitle,
                                        $dbSeason,
                                        $dbEpisode,
                                        $postToFacebook == 'true' ? 1 : 0,
                                        $postToTwitter == 'true' ? 1 : 0
                                    );

                                    mysql_query( $query, $dbh );

                                    if ( mysql_error( $dbh ) ) {
                                        printf(
                                            "%s MySQL Error caching checkin: %s\n",
                                            date( 'Y-m-d H:i:s' ),
                                            mysql_error( $dbh )
                                        );
                                    }
                                }
                            }
                    } else {
                        printf( "%s Could not identify media ID. Debug follows.\n", date( 'Y-m-d H:i:s' ) );
                        print_r( $response );
                    }
                } catch ( OAuthException $e ) {
                    printf( "%s Miso error: %s\n", date( 'Y-m-d H:i:s' ), $e->getMessage() );
                    var_dump( $e );
                }
            } else {
                printf(
                    "%s %d < %d percent. Not checking in.\n",
                    date( 'Y-m-d H:i:s' ),
                    $return['Percentage'],
                    MINIMUM_PERCENTAGE_BEFORE_CHECKIN
                );
            }
        }
    } else {
        printf( "%s Nothing playing.\n", date( 'Y-m-d H:i:s' ) );
    }
} else {
    printf( "%s Plex appears to be off.\n", date( 'Y-m-d H:i:s' ) );
}

/**
 * Determine whether the given media has been recently checked in to
 *
 * @access public
 * @param int $mediaID Miso media ID to check
 * @param string $comment Check against the comment (TV shows will differ)
 * @param int $season Season number
 * @param int $episode Episode number
 * @return bool
 */
function recentlyCheckedIn( $mediaID, $comment, $season, $episode ) {
    global $dbh;

    $return = false;

    $query = sprintf(
        "   SELECT      `checkinLogID`
            FROM        `checkinLog`,
                        `misoMedia`
            WHERE       `checkinLog`.`mediaID` = `misoMedia`.`misoMediaID`
            AND         `misoMedia`.`mediaID` = %d
            AND         NOW() < DATE_ADD( `checkinTimestamp`, INTERVAL %s )
            AND         `comment` = '%s'
            AND         `season` %s
            AND         `episode` %s
            ORDER BY    `checkinTimestamp` DESC
            LIMIT       1",
        $mediaID,
        MINIMUM_CHECKIN_INTERVAL,
        mysql_real_escape_string( $comment, $dbh ),
        is_null( $season ) ? 'IS NULL' : "= '" . $season . "'",
        is_null( $episode ) ? 'IS NULL' : "= '" . $episode . "'"
    );

    $result = mysql_query( $query, $dbh );

    if ( mysql_num_rows( $result ) == 1 ) {
        $return = true;
    }

    return $return;
}

/**
 * Retrieve the local cache's media ID for a given piece of Miso media
 *
 * @access public
 * @param int $misoMediaID Miso's media identifier
 * @param string $title Miso's media title
 * @param string $posterImage Miso's media poster image
 * @param int $releaseYear Year media was released
 * @param string $mediaType Type of media being added, if required
 * @return int
 */
function getMediaID( $misoMediaID, $title = null, $posterImage = null, $releaseYear = '', $mediaType = null )
{
    global $dbh;

    $mediaID = 0;

    $query = 'SELECT `misoMediaID` FROM `misoMedia` WHERE `mediaID` = ' . intval( $misoMediaID );
    $result = mysql_query( $query, $dbh );

    if ( !mysql_num_rows( $result ) ) {
        $mediaID = addMisoMedia( $misoMediaID, $title, $posterImage, $releaseYear, $mediaType );
    } else {
        list( $mediaID ) = mysql_fetch_row( $result );
    }

    if ( $mediaID == 0 ) {
        printf( "%s Could not identify media ID for %d (%s)\n", date( 'Y-m-d H:i:s' ), $misoMediaID, $title );
    }

    return $mediaID;
}

/**
 * Add new new Miso media to the local cache
 *
 * @access public
 * @param int $misoMediaID Miso's media identifier
 * @param string $title Miso's media title
 * @param string $posterImage Miso's media poster image
 * @param int $releaseYear Year media was released
 * @param string $mediaType Type of media being added, if required
 * @return int
 */
function addMisoMedia( $misoMediaID, $title, $posterImage, $releaseYear, $mediaType )
{
    global $dbh;

    if ( $mediaType == 'TvShow' ) {
        $mediaType = 1;
    } else if ( $mediaType == 'Movie' ) {
        $mediaType = 2;
    } else {
        $mediaType = null;
    }

    if ( trim( $releaseYear ) == '' ) {
        $releaseYear = null;
    }

    $query = sprintf(
        "INSERT INTO `misoMedia` (  `mediaID`,
                                    `title`,
                                    `releaseYear`,
                                    `posterImage`,
                                    `typeID`
                                 ) VALUES (
                                    %d,
                                    '%s',
                                    %s,
                                    '%s',
                                    %s
                                )",
        $misoMediaID,
        mysql_real_escape_string( $title, $dbh ),
        is_null( $releaseYear ) ? 'NULL' : "'" . mysql_real_escape_string( $releaseYear, $dbh ),
        mysql_real_escape_string( $posterImage, $dbh ),
        is_null( $mediaType ) ? 'NULL' : $mediaType
    );

    mysql_query( $query, $dbh );

    if ( mysql_error( $dbh ) ) {
        printf( "%s MySQL Error adding new media: %s\n", date( 'Y-m-d H:i:s' ), mysql_error( $dbh ) );
    }

    return mysql_insert_id( $dbh );
}
