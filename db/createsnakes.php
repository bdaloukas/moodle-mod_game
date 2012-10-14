<?php

require( "../../../config.php");


execute_sql( "truncate TABLE {$CFG->prefix}game_snakes_database");

require( "importsnakes.php");
