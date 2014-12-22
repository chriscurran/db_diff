<?php
	//
	// RDS
	//
	$db1_tag = "RDS";
	$db1_host = "127.0.0.1";
	$db1_user = "-- user name here --";
	$db1_pw   = "-- password here --";
	$db1_name = "libsurveys";

	// 
	// production
	// 
	$db2_tag = "Production";
	$db2_host = "127.0.0.1";
	$db2_user = "-- user name here --";
	$db2_pw   = "-- password here --";
	$db2_name = "libsurveys";


	$cwd = getcwd();
	if (substr($cwd,0,11) == 'D:\ss\tools') {
		$db1_port = 3309;
		$db2_port = 3340;
	}
	else {
		$db1_port = 3306;
		$db2_port = 3306;
	}

	date_default_timezone_set ("UTC");

	/**
	 * set "FILTER" to something if you want to limit what table names are processed.
	 *
	 */
	define("FILTER","");
	define("FILTER_LEN",strlen(FILTER));


?>