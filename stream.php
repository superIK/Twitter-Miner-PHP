<?php

//Include RIAK-PHP-CLIENT
require_once('riak-php-client/src/Basho/Riak/Riak.php');
require_once('riak-php-client/src/Basho/Riak/Bucket.php');
require_once('riak-php-client/src/Basho/Riak/Exception.php');
require_once('riak-php-client/src/Basho/Riak/Link.php');
require_once('riak-php-client/src/Basho/Riak/MapReduce.php');
require_once('riak-php-client/src/Basho/Riak/Object.php');
require_once('riak-php-client/src/Basho/Riak/StringIO.php');
require_once('riak-php-client/src/Basho/Riak/Utils.php');
require_once('riak-php-client/src/Basho/Riak/Link/Phase.php');
require_once('riak-php-client/src/Basho/Riak/MapReduce/Phase.php');


// Include Phirehouse
require_once('Phirehose.php');
require_once('OauthPhirehose.php');


// Connecting to Riak database
$client_riak = new Basho\Riak\Riak('127.0.0.1', 10018);
$bucket_riak = $client_riak->bucket('twitterstream');


/**
 * Example of using Phirehose to display the 'sample' twitter stream.
 */
class SampleConsumer extends OauthPhirehose
{
  /**
   * Enqueue each status
   *
   * @param string $status
   */
  public function enqueueStatus($status)
  {
    /*
     * In this simple example, we will just display to STDOUT rather than enqueue.
     * NOTE: You should NOT be processing tweets at this point in a real application, instead they should be being
     *       enqueued and processed asyncronously from the collection process.
     */
    global $client_riak, $bucket_riak;
    $data = json_decode($status, true);
    if (is_array($data) && isset($data['user']['screen_name'])) {
      //print $data['user']['screen_name'] . ': ' . urldecode($data['text']) . "\n";
      //Preparing the store data into RIAK
      $riak_tweet = $bucket_riak->newObject($data['id'], $data);
      $riak_tweet->store();
    }
  }
}

// The OAuth credentials you received when registering your app at Twitter
define("TWITTER_CONSUMER_KEY", "Z1GMWyeEgXn2f18kbAhCg033C");
define("TWITTER_CONSUMER_SECRET", "67azZQCQpAqm0LTcKj7TBUbR13liw605t0Wsng5ksdGAxRSFCV");


// The OAuth data for the twitter account
define("OAUTH_TOKEN", "2854735275-twUxmUpbZD4Gch0kYyGhM2DsNSI3v6k1RMxuVE0");
define("OAUTH_SECRET", "2lErBFByazitLoH8EadIFi1Uq8NDqaCUm0Tx1rUU4Qwld");

// Start streaming
$sc = new SampleConsumer(OAUTH_TOKEN, OAUTH_SECRET, Phirehose::METHOD_SAMPLE);
$sc->consume();


?>
