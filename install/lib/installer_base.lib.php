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

class installer_base {
	
	var $wb = array();
	var $language = 'en';
	var $db;
	
	/*
	
	*/
	
	function lng() {
		
	}
	
	function error($msg) {
		die("ERROR: ".$msg."\n");
	}
	
	function request_language() {
		
		swriteln(lng('Enter your language'));
		swriteln(lng('de, en'));
		
		/*
		do {
			$lang = sread(2);
		} while (!$this->check_break($lang) or $this-> 
		*/
		
		
	}
	
	/*
		This function creates the database for ISPConfig
	*/
	
	function configure_database() {
		global $conf;
		
		// Create the database
		if(!$this->db->query("CREATE DATABASE IF NOT EXISTS ".$conf["mysql_server_database"])) {
			$this->error('Unable to create MySQL database: '.$conf["mysql_server_database"].';');
		}
		
		// Create the ISPConfig database user
		if(!$this->db->query("GRANT SELECT, INSERT, UPDATE, DELETE ON ".$conf["mysql_server_database"].".* TO '".$conf["mysql_server_ispconfig_user"]."'@'".$conf["mysql_server_host"]."' IDENTIFIED BY '".$conf["mysql_server_ispconfig_password"]."';")) {
			$this->error('Unable to create database user: '.$conf["mysql_server_ispconfig_user"]);
		}
		
		// Reload database priveliges
		$this->db->query('FLUSH PRIVILEGES;');
		
		// Set the database name in the DB library
		$this->db->dbName = $conf["mysql_server_database"];
		
		// loading the database dump into the database, if database is empty
		$db_tables = $this->db->getTables();
		if(count($db_tables) > 0) {
			$this->error('Stopped: Database contains already some tables.');
		} else {
			if($conf["mysql_server_admin_password"] == '') {
				caselog("mysql -h '".$conf["mysql_server_host"]."' -u '".$conf["mysql_server_admin_user"]."' '".$conf["mysql_server_database"]."' < 'sql/ispconfig3.sql' &> /dev/null", $FILE, __LINE__,"read in ispconfig3.sql","could not read in ispconfig3.sql");
			} else {
				caselog("mysql -h '".$conf["mysql_server_host"]."' -u '".$conf["mysql_server_admin_user"]."' -p'".$conf["mysql_server_admin_password"]."' '".$conf["mysql_server_database"]."' < 'sql/ispconfig3.sql' &> /dev/null", $FILE, __LINE__,"read in ispconfig3.sql","could not read in ispconfig3.sql");
			}
			$db_tables = $this->db->getTables();
			if(count($db_tables) == 0) {
				$this->error('Unable to load SQL-Dump into database table.');
			}
		}
	}
	
	/*
		Create postfix configuration files
	*/
	
