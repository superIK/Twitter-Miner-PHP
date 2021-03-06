-module(riak_mapred_basic).
-export([count_objects/2, count_words/2, connect/0, clean_bucket/2, print_bucket/2,
          count_map/3, add_int_red/2, words_dict_map/3, merge_dicts_red/2,
          add_some_data/2, sort_result/2, top5_result/2, start/0]).

% Map and reduce functions are executed on Riak nodes.

% Return 1 for every object.
count_map(_G, _KeyData, none) -> [1].

% This function expects the data to be a binary holding text.
words_dict_map(RiakObject, _, _) ->
  [dict:from_list([{I, 1} || I <- binary_to_term(riak_object:get_value(RiakObject))])].

% Reduce function adding integers.
add_int_red(GCounts, none) ->
  [lists:foldl(fun (G, Acc) -> G + Acc end, 0, GCounts)].

% Reduce function for integer-valued dictionaries.
merge_dicts_red(Input, _) ->
  [lists:foldl(
		fun(Tag, Acc) ->
			dict:merge(
				fun(_, Amount1, Amount2) ->
					Amount1 + Amount2
				end,
				Tag,
				Acc
			)
		end,
		dict:new(),
		Input
	)].

connect() ->
  {ok, P} = riakc_pb_socket:start("127.0.0.1", 10017),
  P.

clean_bucket_loop(P, B, Rid) ->
  receive
    {Rid, {keys, Keys}} ->
      lists:map (fun (K) -> riakc_pb_socket:delete(P, B, K) end, Keys),
      clean_bucket_loop(P, B, Rid);
    {Rid, done} -> ok
  end.

clean_bucket(P, B) ->
  {ok, Rid} = riakc_pb_socket:stream_list_keys(P,B),
  clean_bucket_loop(P, B, Rid).

print_metadata(P, B, K) ->
  case riakc_pb_socket:get(P, B, K) of
    {ok, O} ->
      M = riakc_obj:get_metadata(O),
      io:format("~w: ", [K]),
      lists:map (fun ({N, V}) -> io:format("~s -> ~w, ", [N, V]) end, dict:to_list (M)),
      io:format("~n");
    {error, _} -> io:format("~s: not found~n", [K])
  end.

print_bucket_loop(P, B, Rid, N) ->
  receive
    {Rid, {keys, Keys}} ->
      lists:map (fun (K) -> print_metadata(P, B, K) end, Keys),
      print_bucket_loop(P, B, Rid, N+length(Keys));
    {Rid, done} -> N
  end.

% print metadata too
print_bucket(P, B) ->
  {ok, Rid} = riakc_pb_socket:stream_list_keys(P,B),
  print_bucket_loop(P, B, Rid, 0).

% add checking for tombstones!
count_objects(P, Bucket) ->
  X = riakc_pb_socket:mapred(
             P,
             Bucket,
             [{map, {modfun, riak_mapred_basic, count_map}, none, false},
              {reduce, {modfun, riak_mapred_basic, add_int_red}, none, true}]),
  X.

% Count words interpreting values as binaries containging text.
% This function has a bug - can you find it?
count_words(Pid, Keys) ->
  {ok, [{1, [Result]}]} = riakc_pb_socket:mapred(
		Pid,
		Keys,
		[
			{map, {modfun, ?MODULE, words_dict_map}, none, false},
			{reduce, {modfun, ?MODULE, merge_dicts_red}, none, true}
		]
	),
	dict:to_list(Result).

% Add some text in binaries to the bucket (with arbitrary ids)
add_some_data(P, B) ->
  [add_kv(P, B, K, V) || {K, V} <- [{1, <<"word1 word2 word1">>},
                              {2, <<"word3 word4 word5">>},
                              {3, <<"word2 word4 word3">>},
                              {4, <<"word6 word7 word1">>},
				{5, <<"word6 word7 word1">>},
				{6, <<"word6 word7 word1">>}]].

add_kv(P, B, K, V) ->
  Obj = riakc_obj:new(B, list_to_binary(integer_to_list(K)), V),
  riakc_pb_socket:put(P, Obj, [{w, 1}]).


quicksort([]) -> [];
quicksort([{X, Pivot}|Rest]) ->
	{Smaller, Larger} = partition(Pivot,Rest,[],[]),
	quicksort(Smaller) ++ [{X, Pivot}] ++ quicksort(Larger).


partition(_,[], Smaller, Larger) -> {Smaller, Larger};
partition(Pivot, [{X, V}|T], Smaller, Larger) ->
	if V =< Pivot -> partition(Pivot, T, [{X, V}|Smaller], Larger);
	V >  Pivot -> partition(Pivot, T, Smaller, [{X, V}|Larger])
	end.

%sort_result(Pid, Keys) -> lists:reverse(quicksort(count_words(Pid, Keys))).
sort_result(Pid, Keys) -> lists:sublist(lists:reverse(quicksort(count_words(Pid, Keys))),4,5).

len([]) -> 0;
len([_|T]) -> 1 + len(T).

top5(L) ->
	case len(L) of
        0 -> L;
	1 -> L;
	2 -> L;
	3 -> L;
	4 -> L;
	5 -> L;
	6 -> L;
	7 -> L;
	8 -> L;
	_ -> top5(L, [], 0)
	end.

top5(_, Acc, 8) -> Acc;

top5([H|T], Acc, N) -> top5(T, Acc ++[H], N+1).

top5_result(Pid, Keys) -> top5(sort_result(Pid, Keys)).

start() ->
  {ok, Pid} = riakc_pb_socket:start_link("127.0.0.1", 10017),
  Object_Result = top5_result(Pid,<<"hashtags">>),
  file:write_file("/home/pysj/Twitter-Miner-PHP/top5.txt", io_lib:fwrite("~p.\n", [Object_Result])).


