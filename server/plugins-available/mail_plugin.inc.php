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

class mail_plugin {
	
	var $plugin_name = 'mail_plugin';
	var $class_name  = 'mail_plugin';
	
	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;
		
		if($conf['services']['mail'] == true) {
			return true;
		} else {
			return false;
		}
		
	}
	
		
	/*
	 	This function is called when the plugin is loaded
	*/
	
	function onLoad() {
		global $app;
		
		/*
		Register for the events
		*/
		
		//* Mailboxes
		$app->plugins->registerEvent('mail_user_insert',$this->plugin_name,'user_insert');
		$app->plugins->registerEvent('mail_user_update',$this->plugin_name,'user_update');
		$app->plugins->registerEvent('mail_user_delete',$this->plugin_name,'user_delete');
		
		//* Mail Domains
		//$app->plugins->registerEvent('mail_domain_insert',$this->plugin_name,'domain_insert');
		//$app->plugins->registerEvent('mail_domain_update',$this->plugin_name,'domain_update');
		$app->plugins->registerEvent('mail_domain_delete',$this->plugin_name,'domain_delete');
		
		//* Mail transports
		$app->plugins->registerEvent('mail_transport_insert',$this->plugin_name,'transport_update');
		$app->plugins->registerEvent('mail_transport_update',$this->plugin_name,'transport_update');
		$app->plugins->registerEvent('mail_transport_delete',$this->plugin_name,'transport_update');
		
	}
	
	
	function user_insert($event_name,$data) {
		global $app, $conf;
		
		//* get the config
		$app->uses("getconf");
		$mail_config = $app->getconf->get_server_config($conf["server_id"], 'mail');
		
		$maildomain_path = $data['new']['maildir'];
		$tmp_basepath = $data['new']['maildir'];
		$tmp_basepath_parts = explode('/',$tmp_basepath);
		unset($tmp_basepath_parts[count($tmp_basepath_parts)-1]);
		$base_path = implode('/',$tmp_basepath_parts);

		//* Create the mail domain directory, if it does not exist
		if(!empty($base_path) && !is_dir($base_path)) {
			exec("su -c 'mkdir -p ".escapeshellcmd($base_path)."' ".$mail_config['mailuser_name']);
			$app->log('Created Directory: '.$base_path,LOGLEVEL_DEBUG);
		}

		//* Create the maildir, if it doesn not exist, set permissions, set quota.
		if(!empty($maildomain_path) && !is_dir($maildomain_path)) {
			exec("su -c 'maildirmake ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name']);
			exec('chown -R '.$mail_config['mailuser_name'].':'.$mail_config['mailuser_group'].' '.escapeshellcmd($data['new']['maildir']));
			$app->log("Set ownership on ".escapeshellcmd($data['new']['maildir']),LOGLEVEL_DEBUG);
			//* This is to fix the maildrop quota not being rebuilt after the quota is changed.
			exec("su -c 'maildirmake -q ".$data['new']['quota']."S ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name']); // Avoid maildirmake quota bug, see debian bug #214911
			$app->log('Created Maildir: '."su -c 'maildirmake -q ".$data['new']['quota']."S ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name'],LOGLEVEL_DEBUG);
		}
		
		//* Set the maildir quota
		exec("su -c 'maildirmake -q ".$data['new']['quota']."S ".escapeshellcmd($data['new']['maildir'])."' ".$mail_config['mailuser_name']);
		$app->log('Set Maildir quota: '."su -c 'maildirmake -q ".$data['new']['quota']."S ".escapeshellcmd($data['new']['maildir'])."' ".$mail_config['mailuser_name'],LOGLEVEL_DEBUG);
	}
	
	function user_update($event_name,$data) {
		global $app, $conf;
		
		// get the config
		$app->uses("getconf");
		$mail_config = $app->getconf->get_server_config($conf["server_id"], 'mail');
		
		// Create the maildir, if it does not exist
		if(!is_dir($data['new']['maildir'])) {
			exec('mkdir -p '.escapeshellcmd($data['new']['maildir']));
			exec('chown '.$mail_config['mailuser_name'].':'.$mail_config['mailuser_group'].' '.escapeshellcmd($data['new']['maildir']));
			$app->log('Created Maildir: '.$data['new']['maildir'],LOGLEVEL_DEBUG);
		}
		
		// Move mailbox, if domain has changed and delete old mailbox
		if($data['new']['maildir'] != $data['old']['maildir'] && is_dir($data['old']['maildir'])) {
			if(is_dir($data['new']['maildir'])) {
				exec("rm -fr ".escapeshellcmd($data['new']['maildir']));
				//rmdir($data['new']['maildir']);
			}
			exec('mv -f '.escapeshellcmd($data['old']['maildir']).' '.escapeshellcmd($data['new']['maildir']));
			// exec('mv -f '.escapeshellcmd($data['old']['maildir']).'/* '.escapeshellcmd($data['new']['maildir']));
			// if(is_file($data['old']['maildir'].'.ispconfig_mailsize'))exec('mv -f '.escapeshellcmd($data['old']['maildir']).'.ispconfig_mailsize '.escapeshellcmd($data['new']['maildir']));
			// rmdir($data['old']['maildir']);
			$app->log('Moved Maildir from: '.$data['old']['maildir'].' to '.$data['new']['maildir'],LOGLEVEL_DEBUG);
		}
		//This is to fix the maildrop quota not being rebuilt after the quota is changed.
		exec("su -c 'maildirmake -q ".$data['new']['quota']."S ".escapeshellcmd($data['new']['maildir'])."' ".$mail_config['mailuser_name']);
		$app->log('Created Maildir: '."su -c 'maildirmake -q ".$data['new']['quota']."S ".escapeshellcmd($data['new']['maildir'])."' ".$mail_config['mailuser_name'],LOGLEVEL_DEBUG);
	}
	
	function user_delete($event_name,$data) {
		global $app, $conf;
		
		$old_maildir_path = escapeshellcmd($data['old']['maildir']);
		if(!stristr($old_maildir_path,'..') && !stristr($old_maildir_path,'*') && strlen($old_maildir_path) >= 10) {
			exec('rm -rf '.escapeshellcmd($old_maildir_path));
			$app->log('Deleted the Maildir: '.$data['old']['maildir'],LOGLEVEL_DEBUG);
		} else {
			$app->log('Possible security violation when deleting the maildir: '.$data['old']['maildir'],LOGLEVEL_ERROR);
		}
	}
	
	function domain_delete($event_name,$data) {
		global $app, $conf;
		
		// get the config
		$app->uses("getconf");
		$mail_config = $app->getconf->get_server_config($conf["server_id"], 'mail');
		
		$old_maildomain_path = escapeshellcmd($mail_config['homedir_path'].'/'.$data['old']['domain']);
		if(!stristr($old_maildomain_path,'..') && !stristr($old_maildomain_path,'*') && strlen($old_maildomain_path) >= 10) {
			exec('rm -rf '.escapeshellcmd($old_maildomain_path));
			$app->log('Deleted the mail domain directory: '.$old_maildomain_path,LOGLEVEL_DEBUG);
		} else {
			$app->log('Possible security violation when deleting the mail domain directory: '.$old_maildomain_path,LOGLEVEL_ERROR);
		}
	}
	
	function transport_update($event_name,$data) {
		global $app, $conf;
		
		exec('/etc/init.d/postfix reload &> /dev/null');
		$app->log('Postfix config reloaded ',LOGLEVEL_DEBUG);
		
	}
	
	
	

} // end class

?>