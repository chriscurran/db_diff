<!DOCTYPE html>
<HTML>

<HEAD>	
	<meta charset="utf-8">
	<title>db Diff</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="Database difference tool">
	<meta name="author" content="Chris Curran">

	<link href="//netdna.bootstrapcdn.com/bootstrap/3.0.3/css/bootstrap.min.css" rel="stylesheet">

<style>

	.accordionDiv {
		float: left;
		width: 49%;
		overflow-y: auto;
	}

	.accordionHeader {
		float: left;
		width: 49%;

		margin-top: 10px;
		margin-bottom: 4px;
	}

	.accordionHeader h4 {
		display: inline;
		font-size: 18px;
	}


	.accordionDiv table {
	  font-size:10px;
	}

	.tbl-same {
		color: black;
	}
	.tbl-different {
		color: orange;
	}
	.tbl-missing {
		color: red;
	}

	.row-same {
		color: black;
	}
	.row-different {
		color: orange;
	}
	.row-missing {
		color: red;
	}

</style>

<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript" src="//netdna.bootstrapcdn.com/bootstrap/3.0.3/js/bootstrap.min.js"></script>

</HEAD>

<BODY>
<?php	

	require("db_diff_config.php");

	/**
	 * class to encapsulate the mysql row info for a db table
	 *
	 */
	class tblRowInfoT {
		public $Field;
		public $Type;
		public $Null;
		public $Key;
		public $Default;
		public $Extra;
		public $dspClass;


		function __construct() {
			$this->dspClass = 'row-same';
			$this->normalize();
		}

		function normalize() {
			if ($this->Default === '')
				$this->Default = "''";
			else if ($this->Default == null)
				$this->Default = 'Null';
		}

	}


	//
	// connect to the db
	//
	$DB1 = new mysqli($db1_host, $db1_user, $db1_pw, $db1_name, $db1_port);
	if ($DB1->connect_error) {
   		die("{$db1_host} connect Error ({$DB1->connect_errno}) {$DB1->connect_error}");
	}

	$DB2 = new mysqli($db2_host, $db2_user, $db2_pw, $db2_name, $db2_port);
	if ($DB2->connect_error) {
   		die("{$db2_host} connect Error ({$DB2->connect_errno}) {$DB2->connect_error}");
	}


	// 
	// get tables on srv1
	// 
	if (!($result = $DB1->query("SHOW TABLES")))
		die($DB1->error);

	$srv1_tables = array();
	while ($obj = $result->fetch_object()) {
		$lbl = "Tables_in_{$db1_name}";
		$tblName = $obj->$lbl;
		
		if (FILTER_LEN > 0) {
			if ( substr($tblName,0,FILTER_LEN) !== FILTER )
				continue;
		}

		$srv1_tables[$tblName] = getTableDetails($DB1, $tblName);
	}
	$result->close();


	// 
	// get tables on srv2
	// 
	if (!($result = $DB2->query("SHOW TABLES")))
		die($DB2->error);

	$srv2_tables = array();
	while ($obj = $result->fetch_object()) {
		$lbl = "Tables_in_{$db2_name}";
		$tblName = $obj->$lbl;

		if (FILTER_LEN > 0) {
			if ( substr($tblName,0,FILTER_LEN) !== FILTER )
				continue;
		}

		$srv2_tables[$tblName] = getTableDetails($DB2, $tblName);
	}
	$result->close();

	// 
	// display the stuff
	// 
	echo "<div class='accordionHeader'><h4><span class='label label-primary'>$db1_tag, $db1_host, $db1_port, $db1_name</span></h4> (click a table name to show detail)</div>",
		 "<div class='accordionHeader'><h4><span class='label label-primary'>$db2_tag, $db2_host, $db2_port, $db2_name</span></h4> (click a table name to show detail)</div>";


	echo dspServer('1', $srv1_tables, $srv2_tables);
	echo dspServer('2', $srv2_tables, $srv1_tables);


?>
	<script>

	function sync(tbl, me_id, other_id) {
		// put table at top of display
		$('#panel_' + tbl + me_id)[0].scrollIntoView(true);

		// hide any open areas in other side if not same table
		var obj = $('#accordion'+other_id+' .in');
		if (obj.data('tbl') !== tbl) {
			obj.collapse('hide');
		}

		// show same table on other server
		var other = $("[data-tbl='"+tbl+"'][data-tag='"+other_id+"']");
		other.collapse('show');
	}


	$( document ).ready(function() {
		var viewportHeight = $(window).height();
		var h = (viewportHeight - 40) + "px";

		$('#accordion1').height(h);
		$('#accordion2').height(h);

		$('#accordion1').on('shown.bs.collapse', function(ev) {
			var tbl = $(ev.target).data('tbl');
			sync(tbl,'1','2');
		});

		$('#accordion2').on('shown.bs.collapse', function(ev) {
			var tbl = $(ev.target).data('tbl');
			sync(tbl,'2','1');
		});
	});


	</script>

