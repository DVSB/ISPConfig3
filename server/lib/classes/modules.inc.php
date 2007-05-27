<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

class modules {
	
	var $notification_hooks = array();
	
	/*
	 This function is called to load the modules from the mods-available folder
	*/
	
	function loadModules() {
		global $app, $conf;
		
		$modules_dir = $conf["rootpath"].$conf["fs_div"]."lib".$conf["fs_div"]."mods-enabled".$conf["fs_div"]
		
		if (is_dir($modules_dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if($file != '.' && $file != '..') {
						$module_name = substr($file,0,-8);
						include_once($modules_dir.$file);
						$app->log("Loading Module: $module_name",LOGLEVEL_DEBUG);
						$app->modules[$module_name] = new $module_name;
						$app->modules[$module_name]->onLoad();
					}
				}
			}
		} else {
			$app->log("Modules directory missing: $modules_dir",LOGLEVEL_ERROR);
		}
	}
	
	/*
	 This function is called by the modules to register for a specific
	 table change notification
	*/
	
	function registerTableHook($table_name,$module_name,$function_name) {
		$this->notification_hooks[$table_name][] = array('module' => $module_name, 'function' => $function_name);
	}
	
	/*
	 This function goes through all new records in the
	 sys_datalog table and and calls the function in the
	 modules that hooked on to the table change.
	*/
	
	function processDatalog() {
		global $app;
		
		// TODO: process only new entries.
		$sql = "SELECT * FROM sys_datalog WHERE 1";
		$records = $app->db->queryAllRecords($sql);
		foreach($records as $rec) {
			$data = unserialize(stripslashes($rec["data"]));
			$this->raiseTableHook($rec["dbtable"],$rec["action"],$data);
		}
	}
	
	function raiseTableHook($table_name,$action,$data) {
		global $app;
		
		// Get the hooks for this table
		$hooks = $this->notification_hooks[$table_name];
		
		if(is_array($hooks)) {
			foreach($hooks as $hook) {
				$module_name = $hook["module"];
				$function_name = $hook["function"];
				// Claa the processing function of the module
				call_user_method($function_name,$app->modules[$module_name],$table_name,$action,$data);
				unset($module_name);
				unset($function_name);
			}
		}
		unset($hook);
		unset($hooks);
	}
	
}

?>