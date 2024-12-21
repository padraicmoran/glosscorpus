<?php
require 'functions/list_base_text_sections.php';

// List all collections for a specified base text
function listCollections($baseTextID, $singleCollection, $baseTextTitle) {
   global $link, $collectionList, $collectionGetString;

   // check which collections are available
   $sqlC = "Select 
         c.recordID, c.ms_city, c.ms_library, c.ms_collection, c.ms_shelfmark, c.siglum, c.colour, c.credits_short,
         c2.count_glosses,
         p.slug as p_slug
      From 
         gloss_collections c 
      Inner Join
         base_texts b 
         On c.base_textID = b.recordID	
      Inner Join publications p
         On c.publicationID = p.recordID
      Inner Join 
         (Select c.recordID, c.publish, Count(*) As count_glosses
         From 
            gloss_collections c
         Inner Join 
            glosses g 
            On g.collectionID = c.recordID
         Group By c.recordID
         Having c.publish = 1) 
         As c2
         On c2.recordID = c.recordID
      Where 
         c.base_textID = " . $baseTextID . " And 
         b.publish = 1 And
         c.publish = 1 And
         p.publish = 1
   Order by 
         c.siglum ";

   $resultsC = mysqli_query($link, $sqlC);
   $collectionsTotal = mysqli_num_rows($resultsC);

   if ($collectionsTotal == 0) {
      echo '<!--No collections found for primary text: ID ' . $baseTextID . '-->';
   }
   else {
      // Compile total of all glosses
      $sumTotalGlosses = 0;
      while ($row = mysqli_fetch_assoc($resultsC)) {
         $sumTotalGlosses += $row['count_glosses'];
      }	
      mysqli_data_seek($resultsC, 0);

      // Check what collections are selected
      //	If none, set all
      $collectionList = '-1'; 			// dummy value to start
      $collectionListFull = '-1';		// contingent list in case none selected
      $collectionGetString = ''; 		// for use in link-backs
      $collectionGetStringFull = '';	// contingent list

      // compile lists for later use
      while ($row = mysqli_fetch_assoc($resultsC)) {
         // compile list of collections
         if (cleanInput('c' . $row['recordID']) == 1) {
            $collectionList .= ',' . $row['recordID'];
            $collectionGetString .= '&c' . $row['recordID'] . '=1';
         }
         $collectionListFull .= ',' . $row['recordID'];
         $collectionGetStringFull .= '&c' .  $row['recordID'] . '=1';
      }
      if ($collectionGetString == '') {
         $collectionList = $collectionListFull;
         $collectionGetString = $collectionGetStringFull;
      }


      // Build Bootstrap accordion
      // Prepare settings
      $cntCollections = $collectionsTotal . ' gloss ' . switchSgPl($collectionsTotal, 'collection', 'collections');
      $cntTotalGlosses = number_format($sumTotalGlosses) . ' ' . switchSgPl($sumTotalGlosses, 'gloss', 'glosses') . ' total';
      if ($singleCollection) {
         $buttonClass = '';
         $bodyClass = ' show';
         $heading = '<span class="h5">' . $cntCollections . ', ' . $cntTotalGlosses . '</span';
      }
      else {
         $buttonClass = ' collapsed';
         $bodyClass = '';
         $heading = '<div class="container"><div class="row">' . 
            '<div class="col-6 h5">' . $baseTextTitle . '</div>' . 
            '<div class="col-3">' . $cntCollections . '</div>' . 
            '<div class="col-3">' . $cntTotalGlosses . '</div>' .
            '</div></div>';
      }

      // Accordion header (clickable part, with overview info)
		echo '<div class="accordion-item">';
      echo '<div class="accordion-header" id="heading' . $baseTextID . '">';
      echo '<button class="accordion-button' . $buttonClass . '" type="button" 
         data-bs-toggle="collapse" data-bs-target="#collapse' . $baseTextID . '" aria-expanded="true" aria-controls="collapse' . $baseTextID . '">';
      echo $heading;
      echo '</button>';
      echo '</div>';

      // Start accordion body (containing list of collections)
      echo '<div id="collapse' . $baseTextID . '" class="accordion-collapse collapse' . $bodyClass . '" aria-labelledby="heading' . $baseTextID . '">';
      echo '<div class="accordion-body mb-5">';

      echo '<input type="hidden" name="page" value="collections">';

      // Show collections check boxes
      // Reset results array
      mysqli_data_seek($resultsC, 0);

      echo '<table class="table table-striped small" id="collections' . $baseTextID . '">';
      while ($row = mysqli_fetch_assoc($resultsC)) {
         $msRef = $row['ms_city'] . ', ' . $row['ms_library'] . ', ' . $row['ms_collection'] . ' ' . $row['ms_shelfmark'];

         // list collections
         echo '<tr>';
         echo '<td>';
         if ($collectionsTotal > 1) {
            echo '<input type="checkbox" id="c' . $row['recordID'] . '" name="c' . $row['recordID'] . '" value="1" onclick="highlightUpdateButton(); " ';
            if (strpos($collectionGetString, '&c' . $row['recordID'] . '=') !== false) echo ' checked="checked"';
            echo '>';
         }
         echo '</td>';
         echo '<td><div class="siglumBox" style="background-color: #' . $row['colour'] . '"><b>' . $row['siglum'] . '</b></div></td>';
         if ($collectionsTotal > 1) {
            echo '<td><label for="c' . $row['recordID'] . '">' . $msRef . '</label></td>';
         }
         else {
            echo '<td>' . $msRef . '</td>';
         }
         echo '<td class="text-end">' . $row['credits_short'] . '</td>';
         echo '<td style="width: 170px; " class="text-end">' . number_format($row['count_glosses']) . ' ' . switchSgPl($row['count_glosses'], 'gloss', 'glosses') . '</td>';
         echo '<td style="width: 110px; " class="text-end"><a class="btn btn-sm btn-primary text-nowrap" role="button" href="publications/' . $row['p_slug'] . '">credit</a></td>';
         echo '</tr>' . "\n";
      }
      echo '</table>';

      // display options for single collection or part of full list
      if ($singleCollection) {
         if ($collectionsTotal > 1) {
            echo '<div class="small pt-2 pb-2">Select <a href="#" onclick="check(' . $baseTextID . ', true); return false; ">all</a> | <a href="#"  onclick="check(' . $baseTextID . ', false); return false; ">none</a></div>' . "\n";
            echo '<div class="pt-4"><input id="btnUpdateCollections" type="submit" value="Update selection" class="btn btn-secondary btn-sm "></div>' . "\n";
         }
      }
      else {
         if ($collectionsTotal > 1) {
            echo '<div class="small pt-2 pb-2">Select <a href="#" onclick="check(' . $baseTextID . ', true); return false; ">all</a> | <a href="#"  onclick="check(' . $baseTextID . ', false); return false; ">none</a></div>' . "\n";
         }
         echo '<div class="pt-4">Go to section: ';
         listBaseTextSections($baseTextID, 0);
         echo ' <input type="submit" value="go" class="btn btn-sm btn-primary"></div>';
      }

      // end accordion body
      echo '</div>';
      echo '</div>';
		echo '</div>';

   }
}

// list all collections
function listAllCollections() {
   global $link;

   // get list of base texts
   $sql = "Select 
         b.recordID, b.author, b.title
      From 
         base_texts b
      Left Join 
         gloss_collections c 
         On c.base_textID = b.recordID
      Left Join
         publications p
         On c.publicationID = p.recordID
      Where 
         b.publish = 1 And
         c.publish = 1 And
         p.publish = 1
      Group By
         b.recordID, b.author, b.title
      Order By
         b.author, b.title";

   $results = mysqli_query($link, $sql);
   if (mysqli_num_rows($results) == 0) {
      echo '<p>No primary texts found.</p>';
   }
   else {
      while ($row = mysqli_fetch_assoc($results)) {
         $baseTextTitle = '' . $row['author']  . ', <i>' . $row['title'] . '</i>'; 
         echo '<form>';
         listCollections($row['recordID'], false, $baseTextTitle);
         echo '</form>';
      }
   }
}

?>