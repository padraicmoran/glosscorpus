<?php

// Retrieve info about base text
function getBaseTextInfo($sectionID) {
   global $link;

	$sqlB = "Select 
			b.recordID, b.author, b.title As b_title, b.source_info, b.source_credit, b.seg_desc, b.slug, b.about,
			sec.title As sec_title
		From 
			base_texts b 
			Inner Join base_text_sections sec
			On sec.base_textID = b.recordID
		Where 
			b.publish = 1 And
			sec.recordID = " . $sectionID;
	$resultsB = mysqli_query($link, $sqlB);
   return $resultsB;
}

?>