<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * NTICompass' CodeIgniter Subquery Library
 * (Requires Active Record and PHP5)
 *
 * By: Eric Siegel
 * http://NTICompassInc.com
 */
class Subquery{
	var $CI;
	var $db;
	var $statement;
	var $join_type;
	var $join_on;

	function __construct(){
		$this->CI =& get_instance();
		$this->db = array();
		$this->statement = array();
		$this->join_type = array();
		$this->join_on = array();
	}

	/**
	 * start_subquery - Creates a new database object to be used for the subquery
	 *
	 * @param $statement - SQL statement to put subquery into (select, from, join, etc.)
	 * @param $join_type - JOIN type (only for join statements)
	 * @param $join_on - JOIN ON clause (only for join statements)
	 *
	 * @return A new database object to use for subqueries
	 */
	function start_subquery($statement, $join_type='', $join_on=1){
		$db = $this->CI->load->database('', true);
		$this->db[] = $db;
		$this->statement[] = $statement;
		if(strtolower($statement) == 'join'){
			$this->join_type[] = $join_type;
			$this->join_on[] = $join_on;
		}
		return $db;
	}

	/**
	 * start_union - Creates a new database object to be used for unions
	 *
	 * NOTE: Please do all 'ORDER BY' or other modifiers BEFORE start_union
	 *
	 * @return A new database object to use for a union query
	 */
	 function start_union(){
		 return $this->start_subquery('');
	 }

	/**
	 * end_subquery - Closes the database object and writes the subquery
	 *
	 * @param $alias - Alias to use in query
	 *
	 * @return none
	 */
	function end_subquery($alias=''){
		$db = array_pop($this->db);
		$sql = "({$db->_compile_select()})";
		$alias = $alias!='' ? "AS $alias" : $alias;
		$statement = array_pop($this->statement);
		$database = (count($this->db) == 0)
			? $this->CI->db: $this->db[count($this->db)-1];
		if(strtolower($statement) == 'join'){
			$join_type = array_pop($this->join_type);
			$join_on = array_pop($this->join_on);
			$database->$statement("$sql $alias", $join_on, $join_type);
		}
		else{
			$database->$statement("$sql $alias");
		}
	}

	/**
	 * end_union - Combines all opened db objects into a UNION ALL query
	 *
	 * @param none
	 *
	 * @return none
	 */
	 function end_union(){
		$queries = array();
		$this->db = array_reverse($this->db);
		while($db = array_pop($this->db)){
			$queries[] = $db->_compile_select();
			array_pop($this->statement);
		}
		if(substr($queries[0], 0, 6) == 'SELECT'){
			$queries[0] = substr($queries[0], 7);
		}
		$sql = implode(' UNION ALL ', $queries);
		$this->CI->db->select($sql, false);
	 }

	/**
	 * join_range - Helper function to CROSS JOIN a list of numbers
	 *
	 * @param $start - Range start
	 * @param $end - Range end
	 * @param $alias - Alias for number list
	 * @param $table_name - JOINed tables need an alias(Optional)
	 */
	function join_range($start, $end, $alias, $table_name='q'){
		$range = array();
		foreach(range($start, $end) AS $r){
			$range[] = "SELECT $r AS $alias";
		}
		$range[0] = substr($range[0], 7);
		$range = implode(' UNION ALL ', $range);

		$sub = $this->start_subquery('join', 'inner');
		$sub->select($range, false);
		$this->end_subquery($table_name);
	}
}

?>