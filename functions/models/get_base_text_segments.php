<?php

// Query database and return base text segments
function getBaseTextSegments($sectionID, $search) {
   global $link;

   if ($search) {
      $sqlS = "Select 
            b.short_ref,
            s.recordID, s.sectionID, s.ref, s.text
         From 
            base_text_segments s 
         Inner Join 
            glosses g 
            On g.segmentRecordID = s.recordID
         Inner Join 
            base_texts b 
            On s.base_textID = b.recordID
         Where 
            g.text_searchable Like '%$search%' And
            b.publish = 1
         Group 
            By s.recordID
         Order By 
            b.short_ref, s.order_index ";
   }
   else {
      $sqlS = "Select 
            s.recordID, s.sectionID, s.ref, s.text
         From 
            base_text_segments s 
         Left Join 
            base_texts b 
            On s.base_textID = b.recordID
         Where 
            b.publish = 1 
            And s.sectionID = $sectionID 
         Order By 
            s.order_index ";
   }
   $resultsS = mysqli_query($link, $sqlS);
   return $resultsS;
}

?>