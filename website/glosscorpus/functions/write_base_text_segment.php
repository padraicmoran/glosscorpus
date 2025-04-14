<?php

function writeBaseTextSegment($rowS) {
   global $hi, $segmentHighlight;

   echo '<tr class="bg-light">'; 
   echo '<td class="small text-muted text-nowrap ' . $hi . '">' . $rowS['ref'];
   echo '</td>';
   echo '<td colspan="6" class="' . $hi . '"';
   if ($segmentHighlight == $rowS['recordID']) echo ' id="hi">';
   echo '>';

   // Write base text content
   $baseText = $rowS['text'];
   echo renderBaseTextSegment($baseText);

   echo '</td>';
   echo '</tr>' . "\n";
}


function renderBaseTextSegment($xmlStr) {
   global $staging;
   if ($staging) $xsltFile = './xslt/render_base_text_segment_staging.xsl';
   else $xsltFile = './xslt/render_base_text_segment.xsl';

   $xmlDoc = new DOMDocument();
   $xmlDoc->resolveExternals = true;
   $xmlDoc->substituteEntities = true;
   $xmlDoc->loadXML('<!DOCTYPE div>
      <div>' . $xmlStr . '</div>');

   $xslDoc = new DOMDocument();
   $xslDoc->load($xsltFile);

   $proc = new XSLTProcessor();
   $proc->importStylesheet($xslDoc);
   $txt = $proc->transformToXML($xmlDoc);
   
   return $txt;
}
/*
Old formatting to be reviewed:

   if ($highlight != '') $txt = preg_replace('/' . $highlight . '/', '<span class="highlight">$0</span>', $txt);
   $txt = preg_replace('/ &gt; (\([0-9a-z,=\s\'"^\ap\.)]*?\))/', ' &gt; <span class="text-secondary small">$1</span>', $txt);
   $txt = preg_replace('/\(ibid\.\)/', '<span class="text-secondary small">$0</span>', $txt);
   $txt = str_replace(' &gt; <span', ' <span class="text-secondary small">→</span> <span', $txt);
   $txt = preg_replace('/\{[^\}]*?\}/', '<span class="note">$0</span>', $txt);
   $txt = preg_replace('/ 7 /', ' ⁊ ', $txt);
*/


// Get each lemma in this segment of base text
// Returns an associated array [xml:id] = lemma
// Used for supplying a base text lemma where a lemma is not specified in the gloss entry
function getBaseTextLemmata($text) {
   /*
   <w xml:id="II_5.1__1">philosophi</w> <w xml:id="II_5.1__2">definiunt,</w> <w xml:id="II_5.1__3">uocem</w> <w xml:id="II_5.1__4">esse</w> <w xml:id="II_5.1__5">aerem</w> <w xml:id="II_5.1__6">tenuissimum</w> <w xml:id="II_5.1__7">ictum</w> <w xml:id="II_5.1__8">uel</w> <w xml:id="II_5.1__9">suum</w>
   */
  // Load the XML string into a DOMDocument
   $dom = new DOMDocument;
   $dom->loadXML('<ab>' . $text . '</ab>'); // Adding a root element to make it valid XML

   $lemmata = [];
   // Loop through each <w> and <seg> element and add its content to the array
   foreach ($dom->getElementsByTagName('w') as $word) {
      $xmlID = strval($word->getAttribute('xml:id'));
      // Get the part after the "__" in the xml:id
      $parts = explode('__', $xmlID);
      if (count($parts) == 2) {
         $lemmata[intval($parts[1])] = $word->textContent;
      }
   }
   foreach ($dom->getElementsByTagName('seg') as $word) {
      $xmlID = strval($word->getAttribute('xml:id'));
      // Get the part after the "__" in the xml:id
      $parts = explode('__', $xmlID);
      if (count($parts) == 2) {
         $lemmata[intval($parts[1])] = $word->textContent;
      }
   }
   return $lemmata;
}


?>