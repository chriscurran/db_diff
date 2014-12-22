<?php
	//
	// server 1
	//
	$db1_tag = "RDS";
	$db1_host = "127.0.0.1";
	$db1_user = "-- sql user name here --";
	$db1_pw   = "-- sql password here --";
	$db1_name = "-- database name here --";

	// 
	// server 2
	// 
	$db2_tag = "Production";
	$db2_host = "127.0.0.1";
	$db2_user = "-- sql user name here --";
	$db2_pw   = "-- sql password here --";
	$db2_name = "-- database name here --";

	// 
	// if I'm running this on my dev machine I using ssh tunnels to get to the 
	// sql servers, so, I need to use different port numbers here (the ones I'm tunneling into) to 
	// connect to the server.
	//
	$cwd = getcwd();
	if (substr($cwd,0,8) == 'D:\tools') {
		// Use ssh tunnel port numbers. Yours will be different depending on how you setup your tunnel.
		$db1_port = 3309;
		$db2_port = 3340;
	}
	else {
		// not tunneling - use normal mysql port numbers.
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