	function configure_postfix($options = '') {
		global $conf;
		
		if(!is_dir($conf["dist_postfix_config_dir"])) $this->error("The postfix configuration directory ".$conf["dist_postfix_config_dir"]." does not exist.");
		
		// mysql-virtual_domains.cf
		$configfile = 'mysql-virtual_domains.cf';
		if(is_file($conf["dist_postfix_config_dir"].'/'.$configfile)) copy($conf["dist_postfix_config_dir"].'/'.$configfile,$conf["dist_postfix_config_dir"].'/'.$configfile.'~');
		$content = rf("tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}',$conf["mysql_server_ispconfig_user"],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$conf["mysql_server_ispconfig_password"],$content);
		$content = str_replace('{mysql_server_database}',$conf["mysql_server_database"],$content);
		$content = str_replace('{mysql_server_ip}',$conf["mysql_server_ip"],$content);
		wf($conf["dist_postfix_config_dir"].'/'.$configfile,$content);
		
		// mysql-virtual_forwardings.cf
		$configfile = 'mysql-virtual_forwardings.cf';
		if(is_file($conf["dist_postfix_config_dir"].'/'.$configfile)) copy($conf["dist_postfix_config_dir"].'/'.$configfile,$conf["dist_postfix_config_dir"].'/'.$configfile.'~');
		$content = rf("tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}',$conf["mysql_server_ispconfig_user"],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$conf["mysql_server_ispconfig_password"],$content);
		$content = str_replace('{mysql_server_database}',$conf["mysql_server_database"],$content);
		$content = str_replace('{mysql_server_ip}',$conf["mysql_server_ip"],$content);
		wf($conf["dist_postfix_config_dir"].'/'.$configfile,$content);
		
		// mysql-virtual_mailboxes.cf
		$configfile = 'mysql-virtual_mailboxes.cf';
		if(is_file($conf["dist_postfix_config_dir"].'/'.$configfile)) copy($conf["dist_postfix_config_dir"].'/'.$configfile,$conf["dist_postfix_config_dir"].'/'.$configfile.'~');
		$content = rf("tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}',$conf["mysql_server_ispconfig_user"],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$conf["mysql_server_ispconfig_password"],$content);
		$content = str_replace('{mysql_server_database}',$conf["mysql_server_database"],$content);
		$content = str_replace('{mysql_server_ip}',$conf["mysql_server_ip"],$content);
		wf($conf["dist_postfix_config_dir"].'/'.$configfile,$content);
		
		// mysql-virtual_email2email.cf
		$configfile = 'mysql-virtual_email2email.cf';
		if(is_file($conf["dist_postfix_config_dir"].'/'.$configfile)) copy($conf["dist_postfix_config_dir"].'/'.$configfile,$conf["dist_postfix_config_dir"].'/'.$configfile.'~');
		$content = rf("tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}',$conf["mysql_server_ispconfig_user"],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$conf["mysql_server_ispconfig_password"],$content);
		$content = str_replace('{mysql_server_database}',$conf["mysql_server_database"],$content);
		$content = str_replace('{mysql_server_ip}',$conf["mysql_server_ip"],$content);
		wf($conf["dist_postfix_config_dir"].'/'.$configfile,$content);
		
		// mysql-virtual_transports.cf
		$configfile = 'mysql-virtual_transports.cf';
		if(is_file($conf["dist_postfix_config_dir"].'/'.$configfile)) copy($conf["dist_postfix_config_dir"].'/'.$configfile,$conf["dist_postfix_config_dir"].'/'.$configfile.'~');
		$content = rf("tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}',$conf["mysql_server_ispconfig_user"],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$conf["mysql_server_ispconfig_password"],$content);
		$content = str_replace('{mysql_server_database}',$conf["mysql_server_database"],$content);
		$content = str_replace('{mysql_server_ip}',$conf["mysql_server_ip"],$content);
		wf($conf["dist_postfix_config_dir"].'/'.$configfile,$content);
		
		// mysql-virtual_recipient.cf
		$configfile = 'mysql-virtual_recipient.cf';
		if(is_file($conf["dist_postfix_config_dir"].'/'.$configfile)) copy($conf["dist_postfix_config_dir"].'/'.$configfile,$conf["dist_postfix_config_dir"].'/'.$configfile.'~');
		$content = rf("tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}',$conf["mysql_server_ispconfig_user"],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$conf["mysql_server_ispconfig_password"],$content);
		$content = str_replace('{mysql_server_database}',$conf["mysql_server_database"],$content);
		$content = str_replace('{mysql_server_ip}',$conf["mysql_server_ip"],$content);
		wf($conf["dist_postfix_config_dir"].'/'.$configfile,$content);
		
		// mysql-virtual_sender.cf
		$configfile = 'mysql-virtual_sender.cf';
		if(is_file($conf["dist_postfix_config_dir"].'/'.$configfile)) copy($conf["dist_postfix_config_dir"].'/'.$configfile,$conf["dist_postfix_config_dir"].'/'.$configfile.'~');
		$content = rf("tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}',$conf["mysql_server_ispconfig_user"],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$conf["mysql_server_ispconfig_password"],$content);
		$content = str_replace('{mysql_server_database}',$conf["mysql_server_database"],$content);
		$content = str_replace('{mysql_server_ip}',$conf["mysql_server_ip"],$content);
		wf($conf["dist_postfix_config_dir"].'/'.$configfile,$content);
		
		// mysql-virtual_client.cf
		$configfile = 'mysql-virtual_client.cf';
		if(is_file($conf["dist_postfix_config_dir"].'/'.$configfile)) copy($conf["dist_postfix_config_dir"].'/'.$configfile,$conf["dist_postfix_config_dir"].'/'.$configfile.'~');
		$content = rf("tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}',$conf["mysql_server_ispconfig_user"],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$conf["mysql_server_ispconfig_password"],$content);
		$content = str_replace('{mysql_server_database}',$conf["mysql_server_database"],$content);
		$content = str_replace('{mysql_server_ip}',$conf["mysql_server_ip"],$content);
		wf($conf["dist_postfix_config_dir"].'/'.$configfile,$content);
		
		// Changing mode and group of the new created config files.
		caselog("chmod o= ".$conf["dist_postfix_config_dir"]."/mysql-virtual_*.cf* &> /dev/null", __FILE__, __LINE__,"chmod on mysql-virtual_*.cf*","chmod on mysql-virtual_*.cf* failed");
		caselog("chgrp ".$conf["dist_postfix_groupname"]." ".$conf["dist_postfix_config_dir"]."/mysql-virtual_*.cf* &> /dev/null", __FILE__, __LINE__,"chgrp on mysql-virtual_*.cf*","chgrp on mysql-virtual_*.cf* failed");
		
		// Creating virtual mail user and group
		$command = "groupadd -g ".$conf["dist_postfix_vmail_groupid"]." ".$conf["dist_postfix_vmail_groupname"];
		caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);
		
		$command = "useradd -g ".$conf["dist_postfix_vmail_groupname"]." -u ".$conf["dist_postfix_vmail_userid"]." ".$conf["dist_postfix_vmail_username"]." -d ".$conf["dist_postfix_vmail_mailbox_base"]." -m";
		caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);		

		$postconf_commands = array (
			'myhostname = '.$conf["hostname"],
			'mydestination = '.$conf["hostname"].', localhost, localhost.localdomain',
			'mynetworks = 127.0.0.0/8',
			'virtual_alias_domains =',
			'virtual_alias_maps = proxy:mysql:'.$conf["dist_postfix_config_dir"].'/mysql-virtual_forwardings.cf, mysql:'.$conf["dist_postfix_config_dir"].'/mysql-virtual_email2email.cf',
			'virtual_mailbox_domains = proxy:mysql:'.$conf["dist_postfix_config_dir"].'/mysql-virtual_domains.cf',
			'virtual_mailbox_maps = proxy:mysql:'.$conf["dist_postfix_config_dir"].'/mysql-virtual_mailboxes.cf',
			'virtual_mailbox_base = '.$conf["dist_postfix_vmail_mailbox_base"],
			'virtual_uid_maps = static:'.$conf["dist_postfix_vmail_userid"],
			'virtual_gid_maps = static:'.$conf["dist_postfix_vmail_groupid"],
			'smtpd_sasl_auth_enable = yes',
			'broken_sasl_auth_clients = yes',
			'smtpd_recipient_restrictions = permit_mynetworks, permit_sasl_authenticated, check_recipient_access mysql:'.$conf["dist_postfix_config_dir"].'/mysql-virtual_recipient.cf, reject_unauth_destination',
			'smtpd_use_tls = yes',
			'smtpd_tls_cert_file = '.$conf["dist_postfix_config_dir"].'/smtpd.cert',
			'smtpd_tls_key_file = '.$conf["dist_postfix_config_dir"].'/smtpd.key',
			'transport_maps = proxy:mysql:'.$conf["dist_postfix_config_dir"].'/mysql-virtual_transports.cf',
			'virtual_create_maildirsize = yes',
			'virtual_mailbox_extended = yes',
			'virtual_mailbox_limit_maps = proxy:mysql:'.$conf["dist_postfix_config_dir"].'/mysql-virtual_mailbox_limit_maps.cf',
			'virtual_mailbox_limit_override = yes',
			'virtual_maildir_limit_message = "The user you are trying to reach is over quota."',
			'virtual_overquota_bounce = yes',
			'proxy_read_maps = $local_recipient_maps $mydestination $virtual_alias_maps $virtual_alias_domains $virtual_mailbox_maps $virtual_mailbox_domains $relay_recipient_maps $relay_domains $canonical_maps $sender_canonical_maps $recipient_canonical_maps $relocated_maps $transport_maps $mynetworks $virtual_mailbox_limit_maps',
			'smtpd_sender_restrictions = check_sender_access mysql:'.$conf["dist_postfix_config_dir"].'/mysql-virtual_sender.cf',
			'smtpd_client_restrictions = check_client_access mysql:'.$conf["dist_postfix_config_dir"].'/mysql-virtual_client.cf',
			'maildrop_destination_concurrency_limit = 1',
			'maildrop_destination_recipient_limit   = 1',
			'virtual_transport = maildrop'
		);
		
		// Make a backup copy of the main.cf file
		copy($conf["dist_postfix_config_dir"].'/main.cf',$conf["dist_postfix_config_dir"].'/main.cf~');
		
		// Executing the postconf commands
		foreach($postconf_commands as $cmd) {
			$command = "postconf -e '$cmd'";
			caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);
		}
		
		// TODO: Change the master.cf file
		/*
		Add:
maildrop  unix  -       n       n       -       -       pipe
  flags=R user=vmail argv=/usr/bin/maildrop -d ${recipient} ${extension} ${recipient} ${user} ${nexthop} ${sender}
		
		*/
		if(!stristr($options,'dont-create-certs')) {
			// Create the SSL certificate
			$command = "cd ".$conf["dist_postfix_config_dir"]."; openssl req -new -outform PEM -out smtpd.cert -newkey rsa:2048 -nodes -keyout smtpd.key -keyform PEM -days 365 -x509";
			exec($command);
		
			$command = "chmod o= ".$conf["dist_postfix_config_dir"]."/smtpd.key";
			caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);
		}
		
		/*
		We have to change the permissions of the courier authdaemon directory
		to make it accessible for maildrop.
		*/
		
		$command = "chmod 755  /var/run/courier/authdaemon/";
		caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);
		
		// Changing maildrop lines in posfix master.cf
		if(is_file($conf["dist_postfix_config_dir"].'/master.cf')) copy($conf["dist_postfix_config_dir"].'/master.cf',$conf["dist_postfix_config_dir"].'/master.cf~');
		if(is_file($conf["dist_postfix_config_dir"].'/master.cf~')) exec('chmod 400 '.$conf["dist_postfix_config_dir"].'/master.cf~');
		$configfile = $conf["dist_postfix_config_dir"].'/master.cf';
		$content = rf($configfile);
		$content = str_replace('  flags=DRhu user=vmail argv=/usr/bin/maildrop -d ${recipient}','  flags=R user='.$conf["dist_postfix_vmail_username"].' argv=/usr/bin/maildrop -d ${recipient} ${extension} ${recipient} ${user} ${nexthop} ${sender}',$content);
		wf($configfile,$content);
		
		// Writing the Maildrop mailfilter file
		$configfile = 'mailfilter';
		if(is_file($conf["dist_postfix_vmail_mailbox_base"].'/.'.$configfile)) copy($conf["dist_postfix_vmail_mailbox_base"].'/.'.$configfile,$conf["dist_postfix_vmail_mailbox_base"].'/.'.$configfile.'~');
		$content = rf("tpl/".$configfile.".master");
		$content = str_replace('{dist_postfix_vmail_mailbox_base}',$conf["dist_postfix_vmail_mailbox_base"],$content);
		wf($conf["dist_postfix_vmail_mailbox_base"].'/.'.$configfile,$content);
		
		// Create the directory for the custom mailfilters
		$command = "mkdir ".$conf["dist_postfix_vmail_mailbox_base"]."/mailfilters";
		caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);
		
		// Chmod and chown the .mailfilter file
		$command = "chown -R ".$conf["dist_postfix_vmail_username"].":".$conf["dist_postfix_vmail_groupname"]." ".$conf["dist_postfix_vmail_mailbox_base"]."/.mailfilter";
		caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);
		
		$command = "chmod -R 600 ".$conf["dist_postfix_vmail_mailbox_base"]."/.mailfilter";
		caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);
		
		
		
	}
	
