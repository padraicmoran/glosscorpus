<?php
require 'functions/models/get_publication.php';
require "functions/list_base_text_sections.php";

require "template/template.php";

$pageTitle = 'Publication information';
$carouselSlug = '';
templateHeader($pageTitle, $carouselSlug);

$slugPub = cleanInput('pub');

if ($slugPub != '') {
	
	$results = getPublication($slugPub);
	$totalCollections = mysqli_num_rows($results);
	if ($totalCollections > 0) {
		

		$totalGlosses = 0;
		while ($cnt = mysqli_fetch_column($results, 0)) {		
			$totalGlosses += $cnt;
		}

		mysqli_data_seek($results, 0);
		$row = mysqli_fetch_array($results);

		echo '<h2>Publication information</h2>';
		$stableURL = 'http://www.glossing.org/glosscorpus/publications/' . $slugPub;

		echo '<table class="table">';
		if (strtotime($row['publish_date'])) {
			$publishYear = date('Y', strtotime($row['publish_date']));
		}
		else {
			$publishYear = null;
		}

		$citation = $row['citation'] . 
			', in <i>Gloss Corpus</i> (' . $publishYear . '), DOI: ' .
			'<a href="https://doi.org/' . $row['doi'] . '">' . $row['doi'] . '<a>';

		printRow('Citation', $citation);
		if ($publishYear) printRow('Published on', date('j F Y', strtotime($row['publish_date'])));
		printRow('Stable URL', '<a href="' . $stableURL . '">' . $stableURL . '</a>');
		printRow('Total glosses', number_format($totalGlosses));
		printRow('About', '' . $row['p_about']);

		echo '<tr>';
		echo '<th>View glosses</th>';
		echo '<td>';
		echo '<form action="' . $baseURL . '">';
		echo '<p>Go to section: ';
		listBaseTextSections($row['b_recordID'], 0);
		echo ' <input type="submit" value="go" class="btn btn-sm btn-primary"></p>';
		while ($collection = mysqli_fetch_column($results, 7)) {		
			echo '<input type="hidden" name="c' . $collection . '" value="1">';
		}
		echo '</form>';
		echo '</td>';
		echo '</tr>';

		echo '</table>';

		// loop through collections
		mysqli_data_seek($results, 0);
		$currentBaseTextID = 0;
		while ($row = mysqli_fetch_assoc($results)) {
			
			// base text
			// (there should really only be one, but will show more if database says so!)
			if ($row['b_recordID'] <> $currentBaseTextID) {
				$currentBaseTextID = $row['b_recordID'];
				echo '<h3 class="h4 pt-4">Primary text</h3>';

				echo '<table class="table">';
				printRow('Author', $row['author']);
				printRow('Title', '<i>' . $row['title'] . '</i>');
				printRow('Source', $row['source_info']);
				printRow('Credits', $row['source_credit']);
				printRow('Segmentation', '<div class="small text-secondary">How the text is divided in this online edition:</div> ' . $row['seg_desc']);
				printRow('Data', fileLink($baseURL, 'data/texts/' . $row['b_slug'] . '/basetext.xml', 'XML file'));
				echo '</table>';

				echo '<h3 class="h4 pt-4">Gloss collection(s)</h3>';
			}

			// associated collections			
			echo '<table class="table mb-5">';
			printRow('Siglum', '<span class="siglumBox rounded" style="background-color: #' . $row['colour'] . '">' . $row['siglum'] . '</span>');
			printRow('Manuscript', trim($row['ms_city'] . ', ' . $row['ms_library'] . ', ' . $row['ms_collection'] . ' ' . $row['ms_shelfmark']));
			if ($totalCollections > 1) printRow('Glosses', number_format($row['count_glosses']));
			printRow('Credits', $row['c_credits']);
			printRow('Data', fileLink($baseURL, 'data/texts/' . $row['b_slug'] . '/gloss_collections/' . $row['c_slug'] . '.xml', 'XML file'));
			echo '</table>';
		}
	}
	else {
		echo '<p>No gloss collection found. See list on <a href="' . $baseURL . '/">home page</a>.</p>';
	}
}
else {
	echo '<p>No gloss collection found. See list on <a href="' . $baseURL . '/">home page</a>.</p>';
}

templateFooter();


function printRow($th, $td) {
	if ($td <> '') {
		echo '<tr>';
		echo '<th style="width: 180px; ">' . $th . '</th>';
		echo '<td>' . $td . '</td>';
		echo '</tr>';
	}
}

?>