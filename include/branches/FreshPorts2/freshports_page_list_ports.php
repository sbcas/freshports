<?php
	#
	# $Id: freshports_page_list_ports.php,v 1.1.2.2 2005-01-23 03:11:21 dan Exp $
	#
	# Copyright (c) 2005 DVL Software Limited
	#


	require_once($_SERVER['DOCUMENT_ROOT'] . '/include/freshports_page.php');

class freshports_page_list_ports extends freshports_page {

	var $_sql;

    function freshports_page_list_ports($attributes = array()) {
		$this->freshports_page($attributes);
	}

	function getSQL() {
		return $this->_sql;
	}

	function setSQL($Condition, $UserID=0) {
		$this->_sql = "
SELECT ports.id, 
       element.name    as port,  
       categories.name as category, 
       ports.category_id, 
       version         as version, 
       revision        as revision,
       ports.element_id,
       maintainer, 
       short_description, 
       to_char(ports.date_added - SystemTimeAdjust(), 'DD Mon YYYY HH24:MI:SS') as date_added,
       last_commit_id  as last_change_log_id,
       package_exists,
       extract_suffix,
       homepage,
       status,
       broken,
       forbidden,
       latest_link ";

	if ($UserID) {
		$this->_sql .= ",
         onwatchlist";
   }

	$this->_sql .= "
from element, categories, ports ";

	if ($UserID) {
			$this->_sql .= '
      LEFT OUTER JOIN
 (SELECT element_id as wle_element_id, COUNT(watch_list_id) as onwatchlist
    FROM watch_list JOIN watch_list_element
        ON watch_list.id      = watch_list_element.watch_list_id
       AND watch_list.user_id = ' . AddSlashes($UserID) . '
       AND watch_list.in_service
  GROUP BY wle_element_id) AS TEMP
       ON TEMP.wle_element_id = ports.element_id';
	}
	

	$this->_sql .= "
WHERE ports.element_id  = element.id
  and ports.category_id = categories.id 
  and status            = 'A' 
  and " . $Condition;

	$this->_sql .= " order by " . $this->getSort();
#	$this->_sql .= " limit 20";

	}

	function getPorts() {
		$HTML = '';

		if ($this->getDebug()) {
			$HTML .= '<pre>' . $this->getSQL() . '</pre>';
		}

		$result = pg_exec($this->_db, $this->getSQL());
		if (!$result) {
			echo pg_errormessage();
		} else {
			$numrows = pg_numrows($result);
#			echo "There are $numrows to fetch<BR>\n";
		}

		require_once($_SERVER['DOCUMENT_ROOT'] . '/include/list-of-ports.php');

		$HTML .= freshports_ListOfPorts($result, $this->_db, 'Y', $ShowCategoryHeaders);

		return $HTML;
	}

	function getSort() {
		$HTML = '';

		if (IsSet( $_REQUEST["sort"])) {
			$sort = $_REQUEST["sort"];
		} else {
			$sort = '';
		}

		switch ($sort) {
			case 'dateadded':
				$sort = 'ports.date_added desc, category, port';
				$HTML .= 'sorted by date added.  but you can sort by <a href="' . $_SERVER["PHP_SELF"] . '?sort=category">category</a>';
				$ShowCategoryHeaders = 0;
				break;

			default:
				$sort ='category, port';
				$HTML .= 'sorted by category.  but you can sort by <a href="' . $_SERVER["PHP_SELF"] . '?sort=dateadded">date added</a>';
				$ShowCategoryHeaders = 1;
		}

		return $sort;
	}


	function getSortedbyHTML() {
		$HTML = '';

		$sort = $this->getSort();

		switch ($sort) {
			case 'dateadded':
				$HTML .= 'sorted by date added.  but you can sort by <a href="' . $_SERVER["PHP_SELF"] . '?sort=category">category</a>';
				break;

			default:
				$HTML .= 'sorted by category.  but you can sort by <a href="' . $_SERVER["PHP_SELF"] . '?sort=dateadded">date added</a>';
		}

		return $HTML;
	}



	function toHTML() {

		$this->addBodyContent('<TR><TD>
These are the broken ports.
</TD></TR>');

		// make sure the value for $sort is valid

		$HTML = "<TR><TD>\nThis page is " . $this->getSortedbyHTML();

		$HTML .= "</TD></TR>\n";

		$this->addBodyContent($HTML);

		$this->addBodyContent($this->getPorts());

		return parent::toHTML();
	}
}
