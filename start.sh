 #!/bin/bash
# Starting the Twitter-stream
/usr/bin/php stream.php &

# Make it run for 2 minutes
sleep 1m

# Killing the Twitter-Miner
for KILLPID in `ps ax | grep 'stream.php' | awk ' { print $1;}'`; do 
kill -9 $KILLPID;
done

# Starting the parser
/usr/bin/php parse.php

#Running the mapreduce to produce top5.txt
erl -pa riak-erlang-client/ebin/ riak-erlang-client/deps/*/ebin -s riak_mapred_basic -s init stop -s start -noshell

# Deleting the bucket
/usr/bin/php del_bucket.php

# Showing the top5
clear
/usr/bin/php showtop5.php


echo "Completed!"
