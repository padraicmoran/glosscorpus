<?php
require "functions/write_gloss.php";

require "template/template.php";
templateHeader($pageTitle, $carouselSlug);
?>

<div class="container px-0">
	<div class="col-xl-9">


<h2>About Gloss Corpus</h2>

<p>Gloss Corpus is a fully open-access, open-standards resource for the collaborative publication and study of glosses on medieval 
	manuscripts. It aims to support the glossing <a href="http://www.glossing.org">research community</a> by sharing resources and tools, 
	including tools for data analytics and visualisation (currently still in development).
</p>
	
<p>This resource was developed by <a href="https://www.universityofgalway.ie/our-research/people/padraicmoran/">Pádraic
	Moran</a> (University of Galway). It builds on his earlier work on the digital edition
	of the <a href="http://www.stgallpriscian.ie/">St Gall Priscian Glosses</a>. 
	It now forms part of a broader research project, <a href="http://www.glossam.ie">Global and Local Scholarship on Annotated
	Manuscripts</a> (GLOSSAM), funded by Research Ireland, 2022–2026.
</p>

<p>Its editorial principles are:
</p>

<ul>
<li>Contributors receive full credit for their own content.</li>
<li>Editions are citable with unique DOIs and persistent URL.</li>
<li>Editions may be optionally peer-reviewed by an editorial committee.</li>
</ul>

<!-- Expand on peer-review committee -->

<h3 class="mt-5 mb-3">Guidelines for publishing</h3>

<p>In the first place, contact Pádraic Moran (<a href="mailto:padraic.moran@universityofgalway.ie">padraic.moran@universityofgalway.ie</a>)
to discuss your project.
</p>

<p>Content should be provided in XML format, conforming to the usage guidelines described below. 
(XML is very easy to learn. If you are unfamiliar with it, Pádraic will give advice.)</p>


<h4 class="mt-5 mb-3">1. XML templates</h4>

<p>Version 1.0:</p>

<ul>
<li><a href="/glosscorpus/data/templates/GlossCorpus_basetext_v1.xml">Primary text (base text)</a></li>
<li><a href="/glosscorpus/data/templates/GlossCorpus_gloss_collection_v1.xml">Gloss collection</a></li>
<li><a href="/glosscorpus/data/templates/GlossCorpus_publication_v1.xml">Publication</a></li>
</ul>

<h4 class="mt-5 mb-3">2. Documentation</h4>

<p>Download the Gloss Corpus encoding guidelines: <a href="/glosscorpus/docs/Gloss Corpus_encoding guidelines.pdf">version 1.0</a> (18 December 2024)</p>


<h4 class="mt-5 mb-3" id="xml">3. Illustration of tags for manuscript transcription</h4>

<p>In general, the representation of text in editorial transcriptions conforms to the mark-up methods recommended by 
	the <a href="https://www.tei-c.org/release/doc/tei-p5-doc/en/html/PH.html">Text Encoding Initiative</a> (TEI).
	</p>

<p>The following tags are currently accommodated. (Moving your mouse over text in the presentation column may show 
	additional information.) Editors may choose to use whichever tags are appropriate for their goals.
	</p>

<p>3.1 Tags that describe the appearance of the manuscript:</p>

<table class="table small mb-4">
<tr>
 	<th>Tag</th>
 	<th>Purpose</th>
 	<th>Sample code</th>
	<th>Presentation</th>
</tr>
<?php 
presentTag('<add></add>', 'Secondary addition in manuscript', 'glossa<add>s</add> lego'); 
presentTag('<del></del>', 'Text cancelled in manuscript', 'glossas<del>s</del> lego'); 
presentTag('<g></g>', 'Graphical symbol in manuscript (represented in transcription with a similar keyboard/Unicode character)', '<g>%</g>'); 
presentTag('<lb/>', 'Line break in manuscript', 'glos<lb/>sas lego'); 
presentTag('<space/>', 'Blank space in manuscript. 
	(You may optionally indicate an extent: e.g. <code>&lt;space extent="1 line"/&gt;</code>.)', 'glossas <space extent="1 line"/> lego'); 
?>
</table>

<p>3.2 Tags that document editorial interventions:</p>

<table class="table small mb-4">
<tr>
 	<th>Tag</th>
 	<th>Purpose</th>
 	<th>Sample code</th>
	<th>Presentation</th>
</tr>
<?php 
presentTag('<corr></corr>', 'Editorial correction', 'glossis <corr>glossas</corr> lego'); 
presentTag('<ex></ex>', 'Editorial expansion', 'liber glossa<ex>rum</ex>'); 
presentTag('<gap/>', 'Gap in editorial transcription due to illegibility, damage, etc. 
	(You may optionally indicate an extent or reason: e.g. <code>&lt;gap extent="3 letters" reason="illegible"/&gt;</code>.)', '<gap extent="7 letters" /> lego'); 
presentTag('<note type="editorial"></note>', 'Editorial note (shows on mouse over)', 'glossas lego <note type="editorial">Written in Tironian signs</note>'); 
presentTag('<sic></sic>', 'Editorial marking of apparent errors', '<sic>glossis</sic> lego'); 
presentTag('<supplied></supplied>', 'Editorially supplied text', 'glos<supplied>s</supplied>as lego'); 
presentTag('<surplus></surplus>', 'Editorial marking of redundant text', 'glossas<surplus>s</surplus> lego'); 
presentTag('<unclear></unclear>', 'Editorial uncertainty in transcription', '<unclear>g</unclear>lossas lego'); 
?>
</table>


</div>
</div>
<?php

templateFooter();


function presentTag($tag, $desc, $sample) {
	echo '<tr>';
	echo '<td width="160"><code>' . htmlentities($tag) . '</code></td>';
	echo '<td>' . $desc . '</td>';
	echo '<td><code>' . htmlentities($sample) . '</code></td>';
	echo '<td>' . renderGloss($sample, '') . '</td>';
	echo '</tr>';
}

?>