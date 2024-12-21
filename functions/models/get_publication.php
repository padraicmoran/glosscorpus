<?php

// Query database and return publication info
function getPublication($slugPub) {
   global $link;

   $sql = "
      Select 
         c2.count_glosses,
         b.recordID As b_recordID, b.author, b.title, b.source_info, b.source_credit, b.seg_desc, b.slug as b_slug,
         c.recordID As c_recordID, c.siglum, c.colour, c.ms_city, c.ms_library, c.ms_collection, c.ms_shelfmark, c.credits as c_credits, 
         c.slug as c_slug,
         p. citation, p.publish_date, p.doi, p.about as p_about
      From 
         base_texts b
      Inner Join 
         gloss_collections c 
         On c.base_textID = b.recordID
      Inner Join
         publications p
         On c.publicationID = p.recordID
      Inner Join 
         (Select c.recordID, Count(*) As count_glosses
         From gloss_collections c
         Inner Join glosses g On g.collectionID = c.recordID
         Group By c.recordID) As c2
         On c2.recordID = c.recordID
      Where 
         p.slug = '" . $slugPub . "' And 
         b.publish = 1 And
         c.publish = 1 And
         p.publish = 1
      Order By
         c.ms_city, c.ms_library, c.siglum ";

   $results = mysqli_query($link, $sql);
   return $results;
}

?>