<?php

	// 
	// all done
	// 
	echo "</BODY>";
	echo "</HTML>";

	exit();


/**
 * get the table row records
 *
 */
function getTableDetails($DB, $table_name) {
	//
	// get the table description
	//
	if (!($result = $DB->query("DESCRIBE $table_name")))
		die($DB->error);

	$description_obj = array();
	while ($obj = $result->fetch_object("tblRowInfoT")) {
		$description_obj[$obj->Field] = $obj;
	}

	return $description_obj;
}


/**
 * compare structure of tables on both servers
 *
 * note: this isn't optimized - it gets called twice and probably doesn't need to be if 
 *		 the calling sequence was restructured. However, considering this isn't production code
 *		 and it works now, well, to heck with it.
 */
function compareTables($tblName) {
	global $srv1_tables,$srv2_tables;
	
	$tblInfo1 = $srv1_tables[$tblName];
	$tblInfo2 = $srv2_tables[$tblName];

	$is_diff = false;

	// chk tbl1 first
	foreach ($tblInfo1 as $k => $r1) {
		if ( empty($tblInfo2[$k]) ) {
			$r1->dspClass = 'row-missing';
			$is_diff = true;
		}
		else {
			$r2 = $tblInfo2[$k];
			if (($r1->Field !== $r2->Field) ||
				($r1->Type !== $r2->Type) ||
				($r1->Null !== $r2->Null) ||
				($r1->Key !== $r2->Key) ||
				($r1->Default !== $r2->Default) ||
				($r1->Extra !== $r2->Extra)) {

				$r1->dspClass = 'row-different';
				$is_diff = true;
			}
		}

	}

	// chk tbl2 next
	foreach ($tblInfo2 as $k => $r2) {
		if ( empty($tblInfo1[$k]) ) {
			$r2->dspClass = 'row-missing';
			$is_diff = true;
		}
		else {
			$r1 = $tblInfo1[$k];
			if (($r2->Field !== $r1->Field) ||
				($r2->Type !== $r1->Type) ||
				($r2->Null !== $r1->Null) ||
				($r2->Key !== $r1->Key) ||
				($r2->Default !== $r1->Default) ||
				($r2->Extra !== $r1->Extra)) {

				$r2->dspClass = 'row-different';
				$is_diff = true;
			}
		}
	}


	if ($is_diff)
		return 'tbl-different';
	return 'tbl-same';
}

/**
 * display the db tables (and the table details) for a given server
 *
 */
function dspServer($tag, $srv_tables, $other) {
	$divId = "accordion{$tag}";
	$html = '';


	$html = "<div class='panel-group accordionDiv' id='{$divId}'>";
	foreach($srv_tables as $tblName => $tblInfo) {

		if ( empty($other[$tblName]) )
			$tblClass = 'tbl-missing';
		else
			$tblClass = compareTables($tblName);

		// 
		// Build the db table info record. This is not displayed unless the user clicks
		// on the bootstrap accordion thingy
		//
		$detail = "<table width='100%'>".
				  "<tr>".
				  "<th>Name</th>".
				  "<th>Type</th>".
				  "<th>Null</th>".
				  "<th>Key</th>".
				  "<th>Default</th>".
				  "<th>Extra</th>".
				  "</tr>";

		foreach($tblInfo as $tblRow) {
			$detail .= "<tr class='{$tblRow->dspClass}'>";
			$detail .= "<td>$tblRow->Field</td><td>$tblRow->Type</td><td>$tblRow->Null</td><td>$tblRow->Key</td><td>$tblRow->Default</td><td>$tblRow->Extra</td>";
			$detail .= "</tr>";
		}
		$detail .= "</table>";

		// 
		// this is for one db table and its description ($tblName and $detail)
		// 
		$html .="<div class='panel panel-default' id='panel_{$tblName}{$tag}'>
					<div class='panel-heading'>
						<h4 class='panel-title {$tblClass}'>
							<a data-toggle='collapse' data-parent='#{$divId}' href='#collapse_{$tblName}{$tag}'>
								{$tblName}
							</a>
						</h4>
					</div>
					<div id='collapse_{$tblName}{$tag}' data-tbl='{$tblName}' data-tag='{$tag}' class='panel-collapse collapse'>
						<div class='panel-body'>
							{$detail}
						</div>
					</div>
				</div>";

	}

	$html .= "</div>";
	return $html;
}