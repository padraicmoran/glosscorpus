<?php
require "functions/models/get_base_text_segments.php";
require "functions/write_base_text_segment.php";
require "functions/models/get_glosses.php";
require "functions/write_gloss.php";

// Retrieve glosses for current section
// Write lines of base text and call function to format glosses
function displayGlosses($sectionID) {
	global $link, $collectionList;

	if ($collectionList != '') {

		$segmentHighlight = cleanInput('segh');

		// All lines or with glosses only
		$resultsS = getBaseTextSegments($sectionID, null);
		$resultsG = getGlosses($sectionID, null);

		if (mysqli_num_rows($resultsS) == 0) {
			echo 'No content found to match this selection.';
		}
		else {
			// Show totals	
			echo '<div class="py-3">This section has <strong>' . number_format(mysqli_num_rows($resultsS)) . '</strong> ' . switchSgPl(mysqli_num_rows($resultsS), 'segment', 'segments') . ', with a total of ';
			echo '<strong>' . number_format(mysqli_num_rows($resultsG)) . '</strong> ' . switchSgPl(mysqli_num_rows($resultsG), 'gloss', 'glosses') . '.</div>';

			echo '</div>';
			echo '</div>';
			echo '</div>';

			echo '<table class="table">';

			// Load in first gloss
			$rowG = mysqli_fetch_assoc($resultsG);

			// Loop through lines of base text
			while ($rowS = mysqli_fetch_assoc($resultsS)) {

				$hi = "";
				if ($segmentHighlight == $rowS['recordID']) $hi = 'highlight';	

				// Prepare a table row for base text
				writeBaseTextSegment($rowS);
				$lemmata = getBaseTextLemmata($rowS['text']);

				// Write glosses for this line
				while (isset($rowG['segmentRecordID']) && $rowG['segmentRecordID'] == $rowS['recordID']) {
					writeGlossRow($rowG, $lemmata);
					$rowG = mysqli_fetch_assoc($resultsG); // load in next gloss
				}
			}
			echo '</table>' . "\r\n";
		}
	}
?>
<script>

// Highlighted row is normally covered by header bar, so move down a bit

window.onload = showHighlight;
function showHighlight() {
	if (location.hash === 'hi') {
		window.scrollBy(0,-400);
	}
}
</script>
<?php
}
?>