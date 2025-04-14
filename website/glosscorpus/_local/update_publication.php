<?php
require "../functions/core/application.php";
require "common.php";

require "../pages/template/template.php";
templateHeader('CMS', '');

$alertTypes = initaliseAlerts();
echo '<a class="float-end btn btn-primary btn-sm btn-secondary" href="./">CMS home</a>';
echo '<h2>Update publication info</h2>';

$publicationID = intval(cleanInput('publicationID'));
$xmlText = cleanInput('xmlText');

$error = false;
$sqlOutput = array();

if ($publicationID == 0 || (! $filesystemInput && $xmlText == '')) {
	// choose options
	echo '<form method="post">';
	formSelectPublication();

	// textbox, if needed
	if (! $filesystemInput) {
		echo '<label for="xmlText" class="form-label mt-3">XML data:</label>';
		echo '<textarea id="xmlText" name="xmlText" class="form-control mb-2" rows="10"></textarea>';
	}

	// update options
	echo '<div class="form-check mt-2 small"><input type="radio" class="form-check-input" name="update" id="update1" value="n" checked><label class="form-check-label" for="update1">Validate XML only</label></div>';
	echo '<div class="form-check mt-2 small"><input type="radio" class="form-check-input" name="update" id="update2" value="y"><label class="form-check-label" for="update2">Validate XML and update database</label></div>';

	echo '<input type="submit" class="btn-sm btn-primary mt-3" value="go">';
	echo '</form>';
}
else {
	// start processing

	$sql = "Select citation, slug From publications Where recordID = " . $publicationID;
	$results = mysqli_query($link, $sql);

	if (mysqli_num_rows($results) == 0) {
		$error = true;
		alert('error', 'No publication found.');
	}
	else {
		$row = mysqli_fetch_assoc($results);
		$slug = $row['slug'];
		alert('info', 'Publication: ' . $row['citation'] . ' (ID ' . $publicationID . ')');
	}

	// load XML
	if (! $error) {
		libxml_use_internal_errors(true);

		// import XML file from filesystem
		if ($filesystemInput) {
			$importFile = '../../../data/publications/' . $slug . '.xml';
			if (! file_exists($importFile)) {
				$error = true;
				alert('error', 'Import file not found: ' . $importFile);
			}
			else {
				$xmlText = html_entity_decode($importFile);
				$xml = simplexml_load_file($xmlText);
			}
		}
		// or from pasted XML
		else {
			$xmlText = html_entity_decode($xmlText);
			$xml = simplexml_load_string($xmlText);
		}
	}
	
	// validate XML
	if (! $error) {
		// check for valid XML
		if (! $xml) {
			$error = true;
			foreach(libxml_get_errors() as $err) {
				alert('error', $err->message);
			}
		}
	}

	// build SQL update
	if (! $error) {
		alert('success',  'XML checked. ');

		if (date($xml->publish_date)) {
			$sqlPubDate = "" . $xml->publish_date . "";
		}
		else {
			$sqlPubDate = 'null';
		}

		$sqlOutput[] = "Update publications
			Set 
			citation = '" . esc(stripTag('citation', $xml->citation->asXml())) . "',
			doi = '" . esc($xml->doi) . "',
			publish_date = '" . $sqlPubDate . "',
			about = '" . esc(stripTag('about', $xml->about->asXml())) . "'
			Where recordID = " . $publicationID . "; ";
	}

	// final report
	if ($sqlOutput) {
		$update = cleanInput('update');
		processSQL($sqlOutput, $update);
	}
}

// template
templateFooter();

?>