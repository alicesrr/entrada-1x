<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Secondary controller file used by the forms module within the evaluations module.
 * /admin/evaluations/forms
 *
 * @author Organisation: Univeristy of Calgary
 * @author Unit: Faculty of Medicine
 * @author Developer: Ilya Sorokin <isorokin@ucalgary.ca>
 * @copyright Copyright 2010 University of Calgary. All Rights Reserved.
 *
*/

if (!defined("IN_EVALUATIONS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("evaluations", "update")) {
	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] do not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/evaluations/forms", "title" => "Manage Forms");

	$FORM_ID = 0;
	$ALLOW_QUESTION_MODIFICATIONS = false;
	$EVALUATION_TARGETS = array();

	if (isset($_GET["id"]) && ($tmp_input = clean_input($_GET["id"], array("trim", "int")))) {
		$FORM_ID = $tmp_input;
	} elseif (isset($_POST["id"]) && ($tmp_input = clean_input($_POST["id"], array("trim", "int")))) {
		$FORM_ID = $tmp_input;
	}

	if (($router) && ($router->initRoute())) {
		/**
		 * Check to see if we can add / modify / delete questions from an evaluation form.
		 */
		if ((int) $FORM_ID) {
			$query	= "SELECT COUNT(*) AS `total` FROM `evaluations` WHERE `eform_id` = ".$db->qstr($FORM_ID);
			$result = $db->GetRow($query);
			if ((!$result) || ((int) $result["total"] === 0)) {
				$ALLOW_QUESTION_MODIFICATIONS = true;
			}
		}

		/**
		 * Fetch a list of available evaluation targets that can be used as Form Types.
		 */
		$query = "SELECT * FROM `evaluations_lu_targets` WHERE `target_active` = '1' ORDER BY `target_title` ASC";
		$results = $db->GetAll($query);
		if ($results) {
			foreach ($results as $result) {
				$EVALUATION_TARGETS[$result["target_id"]] = $result;
			}
		}

		$module_file = $router->getRoute();
		if ($module_file) {
			require_once($module_file);
		}
	} else {
		$url = ENTRADA_URL."/admin/".$MODULE;
		application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".$url."].");

		header("Location: ".$url);
		exit;
	}
}