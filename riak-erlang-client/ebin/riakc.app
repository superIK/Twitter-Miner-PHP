{application,riakc,
             [{description,"Riak Client"},
              {vsn,"1.4.1-200-gb96d050"},
              {applications,[kernel,stdlib,riak_pb]},
              {registered,[]},
              {env,[{timeout,60000}]},
              {modules,[riakc_counter,riakc_datatype,riakc_flag,riakc_map,
                        riakc_obj,riakc_pb_socket,riakc_register,riakc_set]}]}.
