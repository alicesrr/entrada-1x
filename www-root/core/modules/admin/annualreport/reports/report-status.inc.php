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
 * This file is used to display facutly completion of their annual report
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_ANNUAL_REPORT"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('annualreportadmin', 'read', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {	
	$BREADCRUMB[]	= array("url" => "", "title" => "Annual Report Completion Rate" );
	
	$years = getMinMaxARYears();
	
	if(isset($years["start_year"]) && $years["start_year"] != "") {
		$PROCESSED["report_type"] = $_POST['report_type'];
		$PROCESSED["year_reported"] = $_POST['year_reported'];
		$PROCESSED["report_completed"] = $_POST['report_completed'];
		?>
		<style type="text/css">
		h1 {
			page-break-before:	always;
			border-bottom:		2px #CCCCCC solid;
			font-size:			24px;
		}
		
		h2 {
			font-weight:		normal;
			border:				0px;
			font-size:			18px;
		}
		
		div.top-link {
			float: right;
		}
		</style>
		<a name="top"></a>
		<div class="no-printing">
			<form action="<?php echo ENTRADA_URL; ?>/admin/annualreport/reports?section=<?php echo $SECTION; ?>&step=2" method="post">
			<input type="hidden" name="update" value="1" />
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
			<colgroup>
				<col style="width: 3%" />
				<col style="width: 20%" />
				<col style="width: 77%" />
			</colgroup>
			<tbody>
				<tr>
					<td colspan="3"><h2>Report Options</h2></td>
				</tr>
				<tr>
					<td></td>
					<td><label for="year_reported" class="form-required">Reporting Period</label></td>
					<td><select name="year_reported" id="year_reported" style="vertical-align: middle">
					<?php
						for($i=$years["start_year"]; $i<=$years["end_year"]; $i++)
						{
							if(isset($PROCESSED["year_reported"]) && $PROCESSED["year_reported"] != '')
							{
								$defaultStartYear = $PROCESSED["year_reported"];
							}
							else 
							{
								$defaultStartYear = $years["end_year"];
							}
							echo "<option value=\"".$i."\"".(($defaultStartYear == $i) ? " selected=\"selected\"" : "").">".$i."</option>\n";
						}
						echo "</select>";
					?>
					</td>
				</tr>
				<tr>
					<td></td>
					<td><label for="report_type" class="form-required">Faculty to Display</label></td>
					<td><select name="report_type" id="report_type" style="vertical-align: middle">
					<?php
						echo "<option value=\"All\"".(($PROCESSED["report_type"] == "All") ? " selected=\"selected\"" : "").">All Faculty</option>\n";
						echo "<option value=\"Clinical\"".(($PROCESSED["report_type"] == "Clinical") ? " selected=\"selected\"" : "").">Clinical Faculty</option>\n";
						echo "<option value=\"Non-Clinical\"".(($PROCESSED["report_type"] == "Non-Clinical") ? " selected=\"selected\"" : "").">Non-Clinical Faculty</option>\n";
						echo "</select>";
					?>
					</td>
				</tr>
				<tr>
					<td></td>
					<td><label for="report_completed" class="form-required">Report Status</label></td>
					<td><select name="report_completed" id="report_completed" style="vertical-align: middle">
					<?php
						echo "<option value=\"Completed\"".(($PROCESSED["report_completed"] == "Completed") ? " selected=\"selected\"" : "").">Completed</option>\n";
						echo "<option value=\"Pending\"".(($PROCESSED["report_completed"] == "Pending") ? " selected=\"selected\"" : "").">Started</option>\n";
						echo "</select>";
					?>
					</td>
				</tr>
				<tr>
					<td colspan="3" style="text-align: right; padding-top: 10px"><input type="submit" class="btn btn-primary" value="Create Report" /></td>
				</tr>
			</tbody>
			</table>
			</form>
		</div>
		<?php
		if ($STEP == 2) {
			switch($PROCESSED["report_type"]) {
				case "Clinical":
					$title_suffix = " Clinical Facutly";
					$type_where	= " AND `".AUTH_DATABASE."`.`user_data`.`clinical` = '1'";
					break;
				case "Non-Clinical":
					$title_suffix = " Non-Clinical Facutly";
					$type_where	= " AND `".AUTH_DATABASE."`.`user_data`.`clinical` = '0'";
					break;
				default:
				case "All":
					$title_suffix = " All Facutly";
					$type_where = "";
					break;
			}
			
			switch($PROCESSED["report_completed"]) {
				case "Pending":
					$query = 	"SELECT distinct `firstname`, `lastname`, `year_reported`, `report_completed`, `".AUTH_DATABASE."`.`user_data`.`clinical`, `department_title`, `ar_profile`.`profile_id`, `ar_profile`.`proxy_id`
								FROM `".DATABASE_NAME."`.`ar_profile`, `".AUTH_DATABASE."`.`user_data`, `".AUTH_DATABASE."`.`user_departments`, `".AUTH_DATABASE."`.`departments` 
								WHERE `year_reported` = ".$db->qstr($PROCESSED["year_reported"]).$type_where."
								AND `report_completed` = \"no\"
								AND `".DATABASE_NAME."`.`ar_profile`.`proxy_id` = `".AUTH_DATABASE."`.`user_data`.`id` 
								AND `".AUTH_DATABASE."`.`user_data`.`id` = `".AUTH_DATABASE."`.`user_departments`.`user_id`
								AND `dep_id` = `department_id`
								ORDER BY `department_title` ASC, `lastname` ASC, `firstname` ASC";
					break;
				case "Completed":
				default:
					$query = 	"SELECT distinct `firstname`, `lastname`, `year_reported`, `report_completed`, `".AUTH_DATABASE."`.`user_data`.`clinical`, `department_title`, `ar_profile`.`profile_id`, `ar_profile`.`proxy_id`
								FROM `".DATABASE_NAME."`.`ar_profile`, `".AUTH_DATABASE."`.`user_data`, `".AUTH_DATABASE."`.`user_departments`, `".AUTH_DATABASE."`.`departments` 
								WHERE `year_reported` = ".$db->qstr($PROCESSED["year_reported"]).$type_where."
								AND `report_completed` = \"yes\" 
								AND `".DATABASE_NAME."`.`ar_profile`.`proxy_id` = `".AUTH_DATABASE."`.`user_data`.`id` 
								AND `".AUTH_DATABASE."`.`user_data`.`id` = `".AUTH_DATABASE."`.`user_departments`.`user_id`
								AND `dep_id` = `department_id`
								ORDER BY `department_title` ASC, `lastname` ASC, `firstname` ASC";
					break;
			}
			
			echo "<h2>Annual Report Completion Rate for ".$title_suffix."</h2>";
			echo "<div class=\"content-small\" style=\"margin-bottom: 10px\">\n";
			echo "	<strong>Reporting Period:</strong> ".$PROCESSED["year_reported"]." <strong>";
			echo "</div>\n";
			
			$results	= $db->GetAll($query);
			
			if ($results) {
				?>
				<table class="tableList" cellspacing="0" summary="Grant Eligible Details - Number of Students">
				<colgroup>
					<col class="general" />
					<col class="general" />
					<col class="title" />
					<col class="completed" style="width: 75px; text-align: left;"/>
					<col class="completed" style="width: 75px; text-align: left;"/>
					<col class="modified" />
				</colgroup>
				<thead>
					<tr>
						<td class="general" style="border-left: 1px #666 solid">Firstname</td>
						<td class="general" >Lastname</td>
						<td class="title">Department</td>
						<td class="completed" style="width: 75px; text-align: left;">Clinical</td>
						<td class="completed" style="width: 75px; text-align: left;">Status</td>
						<td class="modified"></td>
					</tr>
				</thead>
				<tbody>
				<?php
				$count = 0;
				foreach ($results as $result) {
					$count++;
					
					if($result["report_completed"] == "yes") {
						$status = "Completed";
						$cell = "<td class=\"modified\"><a href=\"javascript: void(0)\" onclick=\"window.open('".ENTRADA_URL . "/annualreport/generate?section=generate-annual-report&amp;rid=".$result["profile_id"]."&amp;proxy_id=".$result["proxy_id"]."&amp;clinical=".$result["clinical"]."');\" style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"".ENTRADA_RELATIVE."/css/jquery/images/report_go.gif\" style=\"border: none\"/></a></td>";
					} else if($result["report_completed"] == "no") {
						$status = "Started";
						$cell = "<td class=\"modified\"><a href=\"javascript: void(0)\" onclick=\"window.open('".ENTRADA_URL . "/annualreport/generate?section=generate-annual-report&amp;rid=".$result["profile_id"]."&amp;proxy_id=".$result["proxy_id"]."&amp;clinical=".$result["clinical"]."');\" style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"".ENTRADA_RELATIVE."/css/jquery/images/report_go.gif\" style=\"border: none\"/></a></td>";
					} else {
						$status = "Not Started";
						$cell = "<td class=\"modified\">&nbsp;</td>";
					}
					echo "<tr>\n";
					echo "	<td class=\"general\">".$result["firstname"]."</td>\n";
					echo "	<td class=\"general\">".$result["lastname"]."</td>\n";
					echo "	<td class=\"title\" style=\"white-space: normal\">".$result["department_title"]."</td>\n";
					echo "	<td class=\"completed\" style=\"width: 75px; text-align: left;\">".($result["clinical"] == 1 ? "Yes" : "No")."</td>\n";
					echo "	<td class=\"completed\" style=\"width: 75px; text-align: left;\">".$status."</td>\n";
					echo $cell;
					echo "</tr>\n";
				}
				?>
				</tbody>
				</table>
				<?php	
				echo "<h2>Total: ".$count."</h1>";			
			} else {
				echo display_notice(array("There are no annual reports in the system for the period you have selected."));	
			}
		}
	} else {
		echo display_notice(array("There are no annual reports in the system yet."));
	}
}
?>