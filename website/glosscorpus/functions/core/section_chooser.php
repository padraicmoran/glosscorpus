<?php

// Show section chooser and current heading
function sectionChooser($baseTextID, $sectionID) {
   global $link, $collectionGetString, $sectionIDs, $sectionIndex;

	// load sections 
	$sqlS = 'Select recordID, title From base_text_sections Where base_textID = ' . $baseTextID . ' Order By order_index';
	$resultsS = mysqli_query($link, $sqlS);
   
	if (mysqli_num_rows($resultsS) == 0) {
      echo 'No sections found.';
   }
   else {

      $sectionTitles = array();
      $sectionIDs = array();
      $sectionIndex = -1;

      // build arrays for later use
      if (mysqli_num_rows($resultsS) > 0) {
         $cnt = 0;
         while ($rowS = mysqli_fetch_assoc($resultsS)) {
            $sectionTitles[] = $rowS['title'];
            $sectionIDs[] = $rowS['recordID'];
            if ($rowS['recordID'] == $sectionID) $sectionIndex = $cnt;
            $cnt ++;
         }

         echo '<div class="bg-light border my-5 p-2 border-top border-3">';

         // section navigation list
         echo '<div id="sectionNav" class="row">';
         echo '<div class="col-sm-3 h4 py-2">Showing section:</div>';
         echo '<div class="col-sm-9"><select name="sec" class="form-select form-select-lg mb-2" onchange="this.form.submit(); ">';
         for ($n = 0; $n < count($sectionIDs); $n ++) {
            echo '<option value="' . $sectionIDs[$n] . '"';
            if ($sectionIDs[$n] == $sectionID) echo ' selected="selected" class="h3"';
            echo '>' . $sectionTitles[$n] . '</option>';
         }
         echo '</select> &nbsp;';
      //		echo ' <input type="submit" value="go" class="btn btn-sm btn-secondary"> &nbsp; ';

         // section navigation buttoms
         if ($sectionIndex > 0) echo '<a class="btn btn-sm btn-secondary" href="?page=collection&sec=' . $sectionIDs[$sectionIndex - 1] . $collectionGetString . '#sectionNav">&larr; previous</a> ';
         if ($sectionIndex < count($sectionIDs) - 1) echo '<a class="btn btn-sm btn-secondary" href="?page=collection&sec=' . $sectionIDs[$sectionIndex + 1] . $collectionGetString . '#sectionNav">next &rarr;</a> &nbsp; ';

      }
   }
}

// final nav
function finalNav() {
   global $collectionGetString, $sectionIDs, $sectionIndex;
	if ($sectionIndex > 0) echo '<a class="btn btn-sm btn-secondary" href="?page=collection&sec=' . $sectionIDs[$sectionIndex - 1] . $collectionGetString . '#sectionNav">&larr; previous</a> ';
	if ($sectionIndex < count($sectionIDs) - 1) echo '<a class="btn btn-sm btn-secondary" href="?page=collection&sec=' . $sectionIDs[$sectionIndex + 1] . $collectionGetString . '#sectionNav">next &rarr;</a> &nbsp; ';
}

?>