	function configure_saslauthd() {
		global $conf;
		
	
		$configfile = 'sasl_smtpd.conf';
		if(is_file($conf["dist_postfix_config_dir"].'/sasl/smtpd.conf')) copy($conf["dist_postfix_config_dir"].'/sasl/smtpd.conf',$conf["dist_postfix_config_dir"].'/sasl/smtpd.conf~');
		if(is_file($conf["dist_postfix_config_dir"].'/sasl/smtpd.conf~')) exec('chmod 400 '.$conf["dist_postfix_config_dir"].'/sasl/smtpd.conf~');
		$content = rf("tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}',$conf["mysql_server_ispconfig_user"],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$conf["mysql_server_ispconfig_password"],$content);
		$content = str_replace('{mysql_server_database}',$conf["mysql_server_database"],$content);
		$content = str_replace('{mysql_server_ip}',$conf["mysql_server_ip"],$content);
		wf($conf["dist_postfix_config_dir"].'/sasl/smtpd.conf',$content);
		
		// TODO: Chmod and chown on the config file
		
		
		
		// Create the spool directory
		exec("mkdir -p /var/spool/postfix/var/run/saslauthd");
		
		// Edit the file /etc/default/saslauthd
		$configfile = '/etc/default/saslauthd';
		if(is_file($configfile)) copy($configfile,$configfile.'~');
		if(is_file($configfile.'~')) exec('chmod 400 '.$configfile.'~');
		$content = rf($configfile);
		$content = str_replace('START=no','START=yes',$content);
		$content = str_replace('OPTIONS="-c"','OPTIONS="-m /var/spool/postfix/var/run/saslauthd -r"',$content);
		wf($configfile,$content);
		
		// Edit the file /etc/default/saslauthd
		$configfile = '/etc/init.d/saslauthd';
		$content = rf($configfile);
		$content = str_replace('PIDFILE=$RUN_DIR/saslauthd.pid','PIDFILE="/var/spool/postfix/var/run/${NAME}/saslauthd.pid"',$content);
		wf($configfile,$content);
		
		
	}
	
	function configure_pam() {
		global $conf;
		
		// configure pam for SMTP authentication agains the ispconfig database
		$configfile = 'pamd_smtp';
		if(is_file('/etc/pam.d/smtp')) copy('/etc/pam.d/smtp','/etc/pam.d/smtp~');
		if(is_file('/etc/pam.d/smtp~')) exec('chmod 400 /etc/pam.d/smtp~');
		$content = rf("tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}',$conf["mysql_server_ispconfig_user"],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$conf["mysql_server_ispconfig_password"],$content);
		$content = str_replace('{mysql_server_database}',$conf["mysql_server_database"],$content);
		$content = str_replace('{mysql_server_ip}',$conf["mysql_server_ip"],$content);
		wf('/etc/pam.d/smtp',$content);
		exec('chmod 660 /etc/pam.d/smtp');
		exec('chown daemon:daemon /etc/pam.d/smtp');
	
	}
	
	function configure_courier() {
		global $conf;
		
		// authmysqlrc
		$configfile = 'authmysqlrc';
		if(is_file($conf["dist_courier_config_dir"].'/'.$configfile)) copy($conf["dist_courier_config_dir"].'/'.$configfile,$conf["dist_courier_config_dir"].'/'.$configfile.'~');
		exec('chmod 400 '.$conf["dist_courier_config_dir"].'/'.$configfile.'~');
		$content = rf("tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}',$conf["mysql_server_ispconfig_user"],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$conf["mysql_server_ispconfig_password"],$content);
		$content = str_replace('{mysql_server_database}',$conf["mysql_server_database"],$content);
		$content = str_replace('{mysql_server_host}',$conf["mysql_server_host"],$content);
		wf($conf["dist_courier_config_dir"].'/'.$configfile,$content);
		
		exec('chmod 660 '.$conf["dist_courier_config_dir"].'/'.$configfile);
		exec('chown daemon:daemon '.$conf["dist_courier_config_dir"].'/'.$configfile);
		
		//authdaemonrc
		$configfile = $conf["dist_courier_config_dir"].'/authdaemonrc';
		if(is_file($configfile)) copy($configfile,$configfile.'~');
		if(is_file($configfile.'~')) exec('chmod 400 '.$configfile.'~');
		$content = rf($configfile);
		$content = str_replace('authmodulelist="authpam"','authmodulelist="authmysql"',$content);
		wf($configfile,$content);
		
		
	}
	
	function configure_amavis() {
		global $conf;
		
		// amavisd user config file
		$configfile = 'amavisd_user_config';
		if(is_file($conf["dist_amavis_config_dir"].'/conf.d/50-user')) copy($conf["dist_amavis_config_dir"].'/conf.d/50-user',$conf["dist_courier_config_dir"].'/50-user~');
		if(is_file($conf["dist_amavis_config_dir"].'/conf.d/50-user~')) exec('chmod 400 '.$conf["dist_amavis_config_dir"].'/conf.d/50-user~');
		$content = rf("tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}',$conf["mysql_server_ispconfig_user"],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$conf["mysql_server_ispconfig_password"],$content);
		$content = str_replace('{mysql_server_database}',$conf["mysql_server_database"],$content);
		$content = str_replace('{mysql_server_port}',$conf["mysql_server_port"],$content);
		$content = str_replace('{mysql_server_ip}',$conf["mysql_server_ip"],$content);
		wf($conf["dist_amavis_config_dir"].'/conf.d/50-user',$content);
		
		// TODO: chmod and chown on the config file
		
		
		// Adding the amavisd commands to the postfix configuration
		$postconf_commands = array (
			'content_filter = amavis:[127.0.0.1]:10024',
			'receive_override_options = no_address_mappings'
		);
		
		// Make a backup copy of the main.cf file
		copy($conf["dist_postfix_config_dir"].'/main.cf',$conf["dist_postfix_config_dir"].'/main.cf~2');
		
		// Executing the postconf commands
		foreach($postconf_commands as $cmd) {
			$command = "postconf -e '$cmd'";
			caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);
		}
		
		// Append the configuration for amavisd to the master.cf file
		if(is_file($conf["dist_postfix_config_dir"].'/master.cf')) copy($conf["dist_postfix_config_dir"].'/master.cf',$conf["dist_postfix_config_dir"].'/master.cf~');
		$content = rf("tpl/master_cf_amavis.master");
		// Only add the content if we had not addded it before
		if(!stristr("127.0.0.1:10025 inet n - - - - smtpd",$content)) {
			af($conf["dist_postfix_config_dir"].'/master.cf',$content);
		}
		
		// Add the clamav user to the amavis group
		exec('adduser clamav amavis');
		
		
	}
	
	function configure_spamassassin() {
		global $conf;
		
		// Enable spamasasssin in debian and ubunti
		$configfile = '/etc/default/spamassassin';
		if(is_file($configfile)) copy($configfile,$configfile.'~');
		$content = rf($configfile);
		$content = str_replace('ENABLED=0','ENABLED=1',$content);
		wf($configfile,$content);
	}
	
	function configure_getmail() {
		global $conf;
		
		$command = "useradd -d /etc/getmail getmail";
		caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);
		
		$command = "chown -R getmail /etc/getmail";
		caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);
		
		$command = "chmod -R 700 /etc/getmail";
		caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);
	}
	
	
	function configure_pureftpd() {
		global $conf;
		
		// configure pam for SMTP authentication agains the ispconfig database
		$configfile = 'mysql.conf';
		if(is_file($conf["dist_pureftpd_config_dir"].'/'.$configfile)) copy($conf["dist_pureftpd_config_dir"].'/'.$configfile,$conf["dist_pureftpd_config_dir"].'/'.$configfile.'~');
		if(is_file($conf["dist_pureftpd_config_dir"].'/'.$configfile.'~')) exec('chmod 400 '.$conf["dist_pureftpd_config_dir"].'/'.$configfile.'~');
		$content = rf("tpl/pureftpd_".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}',$conf["mysql_server_ispconfig_user"],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$conf["mysql_server_ispconfig_password"],$content);
		$content = str_replace('{mysql_server_database}',$conf["mysql_server_database"],$content);
		$content = str_replace('{mysql_server_ip}',$conf["mysql_server_ip"],$content);
		$content = str_replace('{server_id}',$conf["server_id"],$content);
		wf($conf["dist_pureftpd_config_dir"].'/'.$configfile,$content);
		exec('chmod 600 '.$conf["dist_pureftpd_config_dir"].'/'.$configfile);
		exec('chown root:root '.$conf["dist_pureftpd_config_dir"].'/'.$configfile);
		// enable chrooting
		exec('echo "yes" > /etc/pure-ftpd/conf/ChrootEveryone');
	
	}
	
	
	function install_ispconfig() {
		global $conf;
		
		// Create the ISPConfig installation directory
		$command = "mkdir ".$conf["ispconfig_install_dir"];
		caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);
		
