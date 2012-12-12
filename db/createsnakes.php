<?php

require( "../../../config.php");


execute_sql("truncate TABLE {game_snakes_database}");

require( "importsnakes.php");
