<?php

/**
* This class adds functions to view recent log entries.
*/
class reg_admin_log_view extends reg_admin_log {

	/**
	* How many items to display per page when viewing log entires.
	*/
	protected $items_per_page;

	function __construct($message, $fake, $log) {
        parent::__construct($message, $fake, $log);
		$this->items_per_page = $this->get_constant("ITEMS_PER_PAGE");
	}


	/**
	* Change the number of items per page.
	*/
	function set_items_per_page($num) {
		$this->items_per_page = $num;
	}


	/**
	* Our log viewer.  Lists the most recent log entries.
	*
	* @param integer $id Optional registration ID to limit results
	*	to a single membership.
	*
	* @param array $search Associative array of search criteria to search
	*	for.
	*
	* @return string HTML code of the log entry.
	*/
	function log_recent($id = "", $search = "") {

		$retval = "";

		//
		// The icons and classes variables are sraight from the
		// watchdog_overview() function.
		//
		$icons = array(WATCHDOG_NOTICE  => '',
			WATCHDOG_WARNING => theme('image', 
				'misc/watchdog-warning.png', t('warning'), t('warning')),
			WATCHDOG_ERROR   => theme('image', 
				'misc/watchdog-error.png', t('error'), t('error'))
			);

		$header = array();
		$header[] = " ";
		$header[] = array("data" => "Date", "field" => "date");
		$header[] = array("data" => "Message", "field" => "message");
		$header[] = array("data" => "User", "field" => "name");

		$cursor = $this->log_recent_cursor($header, $id, $search);

		$rows = $this->log_recent_rows($cursor);

		$retval .= theme("table", $header, $rows);

		$retval .= theme_pager();

		return($retval);

	} // End of log_recent()


	/**
	* Get our cursor for the recent log entries.
	* 
	* @param array $header Our header. 
	*
	* @param integer $id The ID of a registration to search for.  May be null.
	*
	* @return A cursor that can be used by the database fetching functions.
	*/
	function log_recent_cursor(&$header, $id, $search) {

		//
		// By default, we'll be sorting by the reverse date.
		//
		$order_by = tablesort_sql($header);
		if (empty($order_by)) {
			$order_by = "ORDER BY id DESC";
		}

		//
		// Create our where clause
		//
		$where = array();
		$where_text = "";
		$where_args = array();

		if (!empty($id)) {
			$where[] = "reg_log.reg_id='%s' ";
			$where_args[] = $id;
		}

		if (!empty($search["text"])) {
			$where[] = "reg_log.message LIKE '%%%s%%' ";
			$where_args[] = $search["text"];
		}

		if (!empty($search["uid"])) {
			$where[] = "reg_log.uid = '%s' ";
			$where_args[] = $search["uid"];
		}

		//
		// If we are just viewing a specific member's activity, don't show 
		// the audit log entries.  Seemed like a good idea at the time, 
		// but the end result is that it completely filled up the member 
		// info screen.  We can still view audit log entries on the main log 
		// search page.
		//
		if (!empty($id) && empty($search)) {
			$where[] = "reg_log.message NOT LIKE '%s%%' ";
			$where_args[] = t("Audit log:");
		}

		if (!empty($where)) {
			$where_text = "WHERE ";
			$where_text .= join("AND ", $where);
		}

		//
		// Fetch our log entries and loop through them
		//
		$rows = array();
		$query = "SELECT reg_log.*, "
			. "users.uid, users.name "
			. "FROM {reg_log} "
			. "LEFT JOIN {users} ON reg_log.uid = users.uid "
			. $where_text
			. $order_by
			;

		$retval = pager_query($query, $this->items_per_page,
			0, null, $where_args);

		return($retval);

	} // End of log_recent_cursor()


	/**
	* Format the rows for log entries.
	*
	* @param $cursor The database cursor to iterate through.
	*
	* @return array An array of rows that can be passed into the 
	*	table theming function.
	*/
	function log_recent_rows($cursor) {

		$retval = array();

		while ($row = db_fetch_array($cursor)) {

			$id = $row["id"];

			//
			// Stick in the username if we have it.
			//
			$username = $row["name"];
			if (!empty($row["name"])) {
				$uid = $row["uid"];
				$user_link = l($username, "user/" . $uid);

			} else {
				$user_link = "Anonymous";

			}
			
			$max_len = 60;

			$link = "admin/reg/logs/view/" . $id . "/view";
			$date = format_date($row["date"], "small");
			$message = truncate_utf8($row["message"], $max_len);
			if (strlen($row["message"]) > $max_len) {
				$message .= "...";
			}

			$severity = $row["severity"];
			$icon = $icons[$severity];

			//
			// Set our display class for any warnings or errors.
			//
			$class = "";
			if ($severity == WATCHDOG_WARNING) {
				$class = "reg-warning";

			} else if ($severity == WATCHDOG_ERROR) {
				$class = "reg-error";

			}

			$retval[] = array(
				"data" => array(
					$icon,
					l($date, $link),
					l($message, $link),
					$user_link,
					),
				"class" => $class,
				);

		}

		return($retval);

	} // End of log_recent_rows()


