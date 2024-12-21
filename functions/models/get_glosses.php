<?php

// Query database and return glosses
function getGlosses($sectionID, $search) {
   global $link, $collectionList;

   // Start of functions/core construction
   $sqlG = "Select 
      g.recordID, g.segmentRecordID, g.word_index, g.ref, g.text, 
      c.siglum, c.colour,
      s.sectionID
   From 
      glosses g
   Inner Join 
      gloss_collections c On g.collectionID = c.recordID 
   Inner Join 
      base_text_segments s On g.segmentRecordID = s.recordID
   Inner Join 
      base_texts b On s.base_textID = b.recordID
   Inner Join 
      publications p On c.publicationID = p.recordID
   Where ";

   // Conditions
   if ($sectionID) {
      $sqlG .= "
         s.sectionID = $sectionID 
         And c.recordID In ($collectionList) ";
   }
   elseif ($search) {
      $sqlG .= "
         g.text_searchable Like '%$search%' ";
   }

   // End of construction
   $sqlG .= "
      And b.publish = 1 
      And c.publish = 1 
      And p.publish = 1
   Order By 
      b.short_ref, s.order_index, 
      Case When g.word_index Is Null Then 1 Else 0 End,	
      g.word_index, g.order_index, 
      c.siglum";

   $resultsG = mysqli_query($link, $sqlG);
   return $resultsG;
}

?>