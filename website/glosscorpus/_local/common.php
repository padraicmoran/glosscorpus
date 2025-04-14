<?php
// settings
// filesystemInput: true to read XML file from data folder; false for reading XML from a textbox
$filesystemInput = true;

// common functions for CMS
function formSelectBaseText() {
	global $link;

	$sql = "Select recordID, author, title, slug From base_texts Order By author, title ";
	$results = mysqli_query($link, $sql);	
	
	if (mysqli_num_rows($results) > 0) {
		echo '<label for="baseTextID" class="form-label mt-3">Select base text:</label>';
		echo '<select class="form-select" id="baseTextID" name="baseTextID">';
		while ($row = mysqli_fetch_assoc($results)) {
			echo '<option value="' . $row['recordID'] . '">' . $row['author'] . ', ' . $row['title'] . ' [' . $row['slug'] . '.xml]</option>';
		}
		echo '</select>';
	}
}

function formSelectCollection() {
	global $link;

	$sql = "
		Select c.recordID, b.author, b.title, c.siglum, c.description, c.slug
		From 
			gloss_collections c
			Inner Join base_texts b
		 	On c.base_textID = b.recordID
		Where
			b.publish = 1
		Order By
			b.author, b.title, c.siglum, c.description ";
	$results = mysqli_query($link, $sql);	
	
	if (mysqli_num_rows($results) > 0) {
		echo '<label for="collectionID" class="form-label mt-3">Select gloss collection:</label>';
		echo '<select class="form-select" id="collectionID" name="collectionID">';
		while ($row = mysqli_fetch_assoc($results)) {
			echo '<option value="' . $row['recordID'] . '">' . $row['author'] . ', ' . $row['title'] . ': ' . $row['siglum'] . ' (' . $row['description'] . ') [' . $row['slug'] . ']</option>';
		}
		echo '</select>';
	}
}

function formSelectPublication() {
	global $link;

	$sql = "Select recordID, citation, slug From publications Order By citation ";
	$results = mysqli_query($link, $sql);	
	
	if (mysqli_num_rows($results) > 0) {
		echo '<label for="publicationID" class="form-label mt-3">Select publication:</label>';
		echo '<select class="form-select" id="publicationID" name="publicationID">';
		while ($row = mysqli_fetch_assoc($results)) {
			echo '<option value="' . $row['recordID'] . '">' . $row['citation'] . ' [' . $row['slug'] . ']</option>';
		}
		echo '</select>';
	}
}

// deprecated
function formSelectFile($path) {

	$files = glob($path);
	echo '<label for="file" class="form-label mt-3">Select import file:</label>';
	echo '<select class="form-select" id="file" name="file">';
	foreach ($files as $file) {
		echo '<option value="' . $file . '">' . $file . '</option>';
	}
	echo '</select> ';
}


// abbreviated SQL escape function
function esc($txt) {
	global $link;
	if ($txt != '') {
		// string extra white space
		$txt = preg_replace('/[\n\r\t\s]+/i', ' ', $txt);
		return trim(mysqli_real_escape_string($link, $txt));
	}
	else {
		return '';
	}
}

// initalise alert boxes
function initaliseAlerts() {
?>
<svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
  <symbol id="check-circle-fill" fill="currentColor" viewBox="0 0 16 16">
    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
  </symbol>
  <symbol id="info-fill" fill="currentColor" viewBox="0 0 16 16">
    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
  </symbol>
  <symbol id="exclamation-triangle-fill" fill="currentColor" viewBox="0 0 16 16">
    <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
  </symbol>
</svg>
<?php
	return array(
		'info' => array('primary', 'info-fill'),
		'success' => array('success', 'check-circle-fill'),
		'warning' => array('warning', 'exclamation-triangle-fill'),
		'error' => array('danger', 'exclamation-triangle-fill')
	);
}

