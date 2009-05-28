<?php
/**
* This class is responsible for managing and searching out watchlist.
*/
class Reg_Util_Watchlist {


	function __construct(&$reg) {
		$this->reg = $reg;
	}


	/**
	* Insert a new watchlist entry.
	*
	* @param array $data Associative array of data to insert
	*
	* @return integer The ID of the record that was inserted.
	*/
	function insert(&$data) {

		$query = "INSERT INTO {reg_watchlist} "
			. "(first, last, first_alias, action, disabled) "
			. "VALUES ('%s', '%s', '%s', '%s', '%s') "
			;
		$query_args = array(
			$data["first"], $data["last"], $data["first_alias"], 
			$data["action"], $data["disabled"]
			);
		db_query($query, $query_args);

		$retval = $this->reg->get_insert_id();

		return($retval);

	} // End of insert()


	/**
	* Load a record from the watchlist.
	*
	* @param integer $id The ID of the row in the database.
	*
	* @return array Associative array of the row from the database.
	*/
	function load($id) {

		$retval = array();

		$query = "SELECT * FROM {reg_watchlist} WHERE id='%s'";
		$query_args = array($id);
		$cursor = db_query($query, $query_args);
		$retval = db_fetch_array($cursor);

		return($retval);

	} // End of load()


	/**
	* This function updates an existing row in the table.
	*
	* @param integer $id The ID of the row to update
	*
	* @param arary $data Array of new values to insert.  Note that 
	*/
	function update($id, &$data) {

		$query = "UPDATE {reg_watchlist} "
			. "SET "
			. "first='%s', last='%s', first_alias='%s', action='%s', "
			. "disabled='%s' "
			. "WHERE "
			. "id='%s' "
			;
		$query_args = array($data["first"], $data["last"], 
			$data["first_alias"], $data["action"], $data["disabled"],
			$id);
		db_query($query, $query_args);

	} // End of update()


	/**
	* Get all rows from the table.
	*
	* @return array an array of all rowse tablesort_sql() somewhere...
	*/
	function getAll() {

		$retval = array();

		$query = "SELECT * FROM {reg_watchlist} "
			. "ORDER BY last, first";
		$cursor = db_query($query);
		
		while ($row = db_fetch_array($cursor)) {
			$retval[] = $row;
		}

		return($retval);

	} // End of getAll()


	/**
	* Sanitize a string by doing things like lowercasing it,
	* and removing non-alphabetic characters. (but allowing those 
	* that can be used in a regexp)
	*
	* @param integer $string The string to sanitize.
	*
	* @return string The sanitized string
	*/
	protected function sanitize($string) {

		$retval = strtolower($string);
		$retval = ereg_replace("[^a-z\|\(\)\^]", "", $retval);
		return($retval);

	} // End of sanitize()


	/**
	* Search the watchlist for a particular member.
	*
	* @param array $data An array containining the member's first 
	*	name and last name.
	*
	* @return mixed False if there is no match, or an array with the row 
	*	from the table if there is a match.
	*/
	function search(&$data) {

		//
		// Rexmove junk from this string to make for better matching
		//
		$data["first"] = $this->sanitize($data["first"]);
		
		//
		// Query for anything matching the last name.  Then go through
		// each result and see if it matches the first name or the alias.
		// This approach should keep from hammering the database and/or
		// the CPU excessively.
		//
		$query = "SELECT * FROM {reg_watchlist} "
			. "WHERE "
			. "last LIKE '%s' "
			;
		$query_args = array($data["last"]);

		$cursor = db_query($query, $query_args);
		while ($row = db_fetch_array($cursor)) {

			//
			// If this row is disabled, skip matching against it.
			//
			if ($row["disabled"]) {
				continue;
			}

			$first = $this->sanitize($row["first"]);
			if ($data["first"] == $first) {
				return($row);
			}

			//
			// Check to see if the first name matches a subset of the name 
			// given.  This will catch issues when the name in the watchlist 
			// is "John" and the member gives us "Johnny".
			//
			$found = strpos($data["first"], $first, 0);;
			if ($found === 0) {
				return($row);
			}

			$first_alias = $this->sanitize($row["first_alias"]);
			if ($data["first"] == $first_alias) {
				return($row);
			}

			//
			// Finally, if there's no match on the first name or alias, treat
			// the alias as a regexp and check it.
			//
			if (!empty($first_alias)) {
				if (eregi($first_alias, $data["first"])) {
					return($row);
				}
			}

		}
		
		//
		// Assume failure
		//
		return(false);

	} // End of search()


} // End of Reg_Util_Watchlist class


