<?php

/**
* This function is used for printing the success page for a user after 
*	they register.
*/
class reg_success {

	/**
	* Check to see if our user just registered, and print up a success 
	* message if they did.
	*/
	static function success() {

		$retval = "";

		//
		// If there is no registration data, send the user over to the
		// verify page.
		//
		$data = $_SESSION["reg"]["success"];
		if (empty($data)) {
			$message = t("No success data found.  Sending user over "
				. "to verify page.");
			reg_log::log($message);
			reg::goto_url("reg/verify");
		}

		$retval = self::success_page($data);

		return($retval);

	} // End of success()


	/**
	* Create our success page.
	*/
	static function success_page(&$data) {

		$url = reg::get_base() . "reg/verify";
		$email = variable_get(reg::VAR_EMAIL, "");

		$retval = "<p/>\n";

		$retval .= reg_message::load_display("success",
			array(
				"!email" => $email,
				"!member_email" => $data["member_email"],
				"!verify_url" => l($url, $url),
				"!badge_num" => $data["badge_num"],
				"!cc_name" => $data["cc_name"],
				"!total_cost" => $data["total_cost"],
				)
			);

		return($retval);

	} // End of success_page()


} // End of reg_success class