		// Create a ISPConfig user and group
		$command = "groupadd ispconfig";
		caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);
		
		$command = "useradd -g ispconfig -d ".$conf["ispconfig_install_dir"]." ispconfig";
		caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);
		
		// copy the ISPConfig interface part
		$command = "cp -rf ../interface ".$conf["ispconfig_install_dir"];
		caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);
		
		// copy the ISPConfig server part
		$command = "cp -rf ../server ".$conf["ispconfig_install_dir"];
		caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);
		
		// Create a symlink, so ISPConfig is accessible via web
		$command = "ln -s ".$conf["ispconfig_install_dir"]."/interface/web/ /var/www/ispconfig";
		caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);
		
		// Create the config file for ISPConfig interface
		$configfile = 'config.inc.php';
		if(is_file($conf["ispconfig_install_dir"].'/interface/lib/'.$configfile)) copy($conf["ispconfig_install_dir"].'/interface/lib/'.$configfile,$conf["ispconfig_install_dir"].'/interface/lib/'.$configfile.'~');
		$content = rf("tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}',$conf["mysql_server_ispconfig_user"],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$conf["mysql_server_ispconfig_password"],$content);
		$content = str_replace('{mysql_server_database}',$conf["mysql_server_database"],$content);
		$content = str_replace('{mysql_server_host}',$conf["mysql_server_host"],$content);
		wf($conf["ispconfig_install_dir"].'/interface/lib/'.$configfile,$content);
		
		// Create the config file for ISPConfig server
		$configfile = 'config.inc.php';
		if(is_file($conf["ispconfig_install_dir"].'/server/lib/'.$configfile)) copy($conf["ispconfig_install_dir"].'/server/lib/'.$configfile,$conf["ispconfig_install_dir"].'/interface/lib/'.$configfile.'~');
		$content = rf("tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}',$conf["mysql_server_ispconfig_user"],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$conf["mysql_server_ispconfig_password"],$content);
		$content = str_replace('{mysql_server_database}',$conf["mysql_server_database"],$content);
		$content = str_replace('{mysql_server_host}',$conf["mysql_server_host"],$content);
		$content = str_replace('{server_id}',$conf["server_id"],$content);
		wf($conf["ispconfig_install_dir"].'/server/lib/'.$configfile,$content);
		
		
		// Chmod the files
		$command = "chmod -R 750 ".$conf["ispconfig_install_dir"];
		caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);

		// chown the files to the ispconfig user and group
		$command = "chown -R ispconfig:ispconfig ".$conf["ispconfig_install_dir"];
		caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);
		
		// TODO: FIXME: add the www-data user to the ispconfig group. This is just for testing
		// and must be fixed as this will allow the apache user to read the ispconfig files.
		// Later this must run as own apache server or via suexec!
		
		$command = "adduser www-data ispconfig";
		caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);
		
		// Make the shell scripts executable
		$command = "chmod +x ".$conf["ispconfig_install_dir"]."/server/scripts/*.sh";
		caselog($command." &> /dev/null", __FILE__, __LINE__,"EXECUTED: ".$command,"Failed to execute the command ".$command);
		
		// Copy the ISPConfig vhost for the controlpanel
		copy('tpl/apache_ispconfig.vhost.master',$conf["dist_apache_vhost_conf_dir"].'/ispconfig.vhost');
		// and create the symlink
		exec('ln -s '.$conf["dist_apache_vhost_conf_dir"].'/ispconfig.vhost '.$conf["dist_apache_vhost_conf_enabled_dir"].'/ispconfig.vhost');
		
	}
	
	function install_crontab() {
		global $conf;
		
		// Root Crontab
		exec("crontab -u root -l > crontab.txt");
		$existing_root_cron_jobs = file('crontab.txt');
		
		$root_cron_jobs = array('* * * * * /usr/bin/php -q /usr/local/ispconfig/server/server.php &> /dev/null');
		foreach($root_cron_jobs as $cron_job) {
			if(!in_array($cron_job."\n",$existing_root_cron_jobs)) {
				$existing_root_cron_jobs[] = $cron_job."\n";
			}
		}
		file_put_contents('crontab.txt',$existing_root_cron_jobs);
		exec("crontab -u root crontab.txt &> /dev/null");
		unlink('crontab.txt');
		
		// Getmail crontab
		exec("crontab -u getmail -l > crontab.txt");
		$existing_cron_jobs = file('crontab.txt');
		
		$cron_jobs = array('*/5 * * * * '.$conf["dist_getmail_program"].' -g '.$conf["dist_getmail_config_dir"].' -r '.$conf["dist_getmail_config_dir"].'/*.conf &> /dev/null');
		foreach($cron_jobs as $cron_job) {
			if(!in_array($cron_job."\n",$existing_cron_jobs)) {
				$existing_cron_jobs[] = $cron_job."\n";
			}
		}
		file_put_contents('crontab.txt',$existing_cron_jobs);
		exec("crontab -u getmail crontab.txt &> /dev/null");
		unlink('crontab.txt');
		
	}
	
	
	
}

?>