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
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 * @version $Id: edit.inc.php 1169 2010-05-01 14:18:49Z simpson $
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GRADEBOOK"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("gradebook", "read", false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["group"]."] and role [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$ASSESSMENT_IDS = array();
	if(isset($_GET["assessment_ids"]) && ($tmp = explode(",", $_GET["assessment_ids"]))) {
		foreach($tmp as $t) {
			$ASSESSMENT_IDS[] = 	clean_input($t, array("trim", "int"));
		}
	}

	if((isset($_GET["grad_year"])) && ($grad_year = clean_input($_GET["grad_year"], array("trim", "int")))) {
		$GRAD_YEAR = $grad_year;
	}
	
	if ($COURSE_ID) {
		$query			= "	SELECT * FROM `courses` 
							WHERE `course_id` = ".$db->qstr($COURSE_ID)."
							AND `course_active` = '1'";
		$course_details	= $db->GetRow($query);
		
		if ($course_details && $ENTRADA_ACL->amIAllowed(new GradebookResource($course_details["course_id"], $course_details["organisation_id"]), "read")) {
			if(!empty($ASSESSMENT_IDS)) {
				$query = "	SELECT `assessments`.*,`assessment_marking_schemes`.`id` as `marking_scheme_id`, `assessment_marking_schemes`.`handler`
							FROM `assessments`
							LEFT JOIN `assessment_marking_schemes` ON `assessment_marking_schemes`.`id` = `assessments`.`marking_scheme_id`
							WHERE `assessments`.`assessment_id` IN (".implode(",", $ASSESSMENT_IDS).")";
			} else {
				$query = "	SELECT `assessments`.*,`assessment_marking_schemes`.`id` as `marking_scheme_id`, `assessment_marking_schemes`.`handler`
							FROM `assessments`
							LEFT JOIN `assessment_marking_schemes` ON `assessment_marking_schemes`.`id` = `assessments`.`marking_scheme_id`
							WHERE `assessments`.`grad_year` = ".$db->qstr($GRAD_YEAR);
			}

			$assessments = $db->GetAll($query);
			
			if($assessments) {
				// CSV Download
							
				$query	= 	"SELECT b.`id` AS `proxy_id`, CONCAT_WS(', ', b.`lastname`, b.`firstname`) AS `fullname`, b.`number`";
				foreach($assessments as $key => $assessment) {
					$query 	.=  ", assessment_$key.`grade_id` AS `".$key."_grade_id`, assessment_$key.`value` AS `".$key."_grade_value` ";
				}
				
				$query 	.=  " FROM `".AUTH_DATABASE."`.`user_data` AS b
							  LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS c
							  ON c.`user_id` = b.`id` AND c.`app_id`=".$db->qstr(AUTH_APP_ID)."
							  AND c.`account_active`='true'
							  AND (c.`access_starts`='0' OR c.`access_starts`<=".$db->qstr(time()).")
							  AND (c.`access_expires`='0' OR c.`access_expires`>=".$db->qstr(time()).") ";
				foreach($assessments as $key => $assessment) {
					$query .=   " LEFT JOIN `".DATABASE_NAME."`.`assessment_grades` AS assessment_$key ON b.`id` = assessment_$key.`proxy_id` AND assessment_$key.`assessment_id` = ".$db->qstr($assessment["assessment_id"])."\n";
				}
				
				if(isset($GRAD_YEAR)) {
					$query .= 	" WHERE c.`group` = 'student' AND c.`role` = ".$db->qstr($GRAD_YEAR);
				}

				$students = $db->GetAll($query);
				
				ob_start();
				echo "\"Number\",\"Fullname\"";
				foreach($assessments as $key => $assessment) {
					echo ",\"".$assessment["name"]." (".$assessment["type"].")\"";
				}
				echo "\n";
				
				if(count($students) >= 1) {
					foreach ($students as $student) {
						$proxy_id	= $student["proxy_id"];
						
						$cols	= array();
						$cols[]	= (($student["group"] == "student") ? $student["number"] : 0);
						$cols[]	= $student["fullname"];
						
						foreach($assessments as $key => $assessment) {					
							$cols[] = $student[$key."_grade_value"];
						}
						
						echo "\"".implode("\",\"", $cols)."\"", "\n";
					}
				}
				$contents = ob_get_contents();

				ob_clear_open_buffers();
				if(isset($GRAD_YEAR)) {
					$filename = date("Y-m-d")."_".useable_filename($course_details["course_code"]."_".$GRAD_YEAR."_gradebook").".csv\"";
				} else {
					$filename = date("Y-m-d")."_".useable_filename($course_details["course_code"]."_gradebook").".csv\"";
				}
				
				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				header("Content-Type: text/csv");
				header("Content-Disposition: attachment; filename=\"".$filename);
				header("Content-Length: ".strlen($contents));
				header("Content-Transfer-Encoding: binary\n");

				echo $contents;
				exit;
			} else {
				$ERROR++;
				$ERRORSTR[] = "In order to view an assessment's grades you must provide some valid assessment identifiers.";

				echo display_error();

				application_log("notice", "Failed to provide a valid assessment identifiers when attempting to view an assessment's grades.");
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "You don't have permission to view this gradebook.";

			echo display_error();

			application_log("error", "User tried to view gradebook without permission.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to export a gradebook you must provide a valid course identifier.";

		echo display_error();

		application_log("notice", "Failed to provide course identifier when attempting to export an gradebook's grades.");
	}
}
?>