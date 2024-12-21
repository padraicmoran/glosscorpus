<?php
require "../functions/core/application.php";
require "common.php";

require "../pages/template/template.php";
templateHeader('CMS', '');

$alertTypes = initaliseAlerts();
?>

<a class="float-end btn btn-primary btn-sm btn-secondary" href="./">CMS home</a>
<h2>Notes on adding new content</h2>

<p>Run these SQL commands to create temporary records, then use the CMS update tools to update content.</p>

<p>Replace values in CAPITAL LETTERS with real values.</p>

<h3 class="h5 mt-4">New base text</h3>
<pre><code>Insert Into base_texts (title, short_ref, slug, publish)
      Values ('Temp', 'Temp', 'BASETEXT_SLUG', 1);</code></pre>



<h3 class="h5 mt-5">New gloss collection</h3>
<p>(Requires an existing publication record. See below.)</p>

<pre><code>Insert Into gloss_collections (base_textID, description, publicationID, slug, siglum, publish)
   Values (BASETEXT_ID, 'Temp', PUBLICATION_ID, 'COLLECTION_SLUG', '', 1);</code></pre>


      
<h3 class="h5 mt-5">New publication</h3>

<pre><code>Insert Into publications (citation, slug)
      Values ('Temp', 'PUBLICATION_SLUG');</code></pre>



<?php

// template
templateFooter();
?>
