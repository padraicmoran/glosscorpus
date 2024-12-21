<?php
require "../functions/core/application.php";
require "common.php";

require "../pages/template/template.php";
templateHeader('CMS', '');

$alertTypes = initaliseAlerts();
echo '<h2>Export base text</h2>';

$baseTextID = intval(cleanInput('baseTextID'));

if ($baseTextID == 0) {

	// list base texts
	echo '<form method="get">';
	formSelectBaseText();
	echo '<input type="submit" class="btn-sm btn-primary mt-3" value="go" />';
	echo '</form></br>';

}
else {

	// header info
	$sql = "Select author, title, short_ref, source_info, source_credit, seg_desc
		From base_texts
		Where recordID = " . $baseTextID;
	$results = mysqli_query($link, $sql);

	if (mysqli_num_rows($results) == 0) {
		alert('error', 'No base text found.');
	}
	else {
		$row = mysqli_fetch_assoc($results);
		alert('info', '' . $row['author'] . ', <i>' . $row['title'] . '</i>');

		echo '<textarea style="width: 100%; height: 600px; font-family: monospace; font-size: 10px; ">';

		// build TEI header

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<TEI xmlns="http://www.tei-c.org/ns/1.0"' . "\n";
		echo '	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
		echo '	xsi:schemaLocation="http://www.tei-c.org/ns/1.0 http://www.tei-c.org/release/xml/tei/custom/schema/xsd/tei_all.xsd">' . "\n";
		echo '	<teiHeader>' . "\n";
		echo '		<fileDesc>' . "\n";
		echo '			<titleStmt>' . "\n";
		echo '				<title>' . $row['title'] . '</title>' . "\n";
		echo '				<author>' . $row['author'] . '</author>' . "\n";
		echo '			</titleStmt>' . "\n";
		echo '			<editionStmt>' . "\n";
		echo '				<p>' . $row['source_info'] . '</p>' . "\n";
		echo '			</editionStmt>' . "\n";
		echo '			<publicationStmt>' . "\n";
		echo '				<publisher>Gloss Corpus</publisher>' . "\n";
		echo '				<authority>Pádraic Moran</authority>' . "\n";
		echo '				<date>'. date("Y") . '</date>' . "\n";
		echo '				<idno type="URL">http://www.glossing.org/glosscorpus</idno>' . "\n";
		echo '				<availability status="free">' . "\n";
		echo '					<licence target="https://creativecommons.org/licenses/by-nc-sa/4.0/">' . "\n";
		echo '						<p>Creative Commons BY-NC-SA 4.0</p>' . "\n";
		echo '					</licence>' . "\n";
		echo '				</availability>' . "\n";
		echo '			</publicationStmt>' . "\n";
		echo '			<notesStmt>' . "\n";
		echo '				<note type="short_title">' . $row['short_ref'] . '</note>' . "\n";
		echo '				<note type="segmentation">' . $row['seg_desc'] . '</note>' . "\n";
		echo '			</notesStmt>' . "\n";
		echo '			<sourceDesc>' . "\n";
		echo '				<p>' . $row['source_credit'] . '</p>' . "\n";
		echo '			</sourceDesc>' . "\n";
		echo '		</fileDesc>' . "\n";
		echo '	</teiHeader>' . "\n";


		// build TEI body

		$sql = "Select 
				sec.ref As sec_ref, sec.title, 
				seg.ref As seg_ref, seg.segmentID, seg.text, sec.recordID
			From base_text_segments seg Left Join
			base_text_sections sec
			 On seg.sectionID = sec.recordID
			Where seg.base_textID = " . $baseTextID . " 
			Order By seg.order_index ";
		$results = mysqli_query($link, $sql);

		echo '	<text>' . "\n";
		echo '		<body>' . "\n";

		$currentSectionID = null;
		while ($row = mysqli_fetch_assoc($results)) {
			if ($currentSectionID != $row['recordID']) {
				// write new section ID
				if ($currentSectionID != null) echo ' 			</div>' . "\n";
				echo '			<div type="section" n="' . $row['sec_ref'] . '">' . "\n";
				echo '				<head>' . $row['title'] . '</head>' . "\n";
				$currentSectionID = $row['recordID'];
			}
			echo '				<ab xml:id="' . $row['segmentID'] . '" n="' . $row['seg_ref'] . '">' . $row['text'] . '</ab>' . "\n";
		}
		echo ' 			</div>' . "\n";


		// finish TEI file
		
		echo '		</body>' . "\n";
		echo '	</text>' . "\n";
		echo '</TEI>' . "\n";

		echo '</textarea>';
	}
}

// template
templateFooter();
?>