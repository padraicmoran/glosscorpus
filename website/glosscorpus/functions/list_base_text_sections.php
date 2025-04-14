<?php

// List base text section in a form select
function listBaseTextSections($baseTextID, $currentSection) {
	global $link;
	
	$sqlS = 'Select recordID, title From base_text_sections Where base_textID = ' . $baseTextID . ' Order By order_index';
	$resultsS = mysqli_query($link, $sqlS);
	if (mysqli_num_rows($resultsS) <> 0) {
		echo '<select name="sec">';
	   while ($rowS = mysqli_fetch_assoc($resultsS)) {
			writeOption($rowS['title'], $rowS['recordID'], $currentSection);
		}
		echo '</select>';
	}
}

?>