// write alert box
function alert($type, $text) {
	global $alertTypes;

	// set a default type if input not matched
	if (! array_key_exists($type, $alertTypes)) $type = 'info';	

	if ($type == 'warning') $text = '' . $text;
	if ($type == 'error') $text = '<b>Error</b>: ' . $text;

	echo '<div class="alert alert-' . $alertTypes[$type][0] . ' d-flex align-items-center" role="alert">';
	echo '<svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="' . $type . ':"><use xlink:href="#' . $alertTypes[$type][1] . '"/></svg>';
	echo '<div>'. $text . '</div>';
	echo '</div>';
 
}

// cannot pass null into function htmlentities
function keepEntities($str) {
	if ($str <> '') return htmlentities($str);
	else return '';
}

// process SQL statements
function processSQL($sqlOutput, $update) {
	global $link, $filesystemInput;
	if ($update == 'y') {
		// process SQL 
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		mysqli_begin_transaction($link);
		$sqlError = false;
		foreach ($sqlOutput as $s) {
			$result = mysqli_query($link, $s);
			if (! $result) $sqlError = true;
		}
		if ($sqlError == false) {
			mysqli_commit($link);
			alert('success', 'Database updated.');
			updateTotals();

			// update file
			// needs folder permissions; will leave for now (still untested)
			/*
			$file = fopen('../../../data/collections/' . $slug . '.xml', 'w') or die('Unable to update XML file.');
			fwrite($file, $xmlText);
			fclose($file);
			alert('success', 'XML file updated.');
			*/
			if (! $filesystemInput) alert('warning', 'XML file still needs to be updated.');
		}
		else {
			mysqli_rollback($link);
			alert('error', 'Database update failed.');
		}
	}
	else {
		// output SQL for inspection
		echo '<textarea style="margin-top: 100px; width: 100%; height: 50px; font-family: monospace; font-size: 10px; ">';
		foreach ($sqlOutput as $s) {
			echo htmlspecialchars($s, ENT_NOQUOTES, 'UTF-8') . "\n";
		}
		echo '</textarea>';
	}
}

// update database totals
function updateTotals() {
	global $link;
	$sql = "
		Insert Into totals_published (base_texts, collections, glosses)
		Select 
			Count(Distinct b.recordID) As 'primary_texts', 
			Count(Distinct c.recordID) As 'collections', 
			Count(Distinct g.recordID) As 'glosses'
		From 
			base_texts b
			Inner Join base_text_sections sec
			On sec.base_textID = b.recordID
			Inner Join base_text_segments seg
			On seg.sectionID = sec.recordID
			Inner Join glosses g
			On g.segmentRecordID = seg.recordID
			Inner Join gloss_collections c
			On c.base_textID = b.recordID
			Inner Join publications p
			On c.publicationID = p.recordID
		Where 
			b.publish = 1 And
			c.publish = 1 And
			p.publish = 1";
	$result = mysqli_query($link, $sql);
	if ($result) {
		alert('success', 'Total counts updated.');
	}
	else {
		alert('error', 'Total counts update failed.');
	}
}

// this function updates the "text_searchable" column in the glosses table
// copies content from "text" column, stripping tags and non-alphanumeric characters
function updateSearchIndex($collectionID) {
	global $link;
	$error = false;

	// remove HTML tags
	$sql = "Update glosses Set text_searchable = regexp_replace(text, '<[^>]*>', '') Where collectionID = $collectionID;";
	$result = mysqli_query($link, $sql);
	if (! $result) $error = true;

	// remove content in brackets 
	$sql = "Update glosses Set text_searchable = regexp_replace(text_searchable, '\[^[:alnum:][:space:]]*', '') Where collectionID = $collectionID; ";
	$result = mysqli_query($link, $sql);
	if (! $result) $error = true;

	if (! $error) {
		alert('success', 'Search index updated.');
	}
	else {
		alert('error', 'Search index update failed.');
	}
}

?>
