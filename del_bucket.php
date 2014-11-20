<?php

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


function delete_bucket($bucketname) {
	$client = new Basho\Riak\Riak('127.0.0.1', 10018);
	$bucket_source = $client->bucket($bucketname);
	$url = 'http://127.0.0.1:10018/buckets/'. $bucketname. '/keys?keys=true';
	$string = file_get_contents($url);
	$tweet = json_decode($string,true);
	foreach($tweet['keys'] as $p) {
	  $sourcetweet = $bucket_source->get($p);
	  $sourcetweet->delete();
	 }
}

delete_bucket('twitterstream');
delete_bucket('hashtags');

?>
