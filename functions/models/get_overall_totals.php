<?php

// Retrieve overall totals from db
function getOverallTotals() {
	global $link;

	// get overall totals from db
	$sql = "Select 
			base_texts, collections, glosses 
		From 
			totals_published
		Order By 
			timestamp Desc 
		Limit 1";
	$results = mysqli_query($link, $sql);

	if (mysqli_num_rows($results) > 0) {
		$row = mysqli_fetch_assoc($results);
		$totals[] = $row['base_texts'];
		$totals[] = $row['collections'];
		$totals[] = $row['glosses'];
	}
	else {
		$totals = array(0, 0, 0);
	}
	return $totals;
}
?>