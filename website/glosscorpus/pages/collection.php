<?php
require "functions/models/get_base_text_info.php";
require "functions/list_collections.php";
require "functions/section_chooser.php";
require "functions/display_glosses.php";

require "template/template.php";

// read inputs
$slugBaseText = cleanInput('b');
$slugCollection = cleanInput('c');
$collectionInfoID = 0;
$msRef = null;

// confirm whether sectionID is valid
if ($sectionID != 0) {

	// get base text info (from sectionID)
	$resultsB = getBaseTextInfo($sectionID);
	if (mysqli_num_rows($resultsB) == 0) {
		// if primary text not found, will revert to home page afterwards
		$page = ''; 
	}
	else {
		// page header
		$rowB = mysqli_fetch_assoc($resultsB);
		$pageTitle = '' . $rowB['author'] . ', ' . $rowB['b_title'];
   	$carouselSlug = $rowB['slug'];
		templateHeader($pageTitle, $carouselSlug);

		// Create global variables used in various functions (value assigned in listCollections() )
		$collectionList = null;
		$collectionGetString = null;
		$sectionIDs = null;
	
      $baseTextID = $rowB['recordID'];

		// Base text info
      echo '<h2 class="mb-4">Glosses on ';
      echo '<i>' . $rowB['b_title'] . '</i></h2>';
      if ($rowB['about'] <> '') {
			echo '<p>' . $rowB['about'] . '</p>';
		}

      echo '<p class="mt-4 pb-3"><b>Source for primary text:</b> ' . str_replace('title>', 'i>', $rowB['source_info']) . ' ';
      if ($rowB['source_credit'] <> '') {
			echo $rowB['source_credit'] . ' ';
		}
      if ($rowB['seg_desc'] <> '') {
			echo $rowB['seg_desc'];
		}
      echo '</p>';

		// List available collections 
		echo '<form>';
		listCollections($rowB['recordID'], true, '');
	
		if ($collectionList != '') {
			sectionChooser($rowB['recordID'], $sectionID);
			displayGlosses($sectionID);
			finalNav();
		}
		echo '</form>';
	}
}

templateFooter();
?>