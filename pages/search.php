<?php
require "functions/models/get_base_text_segments.php";
require "functions/write_base_text_segment.php";
require "functions/models/get_glosses.php";
require "functions/write_gloss.php";
require "template/template.php";

// read inputs
// (need to improve security)
$search = cleanInput('s');
if ($search == '') $pageTitle = 'Search';
else $pageTitle = 'Search results for "' . $search . '"';

templateHeader($pageTitle, $carouselSlug);

echo '<h2>Search</h2>';

echo '<form method="get">';
echo '<input type="hidden" name="page" value="search">';

echo '<p>Search: ';
echo '<input name="s" type="text" value="' . $search . '"> ';
echo '<input type="submit" class="btn-sm btn-primary" value="search"></p>';
echo '</form>';

if ($search == '') {
	echo '<p>The current basic section function will be extended to provide a variety of search options.</p>';
}
else {
	// display results

	// get base text segments where there are matching glosses
	$resultsS = getBaseTextSegments(null, $search);
	$resultsG = getGlosses(null, $search);

	if (mysqli_num_rows($resultsG) == 0) {
	   echo '<p>No results.</p>';
	}
	else {
		echo '<div class="p-2 my-5"><span class="bg-secondary rounded text-white p-2 mr-2">';
		if (mysqli_num_rows($resultsG) == 1) echo '1 result';
		else echo mysqli_num_rows($resultsG) . ' results';
		echo '</span> (Click on a primary text or gloss reference to go the context.)</div>';
	   echo '<table class="table">';
	
		// load in first gloss
		$rowG = mysqli_fetch_assoc($resultsG);

		// loop through lines of base text
	   while ($rowS = mysqli_fetch_array($resultsS)) {

			$hi = "";
	      echo '<tr class="bg-light">';
         echo '<td class="small' . $hi . ' text-nowrap"><a class="text-muted underline" title="See this line in context." href="' . '?sec=' . $rowS['sectionID'] . '&segh=' . $rowS['recordID'] . '&glo=#hi">' . $rowS['short_ref'] . ', ' . $rowS['ref'] . '</a></td>';
         echo '<td colspan="6" class="' . $hi . '">';
			echo renderBaseTextSegment($rowS['text']);
			echo '</td>';
	      echo '</tr>';

	      // glosses for this line
			while (isset($rowG['segmentRecordID']) && $rowG['segmentRecordID'] == $rowS['recordID']) {
				writeGlossRow($rowG, null);
				$rowG = mysqli_fetch_assoc($resultsG); // load in next gloss
			}
	   }
	   echo '</table>' . "\r\n";
	}
}

templateFooter();
?>