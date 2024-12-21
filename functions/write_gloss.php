<?php

function writeGlossRow($rowG, $lemmata) {
   global $staging;

   $glossHighlight = cleanInput('gh');
   $search = cleanInput('s');

   // Start row
   // Set highlighter on row if needed
   if ($rowG['recordID'] == $glossHighlight) {
      echo '<tr class="highlight small">';
      $highlightAnchor = 'hi';
   }
   else {
      echo '<tr class="small">';
      $highlightAnchor = '';
   }

	// Create gloss ref, with style and links as appropriate
	if ($rowG['siglum'] <> '') $glossRef = '<b>' . $rowG['siglum'] . "</b> " . $rowG['ref'];
	else $glossRef =  $rowG['ref'];
	if ($search != '') $glossRef = '<a class="text-dark underline" href="?sec=' . $rowG['sectionID'] . '&gh=' . $rowG['recordID'] . '#hi">' . $glossRef . '</a>';

   // Collect other content
   $xml = simplexml_load_string('<div>' . $rowG['text'] . '</div>');

   $msLink = '';
//   $iiifLink = '';
   $ref2Link = '';
   $hand = '';
   $place = '';

   if ($xml->note['facs']) $msLink = '<a href="' . $xml->note['facs'] . '" target="_blank">MS➚</a>';
   if ($xml->note->ref) {
      $ref2Link = '<a href="' . $xml->note->ref['target'] . '" target="_blank">' . $xml->note->ref . '➚</a>';
   }
   if ($xml->note['hand']) {
      if (substr($xml->note['hand'], 0, 5) === "hand-") {
         $hand = '✍ ' . substr($xml->note['hand'], 5);
      }
      else {
         $hand = '✍ ' . $xml->note['hand'];
      }
   }
   if ($xml->note['place']) $place = $xml->note['place'];
   
   // Write cells (currently 6)
   // Spacer (under base text ref column)
	echo '<td></td>';
	
	// Siglum
   echo '<td class="small text-nowrap" id="' . $highlightAnchor . '"><span class="p-1 rounded" style="background-color: #' . $rowG['colour'] . '">' . $glossRef . '</span></td>' . "\n";

   // Various
	echo '<td class="small text-nowrap">' . $msLink . '</td>';
//   echo '<td class="small text-nowrap">' . $iiifLink . '</td>';
   echo '<td class="small text-nowrap">' . $ref2Link . '</td>';
   echo '<td class="small text-nowrap">' . $hand . '</td>'; 
   echo '<td class="small text-nowrap">' . $place . '</td>'; 
	
	// Main gloss content
	echo '<td style="width: 85%">';
   if ($staging && $rowG['word_index'] != '') echo '<sup class="text-muted">' . $rowG['word_index'] . '</sup> ';
   // Check for a base text lemma; will be supplied if no lemma is specified within the gloss entry
   if ($lemmata && $rowG['word_index']) {
      $lemma = $lemmata[$rowG['word_index']];
   }
   else {
      $lemma = '';
   }
	echo renderGloss($rowG['text'], $lemma);
   echo '</td>' . "\n";

   // End Row
   echo '</tr>' . "\n";
}


function renderGloss($xmlStr, $lemma) {
   $xsltFile = './xslt/render_gloss.xsl';

   $xmlText = '<!DOCTYPE div><div>';
   if ($lemma <> '') $xmlText .= '<lemma>' . $lemma . '</lemma>';
   $xmlText .= $xmlStr . '</div>';

   $xmlDoc = new DOMDocument();
   $xmlDoc->resolveExternals = true;
   $xmlDoc->substituteEntities = true;
   $xmlDoc->loadXML($xmlText);

   $xslDoc = new DOMDocument();
   $xslDoc->load($xsltFile);

   $proc = new XSLTProcessor();
   $proc->importStylesheet($xslDoc);
   $txt = $proc->transformToXML($xmlDoc);
   
   return $txt;
}

?>