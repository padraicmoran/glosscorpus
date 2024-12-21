<?php
require "../functions/core/application.php";
require "common.php";

require "../pages/template/template.php";
templateHeader('CMS', '');

$alertTypes = initaliseAlerts();
echo '<a class="float-end btn btn-primary btn-sm btn-secondary" href="./">CMS home</a>';
echo '<h2>Update base text</h2>';

$baseTextID = intval(cleanInput('baseTextID'));
$xmlText = cleanInput('xmlText');

$error = false;
$sqlOutput = array();
$updateCount = array('insert' => 0, 'update' => 0, 'delete' => 0);

if ($baseTextID == 0 || (! $filesystemInput && $xmlText == '')) {
	// choose options
	echo '<form method="post">';
	formSelectBaseText();

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

	// get db base text details
	$sql = "Select author, title, slug From base_texts Where recordID = " . $baseTextID;
	$results = mysqli_query($link, $sql);

	if (mysqli_num_rows($results) == 0) {
		$error = true;
		alert('error', 'No base text found.');
	}
	else {
		$row = mysqli_fetch_assoc($results);
		$slug = $row['slug'];
		$oldTitle = $row['author'] . ', <i>' . $row['title'] . '</i>';
		alert('info', 'Target base text: <b>' . $oldTitle . '</i></b> (ID ' . $baseTextID . ')');
	}

	// load XML
	if (! $error) {
		libxml_use_internal_errors(true);

		// import XML file from filesystem
		if ($filesystemInput) {
			$importFile = '../data/texts/' . $slug . '/basetext.xml';
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
		else {
			// register name space
			$xml->registerXPathNamespace('tei', 'http://www.tei-c.org/ns/1.0');
		}
	}

	// check whether new title
	if (! $error) {
		$newTitle = trim($xml->teiHeader->fileDesc->titleStmt->author . ', <i>' . $xml->teiHeader->fileDesc->titleStmt->title) . '</i>';
		if ($oldTitle <> $newTitle) {
			alert('warning', 'Title change: <b>' . $newTitle . '</b>');
		}
		else {
			alert('success', 'No title change: <b>' . $newTitle . '</b>');
		}
	}

	// prepare SQL: update main table
	if (! $error) {
		$sqlOutput[] = "Update base_texts Set " .
			"author = '" . esc($xml->teiHeader->fileDesc->titleStmt->author) . "', " . 
			"title = '" . esc($xml->teiHeader->fileDesc->titleStmt->title) . "', " . 
			"short_ref = '" . esc(implode($xml->xpath("tei:teiHeader/tei:fileDesc/tei:notesStmt/tei:note[@type='short_title']"))) . "', " . 
			"source_info = '" . esc(stripTag('p', $xml->teiHeader->fileDesc->editionStmt->p->asXml())) . "', " . 
			"source_credit = '" . esc(stripTag('p', $xml->teiHeader->fileDesc->sourceDesc->p->asXml())) . "', " . 
			"seg_desc = '" . esc(implode($xml->xpath("tei:teiHeader/tei:fileDesc/tei:notesStmt/tei:note[@type='segmentation']"))) . "' " . 
			"Where recordID = " . $baseTextID . ";";
	}

	// compile list of existing sections 
	if (! $error) {
		// get sections from db
		$sql = 'Select recordID, ref From base_text_sections Where base_textID = ' . $baseTextID;
		$results = mysqli_query($link, $sql);
		$dbSecRecordIDs = array();
		$dbSecRefs = array();
		while ($row = mysqli_fetch_assoc($results)) {
			$thisSecRef = strval($row['ref']);
			$dbSecRecordIDs[$thisSecRef] = $row['recordID'];
			$dbSecRefs[] = $thisSecRef;
		}
		alert('warning', 'There are ' . count($dbSecRefs) . ' section(s) for this base text in the database currently.');
	}

	// start validating sections: check for duplicate IDs
	if (! $error) {
		$importedSecIDs = array();
		alert('info', 'Checking imported sections for duplicate IDs or for new sections.');
		foreach ($xml->text->body->div as $sec) {
			$thisSecRef = strval($sec->xpath('@n')[0]);
			
			// check ID is unique in imported file
			if (in_array($thisSecRef, $importedSecIDs)) {
				$error = true;
				alert('error', 'Section reference (@key) <b>' . $thisSecRef . '</b> is duplicated.');
			}
			else {
				$importedSecIDs[] = $thisSecRef;
			}

			// check whether an imported section is not in db
			if (! in_array($thisSecRef, $dbSecRefs)) {
				alert('warning', 'New section required for section <b>' . $thisSecRef . '</b>. Update and then repeat process.');
				$sqlOutput[] = "Insert Into base_text_sections (base_textID, ref, title) Values (". $baseTextID . ", '". esc($thisSecRef) . "', '" . esc($sec->head) . "');";
				$error = true;
				// need to stop process here and create the section, then rerun the validation so that its ID can be used later
			}
		}
		if (! $error) alert('success', count($importedSecIDs) . ' imported sections (&lt;div type="section"&gt;) checked.');
	}
	
	// process segments
	if (! $error) {
		// get segments from db
		$sql = 'Select segmentID From base_text_segments Where base_textID = ' . $baseTextID;
		$results = mysqli_query($link, $sql);
		$dbSegIDs = array();
		while ($row = mysqli_fetch_assoc($results)) {
			$dbSegIDs[] = $row['segmentID'];
		}
		alert('warning', 'There are ' . count($dbSegIDs) . ' text segment(s) for this base text in the database currently.');
		alert('info', 'Checking imported segments for duplicate IDs.');

		// check that segment IDs are unique
		$importedSegIDs = array();
		foreach ($xml->xpath('//tei:ab') as $seg) {
			$thisSegID = strval($seg->xpath('@xml:id')[0]);
			if (in_array($thisSegID, $importedSegIDs)) {
				$error = true;
				alert('error', 'Segment ID <b>' . $thisSegID . '</b> is duplicated.');
			}
			else {
				$importedSegIDs[] = $thisSegID;
			}
		}
		if (! $error) alert('success', '' . count($importedSegIDs) . ' imported segments (&lt;ab…&gt;) checked. No duplicate IDs.');
	}
	
	// generate SQL for updating sections and segments
	if (! $error) {

		// sections
		// reset order_index in db
		$sqlOutput[] = "Update base_text_sections Set order_index = 0 Where base_textID = " . $baseTextID . ";";

		// build section updates
		$orderIndex = 1;
		foreach ($xml->text->body->div as $sec) {
			$thisSecRef = strval($sec->xpath('@n')[0]);
			$sqlOutput[] = "Update base_text_sections Set title = '" . esc($sec->head) . "', order_index = " . $orderIndex . " Where base_textID = " . $baseTextID . " And ref = '". esc($thisSecRef) . "';";
			$orderIndex ++;
		}

		// segments
		// reset order_index in db
		$sqlOutput[] = "Update base_text_segments Set order_index = 0 Where base_textID = " . $baseTextID . ";";

		$orderIndex = 1;
		foreach ($xml->text->body->div as $sec) {
			$secRecordID = $dbSecRecordIDs[strval($sec->xpath('@n')[0])];
			foreach($sec->ab as $seg) {
				$text = stripTag('ab', $seg->asXml());		// hack: need to take the contents of the <ab> tag but not the tag itself; couldn't work an XML solution!
				$segID = strval($seg->xpath('@xml:id')[0]);

				// if an alternative, user-friendly ref (<ab @n="">) is specified, collect that; otherwise treat the ID as the ref
				if ($seg['n']) $segRef = $seg['n'];
				else $segRef = $segID;
				
				if (in_array($segID, $dbSegIDs)) {
					$sqlOutput[] = "Update base_text_segments Set sectionID = " . $secRecordID . ", ref = '" . esc($segRef) . "', text = '" . esc($text) . "', order_index = " . $orderIndex . " Where base_textID = " . $baseTextID . " And segmentID = '" . esc($segID) . "';";
					$updateCount['update'] ++;
				}
				else {
					$sqlOutput[] = "Insert Into base_text_segments (base_textID, sectionID, segmentID, ref, text, order_index) Values (" . $baseTextID . ", " . $secRecordID . ", '" . esc($segID) . "', '" . esc($segRef) . "', '" . esc($text) . "', " . $orderIndex . ");";
					$updateCount['insert'] ++;
				}
				$orderIndex ++;
			}
		}

		// remove residual sections and segments
		$sqlOutput[] = "Delete from base_text_sections Where base_textID = " . $baseTextID . " And order_index = 0; ";
		$sqlOutput[] = "Delete from base_text_segments Where base_textID = " . $baseTextID . " And order_index = 0; ";
		$updateCount['delete'] = count($dbSegIDs) - $updateCount['update'];
	}

	// final report
	if (! $error) {
		alert('success', 
			'Finished. ' . 
			$updateCount['update'] . ' segment(s) will be updated, ' . 
			$updateCount['insert'] . ' segment(s) will be added, ' . 
			$updateCount['delete'] . ' existing segment(s) will be deleted.');
	}

	// update database 
	if ($sqlOutput) {
		$update = cleanInput('update');
		processSQL($sqlOutput, $update);
	}
}

// template
templateFooter();
?>