	/**
	* Pull up details for a single row.
	*
	* @param integer $id The ID from the reg_log table.
	*
	* @return string HTML code of the log entry.
	*/
	function log_detail($id) {

		$query = "SELECT reg_log.*, "
			. "reg_log.id AS reg_log_id, "
			. "reg.badge_num, reg.year, reg.badge_name, "
			. "reg.first, reg.middle, reg.last, "
			. "users.uid, users.name "
			. "FROM {reg_log} "
			. "LEFT JOIN {users} ON reg_log.uid = users.uid "
			. "LEFT JOIN {reg} ON reg_log.reg_id = reg.id "
			. "WHERE "
			. "reg_log.id='%s' ";
		$query_args = array($id);
		$cursor = db_query($query, $query_args);
		$row = db_fetch_array($cursor);
		$row["url"] = check_url($row["url"]);
		$row["referrer"] = check_url($row["referrer"]);

		//
		// Stick in the username if we have it.
		//
		$username = $row["name"];
		if (!empty($row["name"])) {
			$uid = $row["uid"];
			$user_link = l($username, "user/" . $uid);

		} else {
			$user_link = "Anonymous";

		}
			
		if (!empty($row["reg_id"])) {
			$member_link = "admin/reg/members/view/" 
				. $row["reg_id"] . "/view";
		}

		$rows = array();
		$rows[] = array(
			array("data" => "Registration Log ID#", "header" => true),
			$row["reg_log_id"]
			);
		$rows[] = array(
			array("data" => "Date", "header" => true),
			format_date($row["date"], "small"),
			);

		if (!empty($row["badge_num"])) {
			$rows[] = array(
				array("data" => "Badge Number", "header" => true),
				l($row["year"] . "-" 
					. $this->format_badge_num($row["badge_num"]
					), $member_link)
				);
		}

		if (!empty($row["badge_name"])) {
			$rows[] = array(
				array("data" => "Badge Name", "header" => true),
				l($row["badge_name"], $member_link)
				);
		}

		if (!empty($row["first"])) {
			$name = $row["first"] . " " 
				. $row["middle"] . " " . $row["last"];
			$rows[] = array(
				array("data" => "Real Name", "header" => true),
				l($name, $member_link)
				);
		}

		$rows[] = array(
			array("data" => "Location", "header" => true),
			"<a href=\"" . $row["url"] . "\">" . $row["url"] . "</a>",
			);
		$rows[] = array(
			array("data" => "Referrer", "header" => true),
			"<a href=\"" . $row["referrer"] . "\">" 
				. $row["referrer"] . "</a>",
			);
		$rows[] = array(
			array("data" => "User", "header" => true),
			$user_link
			);
		$rows[] = array(
			array("data" => "Message", "header" => true),
			$row["message"]
			);
		$rows[] = array(
			array("data" => "Hostname", "header" => true),
			$row["remote_addr"]
			);

		$retval = theme("table", array(), $rows);
		return($retval);

	} // End of log_detail()


