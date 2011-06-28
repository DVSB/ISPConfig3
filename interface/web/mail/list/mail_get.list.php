<?php

/*
	Datatypes:
	- INTEGER
	- DOUBLE
	- CURRENCY
	- VARCHAR
	- TEXT
	- DATE
*/



// Name of the list
$liste["name"] 				= "mail_get";

// Database table
$liste["table"] 			= "mail_get";

// Index index field of the database table
$liste["table_idx"]			= "mailget_id";

// Search Field Prefix
$liste["search_prefix"] 	= "search_";

// Records per page
$liste["records_per_page"] 	= "15";

// Script File of the list
$liste["file"]				= "mail_get_list.php";

// Script file of the edit form
$liste["edit_file"]			= "mail_get_edit.php";

// Script File of the delete script
$liste["delete_file"]		= "mail_get_del.php";

// Paging Template
$liste["paging_tpl"]		= "templates/paging.tpl.htm";

// Enable auth
$liste["auth"]				= "yes";


/*****************************************************
* Suchfelder
*****************************************************/

$liste["item"][] = array(	'field'		=> "active",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "SELECT",
							'op'		=> "=",
							'prefix'	=> "",
							'suffix'	=> "",
							'width'		=> "",
							'value'		=> array('y' => "<div id=\"ir-Yes\" class=\"swap\"><span>Yes</span></div>", 'n' => "<div class=\"swap\" id=\"ir-No\"><span>No</span></div>"));


$liste["item"][] = array(	'field'		=> "server_id",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "SELECT",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'datasource'	=> array ( 	'type'	=> 'SQL',
														'querystring' => 'SELECT server_id,server_name FROM server WHERE {AUTHSQL} ORDER BY server_name',
														'keyfield'=> 'server_id',
														'valuefield'=> 'server_name'
									 				  ),
							'width'		=> "",
							'value'		=> "");

$liste["item"][] = array(	'field'		=> "source_server",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "TEXT",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'width'		=> "",
							'value'		=> "");

$liste["item"][] = array(	'field'		=> "source_username",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "TEXT",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'width'		=> "",
							'value'		=> "");

$liste["item"][] = array(	'field'		=> "destination",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "TEXT",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'width'		=> "",
							'value'		=> "");


?>