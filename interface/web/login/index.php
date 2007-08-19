<?php

/*
Copyright (c) 2005, Till Brehm, projektfarm Gmbh
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

//

class login_index {

public $status = '';
private $target = '';

public function render() {
	if(isset($_SESSION['s']['user']) && is_array($_SESSION['s']['user']) && is_array($_SESSION['s']['module'])) {
		die('HEADER_REDIRECT:'.$_SESSION['s']['module']['startpage']);
	}
	
	global $app;
	$app->uses('tpl');
	$app->tpl->newTemplate('form.tpl.htm');
    
    $error = '';    


	//* Login Formular wurde abgesandt
	if(count($_POST) > 0) {
	//die('Hier');
        // importiere Variablen
        $username = $app->db->quote($_POST['username']);
        $passwort = $app->db->quote($_POST['passwort']);

        if($username != '' and $passwort != '') {
                $sql = "SELECT * FROM sys_user WHERE USERNAME = '$username' and ( PASSWORT = '".md5($passwort)."' or PASSWORT = password('$passwort') )";
                if($user = $app->db->queryOneRecord($sql)) {
                        if($user['active'] == 1) {
                                $user = $app->db->toLower($user);
                                $_SESSION = array();
                                $_SESSION['s']['user'] = $user;
                                $_SESSION['s']['user']['theme'] = $user['app_theme'];
                                $_SESSION['s']['language'] = $user['language'];
								
								if(is_file($_SESSION['s']['user']['startmodule'].'/lib/module.conf.php')) {
									include_once($_SESSION['s']['user']['startmodule'].'/lib/module.conf.php');
									$_SESSION['s']['module'] = $module;
								}

                                //$site = $app->db->queryOneRecord("SELECT * FROM mb_sites WHERE name = '".$user["site_preset"]."'");
                                //$_SESSION["s"]["site"] = $site;
																
								//header ("HTTP/1.0 307 Temporary redirect");
								//header("Location: http://localhost:8080/ispconfig3_export/interface/web/admin/index.php");
																
                                /*header("Location: ../capp.php?mod=".$user["startmodule"]."&phpsessid=".$_SESSION["s"]["id"]);*/
								//header('Content-type: text/javascript');
								/*echo "<script language=\"javascript\" type=\"text/javascript\">loadContent('admin/users_list.php','')</script>";*/
								//$this->status = 'REDIRECT';
								//$this->target = 'admin:index';
								//return '';
								
								echo 'HEADER_REDIRECT:'.$_SESSION['s']['module']['startpage'];
								//echo 'HEADER_REDIRECT:content.php?s_mod=admin&s_pg=index';
                                exit;
                        } else {
                                $error = $app->lng(1003);
                        }
                } else {
                        // Username oder Passwort falsch
                        $error = $app->lng(1002);
                        if($app->db->errorMessage != '') $error .= '<br>'.$app->db->errorMessage != '';
                }
        } else {
                // Username oder Passwort leer
                $error = $app->lng(1001);
        }
	}
	if($error != ''){
  		$error = '<table width="100%" border="0" cellspacing="0" cellpadding="2">
		<tr>
		<td class="error"><b>Error:</b><br>'.$error.'</td>
		</tr>
		</table>';
	}



	$app->tpl->setVar('error', $error);
	$app->tpl->setInclude('content_tpl','login/templates/index.htm');
	$app->tpl_defaults();
	//$app->tpl->pparse();
	
	$this->status = 'OK';
	
	return $app->tpl->grab();
	
} // << end function

} // << end class

?>