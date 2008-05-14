<?php

/**
* This is the reg_admin class, which holds functions related to the 
*	administrative end of the registration system.
*/
class reg_admin {

	/**
	* Our constructor.  This should never be called.
	*/
	function __construct() {
		$error = "You tried to instantiate this class even after I told "
			. "you not to!";
		throw new Exception($error);
	}


	/**
	* Our main admin page.
	*/
	static function main() {

		$retval = "";
		$retval = drupal_get_form("reg_admin_form");

		return($retval);

	} // End of admin()

	
	/**
	* List membership levels.
	*/
	static function levels() {

		$retval = "";

$retval = "TEST";

		return($retval);

	} // End of levels()


	/**
	* Add a new membership level.
	*/
	static function levels_edit() {

		$retval = drupal_get_form("reg_admin_level_form");
		return($retval);

	} // End of levels_add()


	/**
	* Create our level for adding/editing a form.
	*/
	static function level_form($id) {

		drupal_set_title("Add New Membership Level");

		$retval = array();
//
// TODO: 
// - Saving function
// - Editing function
// - Write validation function?
//	- How about a warning when editing a pre-existing membership level?
// - Load existing membership level
// - List levels
//	- Sort by descending year
//

		$retval["name"]  = array(
			"#title" => "Level Name",
			"#description" => "What the user sees.  i.e. Attending, Sponsor, etc.",
			"#type" => "textfield",
			"#size" => reg::FORM_TEXT_SIZE,
			);

		$retval["year"] = array(
			"#title" => "Convention Year",
			"#descrption" => "This is so that we can keep *proper* historic "
				. "data from past years.",
			"#type" => "textfield",
			"#size" => reg::FORM_TEXT_SIZE_SMALL,
			);

		$types = reg::get_types();
		$retval["reg_type_id"] = array(
			"#title" => "Membership Type",
			"#description" => "The type of membership.  The user does NOT see this.",
			"#type" => "select",
			"#options" => $types,
			);


		$retval["price"] = array(
			"#title" => "Price",
			"#description" => "The price of this membership",
			"#type" => "textfield",
			"#size" => reg::FORM_TEXT_SIZE_SMALL,
			);

		$retval["start"] = array(
			"#title" => "Starting Date",
			"#description" => "The membership will be available to the public "
				. "on or after this date.",
			"#type" => "date",
			);

		$retval["end"] = array(
			"#title" => "End Date",
			"#description" => "After 11:59 PM on this date, this membership "
				. "will no logner be available to the public.",
			"#type" => "date",
			);

		$retval["submit"] = array(
			"#type" => "submit",
			"#value" => "Save"
			);

		return($retval);

	} // End of level_edit()


	/**
	* This function creates the data structure for our main admin form.
	*
	* @return array Associative array of registration form.
	*/
	static function form() {

		$retval = array();

		$retval["fake_cc"] = array(
			"#type" => "checkbox",
			"#title" => "Credit Card Test Mode?",
			"#default_value" => variable_get(reg::FORM_ADMIN_FAKE_CC, false),
			"#description" => "If set, credit card numbers will "
				. "not be processed.  Do NOT use in production!",
			);

		$retval["conduct_path"] = array(
			"#type" => "textfield",
			"#title" => "Standards of Conduct Path",
			"#default_value" => variable_get(reg::FORM_ADMIN_CONDUCT_PATH, ""),
			"#description" => "If a valid path is entered here, "
				. "the user will be forced to agree to the "
				. "Standards of Conduct before registering.  Do NOT use a "
				. "leading slash.",
			"#size" => reg::FORM_TEXT_SIZE,
			);

		$retval["submit"] = array(
			"#type" => "submit",
			"#value" => "Save"
			);

		return($retval);

	} // End of form()


	/**
	* This function is called to validate the form data.
	* If there are any issues, form_set_error() should be called so
	* that form processing does not continue.
	*/
	static function form_validate(&$form_id, &$data) {

		//
		// If a path was entered, make sure it is a valid alias or
		// a valid node.
		//
		if (!empty($data["conduct_path"])) {
			if (!drupal_lookup_path("source", $data["conduct_path"])) {
				$results = explode("/", $data["conduct_path"]);
				$nid = $results[1];
				if (empty($nid) || !node_load($nid)) {
					form_set_error("conduct_path", 
						"Invalid path entered for Standards of Conduct");
				}
			}
		}

		//form_set_error("fake_cc", "test2");
		//print_r($data);

	} // End of form_validate()


	/**
	* This function is called after our form has been successfully validated.
	*
	* It should make any necessary changes to the database.  At the 
	* conclusion of this funciton, the user is redirected back to the 
	* form page.
	*/
	static function form_submit($form_id, $data) {
		variable_set(reg::FORM_ADMIN_FAKE_CC, $data["fake_cc"]);
		variable_set(reg::FORM_ADMIN_CONDUCT_PATH, $data["conduct_path"]);
		drupal_set_message("Settings updated");
	}

} // End of reg_admin class

