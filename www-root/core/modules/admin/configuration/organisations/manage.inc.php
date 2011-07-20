<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Organisation: Queen's University
 * @author Unit: MEdTech Unit
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CONFIGURATION"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "update",false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["group"]."] and role [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	if (($router) && ($router->initRoute())) {
		$PREFERENCES = preferences_load($MODULE);
		
		if (isset($_GET["org"]) && ($org = clean_input($_GET["org"], array("notags", "trim")))) {
				$ORGANISATION_ID = $org;
		}
		
		if($ORGANISATION_ID){
			$query = "SELECT * FROM `" . AUTH_DATABASE . "`.`organisations` WHERE `organisation_id` = " . $db->qstr($ORGANISATION_ID);
			$ORGANISATION = $db->GetRow($query);
			if($ORGANISATION){
				if($ENTRADA_ACL->amIAllowed(new ConfigurationResource($ORGANISATION_ID),"read")){
					$query = "SELECT * FROM `".AUTH_DATABASE."`.`organisations` WHERE `organisation_id` = ".$db->qstr($ORGANISATION_ID);

					$ORGANISATION = $db->GetRow($query);
					$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/configuration/organisations/manage?org=".$ORGANISATION['organisation_id'], "title" => $ORGANISATION["organisation_title"]);

					$sidebar_html  = "<ul class=\"menu\">";
					$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_URL."/admin/configuration/organisations/manage/objectives?org=".$ORGANISATION_ID."\">Manage Objectives</a></li>\n";
					$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_URL."/admin/configuration/organisations/manage/eventtypes?org=".$ORGANISATION_ID."\">Manage Eventtypes</a></li>\n";
					$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_URL."/admin/configuration/organisations/manage/hottopics?org=".$ORGANISATION_ID."\">Manage Hot Topics</a></li>\n";
					$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_URL."/admin/configuration/organisations/manage/metadata?org=".$ORGANISATION_ID."\">Manage Meta Data</a></li>\n";
					$sidebar_html .= "</ul>";
					new_sidebar_item("Organisation Management", $sidebar_html, "config-org-nav", "open");


					$module_file = $router->getRoute();
					if ($module_file) {
						require_once($module_file);
					}
				}else {
					add_notice("You don't appear to have access to change this organisation. If you feel you are seeing this in error, please contact your system administrator.");
					echo display_notice();
				}
			}else{
				add_notice("The organisation appears to be invalid. If you feel you are seeing this in error, please contact your system administrator.");
				echo display_notice();
			}
		}
		else{
			$url = ENTRADA_URL."/admin/configuration/organisations/";
			$ERROR++;
			$ERRORSTR[] = "No organisation was selected. Please select an organisation and try again. In five seconds you will now be returned to the organisation screen, or, click <a href = \"".$url."\">here</a> to continue.";
			echo display_error();
			$ONLOAD[]	= "setTimeout('window.location=\\'".$url."\\'', 5000)";
			
		}
		
		


		/**
		 * Check if preferences need to be updated on the server at this point.
		 */
		preferences_update($MODULE, $PREFERENCES);
	} else {
		$url = ENTRADA_URL."/admin/".$MODULE;
		application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".$url."].");

		header("Location: ".$url);
		exit;
	}
}