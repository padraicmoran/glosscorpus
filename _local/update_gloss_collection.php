<?php
require "../functions/core/application.php";
require "common.php";

require "../pages/template/template.php";
templateHeader('CMS', '');

$alertTypes = initaliseAlerts();
echo '<a class="float-end btn btn-primary btn-sm btn-secondary" href="./">CMS home</a>';
echo '<h2>Update gloss collection</h2>';

$collectionID = intval(cleanInput('collectionID'));
$xmlText = cleanInput('xmlText');

$error = false;
$base_textID = null;
$sqlOutput = array();
$updateCount = array('insert' => 0, 'update' => 0, 'insert' => 0);

if ($collectionID == 0 || (! $filesystemInput && $xmlText == '')) {
	// selection page

	// choose options
	echo '<form method="post">';
	formSelectCollection();

	// textbox, if needed
	if (! $filesystemInput) {
		echo '<label for="xmlText" class="form-label mt-3">XML data:</label>';
		echo '<textarea id="xmlText" name="xmlText" class="form-control" rows="10"></textarea>';
	}

	// update options
	echo '<div class="form-check mt-2 small"><input type="radio" class="form-check-input" name="update" id="update1" value="n" checked><label class="form-check-label" for="update1">Validate XML only</label></div>';
	echo '<div class="form-check mt-2 small"><input type="radio" class="form-check-input" name="update" id="update2" value="y"><label class="form-check-label" for="update2">Validate XML and update database</label></div>';

	echo '<input type="submit" class="btn-sm btn-primary mt-3" value="go">';
	echo '</form>';
}
else {
	// start processing

	// get collection details from db
	$sql = "Select 
		c.base_textID, b.slug as base_slug, c.slug, c.author, c.description 
	From 
		gloss_collections c Inner Join
		base_texts b On c.base_textID = b.recordID
	Where 
		c.recordID = " . $collectionID;
	$results = mysqli_query($link, $sql);

	if (mysqli_num_rows($results) == 0) {
		$error = true;
		alert('error', 'No gloss collection found.');
	}
	else {
		$row = mysqli_fetch_assoc($results);
		$base_textID = $row['base_textID'];
		$slug = $row['slug'];
		$baseSlug = $row['base_slug'];
		$oldTitle = $row['author'] . ', ' . $row['description'];
		alert('info', 'Target collection: <b>' . $oldTitle . '</b> (ID ' . $collectionID . ')');
	}

	// load XML
	if (! $error) {
		libxml_use_internal_errors(true);

		// import XML file from filesystem
		if ($filesystemInput) {
			$importFile = '../data/texts/' . $baseSlug . '/gloss_collections/' . $slug . '.xml';
			if (! file_exists($importFile)) {
				$error = true;
				alert('error', 'Import file not found: ' . $importFile);
			}
			else {
				$xml = simplexml_load_file($importFile, 'SimpleXMLElement', LIBXML_DTDLOAD | LIBXML_DTDATTR | LIBXML_NOENT);
			}
		}
		// or from pasted XML
		else {
			$xmlText = html_entity_decode($xmlText);
			$xml = simplexml_load_string($xmlText, 'SimpleXMLElement', LIBXML_DTDLOAD | LIBXML_DTDATTR | LIBXML_NOENT);
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

	// check new heading
	if (! $error) {
		$newTitle = trim($xml->teiHeader->fileDesc->titleStmt->author . ', ' . $xml->teiHeader->fileDesc->titleStmt->title);
		if ($oldTitle <> $newTitle) {
			alert('warning', 'Title change: <b>' . $newTitle . '</b>');
		}
		else {
			alert('success', 'No title change: <b>' . $newTitle . '</b>');
		}
	}

	// compile list of existing glosses
	if (! $error) {
		$sql = "Select ref From glosses Where collectionID = " . $collectionID . "; ";
		$results = mysqli_query($link, $sql);
		
		$dbGlossRefs = array();
		while ($row = mysqli_fetch_assoc($results)) {
			$dbGlossRefs[] = $row['ref'];
		}
		alert('warning', 'There are ' . count($dbGlossRefs) . ' gloss(es) for this collection in the database currently.');
	}

	// start validating: check for duplicate refs
	if (! $error) {
		alert('info', 'Checking for duplicate references (@n) in new content.');	
		$importedGlossRefs = array();

		foreach ($xml->xpath('//tei:body/tei:div/tei:note') as $gloss) {
			$thisRef = strval($gloss['n']);
			if (in_array($thisRef, $importedGlossRefs)) {
				$error = true;
				alert('error', 'Duplicate gloss reference (@n): <b>' . $thisRef . '</b>.');
			}
			else $importedGlossRefs[] = $thisRef;
		}
		if (! $error) {
			alert('success', 'No duplicate references found in ' . count($importedGlossRefs) . ' gloss(es).');
		}
	}

	// generate SQL update for gloss_collections table
	if (! $error) {
		$sqlOutput[] = "Update gloss_collections Set " .
			"author = '" . esc($xml->teiHeader->fileDesc->titleStmt->author) . "', " . 
			"description = '" . esc($xml->teiHeader->fileDesc->titleStmt->title) . "', " . 
			"ms_city = '" . esc($xml->teiHeader->fileDesc->sourceDesc->msDesc->msIdentifier->settlement) . "', " . 
			"ms_library = '" . esc($xml->teiHeader->fileDesc->sourceDesc->msDesc->msIdentifier->repository) . "', " . 
			"ms_collection = '" . esc($xml->teiHeader->fileDesc->sourceDesc->msDesc->msIdentifier->collection) . "', " . 
			"ms_shelfmark = '" . esc($xml->teiHeader->fileDesc->sourceDesc->msDesc->msIdentifier->idno) . "', " . 
			"siglum  = '" . esc(implode($xml->xpath("tei:teiHeader/tei:fileDesc/tei:notesStmt/tei:note[@type='siglum']"))) . "', " . 
			"colour = '" . esc(implode($xml->xpath("tei:teiHeader/tei:fileDesc/tei:notesStmt/tei:note[@type='hex_colour']"))) . "', " . 
			"credits = '" . esc(stripTag('p', $xml->teiHeader->fileDesc->publicationStmt->p->asXml())) . "' " . 
			"Where recordID = " . $collectionID . ";";
	}

	// compile list of all segment IDs from import file (ignoring word ID suffixes __x)
	if (! $error) {
		$importedSegIDs = array();
		foreach ($xml->xpath('//tei:body/tei:div/tei:note') as $gloss) {
			$importedSegIDs[] = extractSegID(strval($gloss['target']));
		}
		alert('info', 'Checking ' . count($importedSegIDs) . ' reference(s) to base text segments.');

		// get associated segment IDs from db
		$sql = "Select recordID, segmentID From base_text_segments Where 
			base_textID = " . $base_textID . " And 
			segmentID In ('" . implode("', '" , $importedSegIDs) . "'); ";
		$results = mysqli_query($link, $sql);
		
		$segRecordIDs = array();
		while ($row = mysqli_fetch_assoc($results)) {
			$segRecordIDs[$row['segmentID']] = $row['recordID'];
		}
		alert('info', '' . count($segRecordIDs) . ' unique segment references.');
	}
	
	// generate SQL for updating glosses
	if (! $error) {
		// reset order_index in db
		$sqlOutput[] = "Update glosses Set order_index = 0 Where collectionID = " . $collectionID . ";";

		$orderIndex = 1;
		foreach ($xml->xpath('//tei:body/tei:div/tei:note') as $gloss) {
			$thisRef = strval($gloss['n']);
			$thisWordIndex = extractWordIndex($gloss['target']);
			$thisSegID = extractSegID($gloss['target']);
			
			$trans = '';
			$miscNotes = '';
			foreach ($gloss->note as $note) {
				if (strval($note['type']) == 'translation') $trans = $note;
				if (strval($note['type']) == 'editorial') $miscNotes = $note;
			}

			// check whether this gloss has a valid segmentID
			if (! isset($segRecordIDs[$thisSegID])) {
				alert('warning', 'Segment target reference <b>"' . $gloss['target'] . '"</b> for gloss <b>' . $thisRef . '</b> not found in base text. This gloss will be ignored.');
			}
			else {
				if (in_array($thisRef, $dbGlossRefs)) {
					// update existing gloss
					$sqlOutput[] = "Update glosses Set " . 
						"segmentRecordID = " . esc($segRecordIDs[$thisSegID]) . ", " . 
						"word_index = " . $thisWordIndex . ", " . 
						"text = '" . esc($gloss->asXml()) . "', " . 
						"order_index = " . $orderIndex . " " . 
						" Where collectionID = " . $collectionID . " And ref = '" . esc($thisRef) . "';"; 
					$updateCount['update'] ++;
				}
				else {
					// insert new gloss
					$sqlOutput[] = "Insert Into glosses (collectionID, ref, segmentRecordID, word_index, text, order_index) Values (" . 
						"" . $collectionID . ", " . 
						"'" . esc($thisRef) . "', " . 
						"" . esc($segRecordIDs[$thisSegID]) . ", " . 
						"" . $thisWordIndex . ", " . 
						"'" . esc($gloss->asXml()) . "', " . 
						"" . $orderIndex . ");"; 
					$updateCount['insert'] ++;
				}
			}
			$orderIndex ++;
		}
		alert('success', 'Done checking segment references.');

		// remove residual sections and segments
		$updateCount['delete'] = count($dbGlossRefs) - $updateCount['update'];
		$sqlOutput[] = "Delete from glosses Where collectionID = " . $collectionID . " And order_index = 0;";

		// update search index
		/* production server is MySQL 5.7; does not support text_searchable function!
		
		$sqlOutput[] = "Update glosses Set text_searchable = regexp_replace(text, '<[^>]*>', ' ') Where collectionID = " . $collectionID . ";";
		$sqlOutput[] = "Update glosses Set text_searchable = regexp_replace(text_searchable, '\[^[:alnum:][:space:]]*', '') Where collectionID = " . $collectionID . ";";
		*/
	}

	// final report
	if (! $error) {
		alert('success', 
			'Finished. ' . 
			$updateCount['update'] . ' gloss(es) will be updated, ' . 
			$updateCount['insert'] . ' new gloss(es) will be added, ' . 
			$updateCount['delete'] . ' existing gloss(es) will be deleted.');
	}
	
	// update database 
	if ($sqlOutput) {
		$update = cleanInput('update');
		processSQL($sqlOutput, $update);
		// if ($update == 'y') updateSearchIndex($collectionID);
	}
}

// template
templateFooter();

// trim starting hash from imported segmentID references
// trim any final double undersfunctions/core and word ref
function extractSegID($node) {
	$temp = substr(strval($node), 1);		# trim hash
	$temp = explode("__", $temp)[0];		# trim any final word ref
	return $temp;
}

function extractWordIndex($node) {
	$temp = substr(strval($node), 1);		# trim hash
	$parts = explode("__", $temp);
	if (count($parts) == 1) return 'null';
	else return intval($parts[1]);
}

?>