	/**
	* View our transactions.
	*
	* @param integer $id Optional registration ID to limit results
	*	to a single membership.
	*
	* @return string HTML code.
	*/
	function trans_recent($id = "") {

		$retval = "";

		$header = array();
		$header[] = array("data" => "Date", "field" => "date",
			"sort" => "desc");
		$header[] = array("data" => "Payment Type", "field" => "name");
		$header[] = array("data" => "Transaction Type", "field" => "name");
		$header[] = array("data" => "Amount", "field" => "name");
		$header[] = array("data" => "Donation", "field" => "name");
		$header[] = array("data" => "Total", "field" => "name");
		$header[] = array("data" => "User", "field" => "name");

		//
		// By default, we'll be sorting by the reverse date.
		//
		$order_by = tablesort_sql($header);

		$where = "";
		$where_args = array();
		if (!empty($id)) {
			$where = "WHERE reg_trans.reg_id='%s' ";
			$where_args[] = $id;
		}

		//
		// Select log entries with the username included.
		//
		$rows = array();
		$query = "SELECT reg_trans.*, "
			. "reg_payment_type.payment_type, "
			. "reg_trans_type.trans_type, "
			. "users.uid, users.name "
			. "FROM {reg_trans} "
			. "LEFT JOIN {reg_trans_type} "
				. "ON reg_trans_type_id = reg_trans_type.id "
			. "LEFT JOIN {reg_payment_type} "
				. "ON reg_payment_type_id = reg_payment_type.id "
			. "LEFT JOIN {users} ON reg_trans.uid = users.uid "
			. $where
			. $order_by
			;
		$cursor = pager_query($query, $this->items_per_page,
			0, null, $where_args);
		while ($row = db_fetch_array($cursor)) {

			$id = $row["id"];

			//
			// Stick in the username if we have it.
			//
			$username = $row["name"];
			if (!empty($row["name"])) {
				$uid = $row["uid"];
				$user_link = l($username, "user/" . $uid);

			} else {
				$user_link = "Anonymous";

			}
			
			$link = "admin/reg/logs/transactions/" . $id . "/view";
			$date_string = format_date($row["date"], "small");
			$rows[] = array(
				l($date_string, $link),
				l($row["payment_type"], $link),
				l($row["trans_type"], $link),
				l("$" . $row["badge_cost"], $link),
				l("$" . $row["donation"], $link),
				l("$" . $row["total_cost"], $link),
				$user_link,
				);
		}

		$retval = theme("table", $header, $rows);

		$retval .= theme_pager();

		return($retval);

	} // End of trans()


	/**
	* Pull up details for a single transaction.
	*
	* @param integer $id The ID from the reg_transacion table.
	*
	* @return string HTML code of the log entry.
	*/
	function trans_detail($id) {

		//
		// Load transaction data
		//
		$row = $this->trans_detail_data($id);

		//
		// Stick in the username if we have it.
		//
		$username = $row["name"];
		if (!empty($row["name"])) {
			$uid = $row["uid"];
			$user_link = l($username, "user/" . $uid);

		} else {
			$user_link = "Anonymous";

		}

		if (!empty($row["reg_id"])) {
			$member_link = "admin/reg/members/view/" 
				. $row["reg_id"] . "/view";
		}

		$rows = array();
		$rows[] = array(
			array("data" => "Transaction Log ID#", "header" => true),
			$row["reg_trans_id"]
			);
		$rows[] = array(
			array("data" => "Date", "header" => true),
			format_date($row["date"], "small"),
			);

		if (!empty($row["badge_num"])) {
			$rows[] = array(
				array("data" => "Badge Number", "header" => true),
				l($row["year"] . "-" 
					. $this->format_badge_num($row["badge_num"]
					), $member_link)
				);
		}

		if (!empty($row["badge_name"])) {
			$rows[] = array(
				array("data" => "Badge Name", "header" => true),
				l($row["badge_name"], $member_link)
				);
		}

		if (!empty($row["first"])) {
			$name = $row["first"] . " " 
				. $row["middle"] . " " . $row["last"];
			$rows[] = array(
				array("data" => "Real Name", "header" => true),
				l($name, $member_link)
				);
		}

		$rows[] = array(
			array("data" => "User", "header" => true),
			$user_link
			);

		if (!empty($row["first"])) {
			$name = $row["first"] . " " . $row["middle"] 
				. " " . $row["last"];
			$rows[] = array(
				array("data" => "Name", "header" => true),
				$name
				);
		}

		if (!empty($row["address1"])) {
			$address = $row["address1"] . "<br/>\n"
				. $row["address2"] . "<br/>\n"
				. $row["city"] . ", " . $row["state"] . " " . $row["zip"] 
				. "<br/>\n"
				. $row["country"]
				;
			$rows[] = array(
				array("data" => "Address", "header" => true, "valign" => "top"),
				$address
				);

		}

		if (!empty($row["shipping_address1"])) {

			$address = $row["shipping_name"] . "<br/>\n"
				. $row["shipping_address1"] . " " 
				. $row["shipping_address2"] . "<br/>\n"
				. $row["shipping_city"] . ", " . $row["shipping_state"] 
				. " " . $row["shipping_zip"] 
				. "<br/>\n"
				. $row["shipping_country"]
				;

			$rows[] = array(
				array("data" => "Address", "header" => true, "valign" => "top"),
				$address
				);
		}


		if (!empty($row["payment_type"])) {
			$rows[] = array(
				array("data" => "Payment Type", "header" => true),
				$row["payment_type"]
				);
		}

		if (!empty($row["reg_trans_gateway_id"])) {
			$rows[] = array(
				array("data" => "Payment Gateway", "header" => true),
				$row["gateway"]
				);
		}

		if (!empty($row["gateway_transaction_id"])) {
			$rows[] = array(
				array("data" => "Gateway Transaction ID", "header" => true),
				$row["gateway_transaction_id"]
				);
		}
		
		if (!empty($row["gateway_auth_code"])) {
			$rows[] = array(
				array("data" => "Gateway Auth Code", "header" => true),
				$row["gateway_auth_code"]
				);
		}
		
		if (!empty($row["gateway_avs"])) {
			$rows[] = array(
				array("data" => "Gateway AVS Response", "header" => true),
				$row["gateway_avs"]
				);
		}
		
		if (!empty($row["gateway_cvv"])) {
			$rows[] = array(
				array("data" => "Gateway CVV Response", "header" => true),
				$row["gateway_cvv"]
				);
		}

		if (!empty($row["invoice_number"])) {
			$rows[] = array(
				array("data" => "Custom invoice number", "header" => true),
				$row["invoice_number"]
					. t(" (For internal use only.  This is NEVER to be "
						. "shown to the user.)")
				);
		}

		if (!empty($row["cc_num"])
			&& $row["payment_type"] == "Credit Card"
			) {

			//
			// Create a time_t from the expiration month and year, then
			// format it so only the month and year are shown.
			//
			$card_expire_time_t = mktime(0,0,0,$row["card_expire_month"], 1, 
				$row["card_expire_year"]);
			$date_format = "F, Y";
			$cc_exp = format_date($card_expire_time_t, "custom", $date_format);

			$cc = t("%type% ending '%num%'. Expires: %date%",
				array(
					"%type%" => $row["cc_type"],
					"%num%" => $row["cc_num"],
					"%date%" => $cc_exp,
					)
				);
			$rows[] = array(
				array("data" => "Credit Card", "header" => true),
				$cc
				);
		}

		$rows[] = array(
			array("data" => "Transaction Type", "header" => true),
			$row["trans_type"]
			);
		$rows[] = array(
			array("data" => "Amount", "header" => true),
			"$" . $row["badge_cost"]
			);
		$rows[] = array(
			array("data" => "Donation", "header" => true),
			"$" . $row["donation"]
			);
		$rows[] = array(
			array("data" => "Total Cost", "header" => true),
			"$" . $row["total_cost"]
			);

		$retval = theme("table", array(), $rows);

		//
		// If we have a log entry, display it
		//
		if (!empty($row["reg_log_id"])) {
			$retval .= "<h2>Attached Log Entry</h2>";
			$retval .= $this->log_detail($row["reg_log_id"]);
		}

		return($retval);

	} // End of trans_detail()


