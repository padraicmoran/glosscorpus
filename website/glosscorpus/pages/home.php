<?php
require 'functions/list_collections.php';
require "functions/models/get_overall_totals.php";

require "template/template.php";
templateHeader($pageTitle, $carouselSlug);

$totals = getOverallTotals();
?>

<div class="container px-0">
	<div class="col-xl-9">

<p class="display-6 mb-4">Open-access publishing for medieval glosses
</p>

<p>Gloss Corpus is a platform for publishing and analysis of glosses on medieval manuscripts, 
	based on Open Access and open standards for data.
</p>

<p>Contributors receive full credit for their work, with 
	each edition of glosses assigned a unique DOI and persistent URL. 
	Contributors also have the option to avail of peer-review.
</p>

<p>As well as a reading and browsing interface, the resource currently offers a simple search feature. 
	Tools for more complex searches, visualisations
	and analytics are currently in development. 
	Read more <a href="?page=about">about the project&rarr;</a>
</p>

<h2 class="h3 mt-5">Collections currently available</h2>

<p>Gloss	 Corpus currently holds <strong><?php echo $totals[1]; ?></strong> collections of glosses on <strong><?php echo $totals[0]; ?></strong> primary texts, 
comprising <strong><?php echo number_format($totals[2]); ?></strong> glosses in total.
To browse a collection, click on a primary text below and then choose a starting section. </p>

	</div>
</div>

<?php
listAllCollections();
templateFooter();
?>