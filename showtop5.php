<?php
error_reporting(0);

$string = file_get_contents('top5.txt');
$tweet = explode(',', $string);
//print_r($tweet);


function clean($string) {
   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}


print "This is the top5 hashtags". "\n";
print "1. #". clean(substr($tweet[0],3,-1)). " (". clean(substr($tweet[1],0,-1)). ")". "\n";
print "2. #". clean(substr($tweet[2],2,-1)). " (". clean(substr($tweet[3],0,-1)). ")". "\n";
print "3. #". clean(substr($tweet[4],2,-1)). " (". clean(substr($tweet[5],0,-1)). ")". "\n";
print "4. #". clean(substr($tweet[6],2,-1)). " (". clean(substr($tweet[7],0,-1)). ")". "\n";
print "5. #". clean(substr($tweet[8],2,-1)). " (". clean(substr($tweet[9],0,-4)). ")". "\n";



//print "2. #". $nr2[0]. " (". $nr2[1]. ")". "\n";
//print "3. #". $nr3[0]. " (". $nr3[1]. ")". "\n";
//print "4. #". $nr4[0]. " (". $nr4[1]. ")". "\n";
//print "5. #". $nr5[0]. " (". $nr5[1]. ")". "\n";


?>
