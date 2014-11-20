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


// get_json('http://127.0.0.1:10018/riak/tweets/528962560205533186');

$client_source = new Basho\Riak\Riak('127.0.0.1', 10018);
$client_target = new Basho\Riak\Riak('127.0.0.1', 10018);
$bucket_source = $client_source->bucket('twitterstream');
get_keys();

function get_keys() {
global $bucket_source, $client_source;
$string = file_get_contents('http://127.0.0.1:10018/buckets/twitterstream/keys?keys=true');
$tweet = json_decode($string,true);

foreach($tweet['keys'] as $p) {
  $bucket_source = $client_source->bucket('tweets');
  $jsonurl = 'http://127.0.0.1:10018/riak/twitterstream/'.$p;
  get_json($jsonurl);
  $sourcetweet = $bucket_source->get($p);
 }

}


function uniord($u) {
    // i just copied this function fron the php.net comments, but it should work fine!
    $k = mb_convert_encoding($u, 'UCS-2LE', 'UTF-8');
    $k1 = ord(substr($k, 0, 1));
    $k2 = ord(substr($k, 1, 1));
    return $k2 * 256 + $k1;
}


function is_arabic($str) {
    if(mb_detect_encoding($str) !== 'UTF-8') {
        $str = mb_convert_encoding($str,mb_detect_encoding($str),'UTF-8');
    }

    /*
    $str = str_split($str); <- this function is not mb safe, it splits by bytes, not characters. we cannot use it
    $str = preg_split('//u',$str); <- this function woulrd probably work fine but there was a bug reported in some php version so it pslits by bytes and not chars as well
    */
    preg_match_all('/.|\n/u', $str, $matches);
    $chars = $matches[0];
    $arabic_count = 0;
    $latin_count = 0;
    $total_count = 0;
    foreach($chars as $char) {
        //$pos = ord($char); we cant use that, its not binary safe 
        $pos = uniord($char);
        echo $char ." --> ".$pos.PHP_EOL;

        if($pos >= 1536 && $pos <= 1791) {
            $arabic_count++;
        } else if($pos > 123 && $pos < 123) {
            $latin_count++;
        }
        $total_count++;
    }
    if(($arabic_count/$total_count) > 0.6) {
        // 60% arabic chars, its probably arabic
        return true;
    }
    return false;
}


function delete_bucket($bucketname) {
        $client_delete = new Basho\Riak\Riak('127.0.0.1', 10018);
        $bucket_delete = $client_delete->bucket($bucketname);
        $url = 'http://127.0.0.1:10018/buckets/'. $bucketname. '/keys?keys=true';
        $string = file_get_contents($url);
        $tweet = json_decode($string,true);
        foreach($tweet['keys'] as $p) {
          $sourcetweet = $bucket_delete->get($p);
          $sourcetweet->delete();
         }
}


function get_json($url) {
global $client_target;
$string = file_get_contents($url);
// echo $string;
$tweet = json_decode($string,true);
$bucket_target = $client_target->bucket('hashtags');

foreach($tweet['entities']['hashtags'] as $p)
{
//if ($tweet['lang'] = 'en' AND strpos($tweet['user']['location'],'UK')) {
if ($tweet['lang'] = 'en') {
 echo 'Hashtag: '.$p['text'];
 echo "\r\n";
$tweetid = $tweet['id']. '_'. $p['text']; 
echo $p['text']. "\r\n";
$riak_tweet = $bucket_target->newObject($tweetid, array(
	'hashtag' => $p['text'],
     ));
 if (!is_arabic($p['text'])) {
   $riak_tweet->store(); }}
}

// print_r($tweet);

}

?>
