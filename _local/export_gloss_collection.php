<?php
require "../functions/core/application.php";
require "common.php";

require "../pages/template/template.php";
templateHeader('CMS', '');

$alertTypes = initaliseAlerts();
echo '<h2>Export gloss collection</h2>';

$collectionID = intval(cleanInput('collectionID'));

if ($collectionID == 0) {

	// list gloss collections
	echo '<form method="get">';
	formSelectCollection();
	echo '<input type="submit" class="btn-sm btn-primary" value="go">';
	echo '</form></br>';

}
else {

	// header info
	$sql = "Select author, description, ms_city, ms_library, ms_collection, ms_shelfmark, ms_url_base, siglum, colour, credits, ref2_url_base
		From gloss_collections
		Where recordID = " . $collectionID;
	$results = mysqli_query($link, $sql);

	if (mysqli_num_rows($results) == 0) {
		alert('error', 'No collection found.');
	}
	else {
		$row = mysqli_fetch_assoc($results);
		alert('info', $row['description'] . ' (' . $row['siglum'] . ')');
	
		echo '<textarea style="width: 100%; height: 600px; font-family: monospace; font-size: 10px; ">';

		// build TEI header

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<TEI xmlns="http://www.tei-c.org/ns/1.0">' . "\n";
		echo '	<teiHeader>' . "\n";
		echo '		<fileDesc>' . "\n";
		echo '			<titleStmt>' . "\n";
		echo '				<author>' . $row['author'] . '</author>' . "\n";
		echo '				<title>' . $row['description'] . '</title>' . "\n";
		echo '			</titleStmt>' . "\n";

		echo '			<publicationStmt>' . "\n";
		echo '				<p>' . $row['credits'] . '</p>' . "\n";
		echo '			</publicationStmt>' . "\n";

		echo '			<notesStmt>' . "\n";
		echo '				<note type="siglum">' . $row['siglum'] . '</note>' . "\n";
		echo '				<note type="hex_colour">' . $row['colour'] . '</note>' . "\n";
		echo '				<note type="image_url_base">' . $row['ms_url_base'] . '</note>' . "\n";
		echo '				<note type="ref2_url_base">' . $row['ref2_url_base'] . '</note>' . "\n";
		echo '			</notesStmt>' . "\n";

		echo '			<sourceDesc>' . "\n";
		echo '				<msDesc>' . "\n";
		echo '					<msIdentifier>' . "\n";
		echo '						<settlement>' . $row['ms_city'] . '</settlement>' . "\n";
		echo '						<repository>' . $row['ms_library'] . '</repository>' . "\n";
		echo '						<collection>' . $row['ms_collection'] . '</collection>' . "\n";
		echo '						<idno>' . $row['ms_shelfmark'] . '</idno>' . "\n";
		echo '					</msIdentifier>' . "\n";
		echo '					<physDesc>' . "\n";
		echo '						<handDesc>' . "\n";
		echo '						<!-- hand details still to be implemented -->' . "\n";
		echo '						</handDesc>' . "\n";
		echo '					</physDesc>' . "\n";
		echo '				</msDesc>' . "\n";
		echo '			</sourceDesc>' . "\n";

		echo '		</fileDesc>' . "\n";
		echo '	</teiHeader>' . "\n";


		// build TEI body
		
		$sql = "Select 
				g.ref As g_ref, s.segmentID As s_ref, g.text, g.translation, g.notes, g.ms_url_end, g.hand, g.ref2, g.ref2_url_end
			From 
				glosses g 
			Left Join 
				base_text_segments s 
				On g.segmentRecordID = s.recordID
			Where 
				g.collectionID = " . $collectionID . " 
			Order By 
				g.order_index ";

		$results = mysqli_query($link, $sql);

		echo '	<text>' . "\n";
		echo '		<body>' . "\n";

		while ($row = mysqli_fetch_assoc($results)) {
			echo '			<note n="' . trim($row['g_ref']) . '" target="#' . $row['s_ref'] . '"';
			if ($row['ms_url_end'] <> '') echo ' facs="' . $row['ms_url_end'] . '"';
			echo '>' . "\n";
			echo '				' . $row['text'] . "\n";
			if ($row['translation']) echo '				<note type="translation">' . $row['translation'] . '</note>' . "\n";
			if ($row['notes']) echo '				<note type="editorial">' . $row['notes'] . '</note>' . "\n";
			if ($row['ref2']) {
				if ($row['ref2_url_end']) echo '				<ref target="' . $row['ref2_url_end'] . '">' . $row['ref2'] . '</ref>' . "\n";
				else echo '				<ref>' . $row['ref2'] . '</ref>' . "\n";
			}
			echo '			</note>' . "\n";
		}


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