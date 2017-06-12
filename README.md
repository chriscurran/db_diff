Purpose
-------

When developing software you are often working on multiple sql servers - one
for development and testing, the other for production use. While developing
you try to make sure database structure changes are propagated to production
properly (using whatever means you now do), but, as they say "stuff happens". 

Rather than worry about "did I forget something" prior to every production
release, I wrote this simple utility that will connect to two MySQL servers
and compare the tables and structure of those tables. 

Install
-------
To install:
```
   git clone https://github.com/chriscurran/db_diff.git
```

Configuration
-------------

Copy `db_diff_config_sample.php` to `db_diff_config.php`, then edit the values in `db_diff_config.php` to match your servers. The sample config file is below; it's pretty simple.

```
<?php
	//
	// RDS
	//
	$db1_tag = "RDS";
	$db1_host = "127.0.0.1";
	$db1_user = "-- user name here --";
	$db1_pw   = "-- password here --";
	$db1_name = "-- database name here --";

	// 
	// production
	// 
	$db2_tag = "Production";
	$db2_host = "127.0.0.1";
	$db2_user = "-- user name here --";
	$db2_pw   = "-- password here --";
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
```


Usage
-----

The tables for each server are displayed in a two column fashion with the server info displayed at the top of the column. Differences are displayed in either:

* orange - something is different in the table definitions.
* red	 - the item in red does not exist in the other column.

The below example has two tables that have something different: `form` and `sites`:

![image](http://www.planetcurran.com/db_diff/initial_display.png)

Click on a table name and it will expand to show the definition (both columns):

![image](http://www.planetcurran.com/db_diff/form_diff.png)

Again, items in orange are different and items in red are missing. For example, I added a `new_field` to one server:

![image](http://www.planetcurran.com/db_diff/form_diff_col.png)

If a table exists on one server and not the other,it's displayed as such (`new_table` below):

![image](http://www.planetcurran.com/db_diff/diff_table.png)
