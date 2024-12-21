<?php
require "../functions/core/application.php";
require "common.php";

require "../pages/template/template.php";
templateHeader('CMS', '');

?>

<h2>Content Management System (CMS)</h2>

<p>Intended for local deployment.
   Updates should be reviewed and tested before transfer to live server.
</p>

<div class="mb-1"><a class="btn btn-secondary" href="update_base_text.php">Update base text</a></div>
<div class="mb-1"><a class="btn btn-secondary" href="update_gloss_collection.php">Update gloss collection</a></div>
<div class="mb-1"><a class="btn btn-secondary" href="update_publication.php">Update publication info</a></div>

<p class="mt-5">Other resources:</p>

<ul>
<li><a href="update_totals.php">Update total counts</a></li>
<li><a href="notes.php">Notes on adding new content</a></li>
<li><a href="export_base_text.php">Export base text</a></li>
<li><a href="export_gloss_collection.php">Export gloss collection</a></li>
</ul>


<?php

// template
templateFooter();
?>