	/**
	* Load data for a specific transaction.
	*
	* @param integer $id The transaction ID
	*
	* @return array An array of transaction data.
	*/
	function trans_detail_data($id) {

		$query = "SELECT reg_trans.*, "
			//
			// Add 1 day to timestamp to ensure that GMT offset issues 
			// don't accidnetally bump the date into the previous month
			//
			. "YEAR(FROM_UNIXTIME(reg_trans.card_expire + 86400)) "
				. "AS card_expire_year, "
			. "MONTH(FROM_UNIXTIME(reg_trans.card_expire + 86400)) "
				. "AS card_expire_month, "
			. "reg_trans.id AS reg_trans_id, "
			. "reg.badge_num, reg.year, reg.badge_name, "
			. "reg_payment_type.payment_type, "
			. "reg_trans_type.trans_type, "
			. "reg_cc_type.cc_type, "
			. "reg_trans_gateway.*, "
			. "users.uid, users.name "
			. "FROM {reg_trans} "
			. "LEFT JOIN {reg} ON reg_trans.reg_id = reg.id "
			. "LEFT JOIN {reg_trans_type} "
				. "ON reg_trans_type_id = reg_trans_type.id "
			. "LEFT JOIN {reg_payment_type} "
				. "ON reg_payment_type_id = reg_payment_type.id "
			. "LEFT JOIN {reg_cc_type} "
				. "ON reg_cc_type_id = reg_cc_type.id "
			. "LEFT JOIN {reg_trans_gateway} "
				. "ON reg_trans_gateway_id = reg_trans_gateway.id "
			. "LEFT JOIN {users} ON reg_trans.uid = users.uid "
			. "WHERE "
			. "reg_trans.id='%s' ";
		$query_args = array($id);
		$cursor = db_query($query, $query_args);
		$retval = db_fetch_array($cursor);
		$retval["url"] = check_url($row["url"]);
		$retval["referrer"] = check_url($row["referrer"]);

		return($retval);

	} // End of trans_detail_data()


} // End of reg_log_view class

