<?php
require "carousel.php";

// Template header
function templateHeader($pageTitle, $carouselSlug) {
	global $version;
   ?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title>Gloss Corpus<?php if ($pageTitle <> '') echo ': ' . $pageTitle; ?></title>

	<meta name="DC.title" lang="en" content="Gloss Corpus">
	<meta name="DC.description" lang="en" content="Gloss Corpus is a fully open-access, open-standards resource for the collaborative publication and study of glosses on medieval manuscripts. It aims to support the glossing research community by sharing resources and tools, including tools for data analytics and visualisation. ">
	<meta name="DC.creator" content="Pádraic Moran">
	<meta name="DC.publisher" content="Gloss Corpus">
	<meta name="DC.type" content="Text">
	<meta name="DC.format" content="text/html">
	<meta name="DC.coverage" content="Global">
	<meta name="DC.source" content="University of Galway">
	<meta name="DC.language" content="en_IE">

	<!-- libraries -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
	<script src="https://unpkg.com/@popperjs/functions/core@2"></script>
	<script src="/glosscorpus//pages/template/gloss-corpus.js"></script>

	<!-- favicons -->
	<link rel="apple-touch-icon" href="/glosscorpus/images/favicons/apple-touch-icon.png" sizes="180x180">
	<link rel="icon" type="image/x-icon" href="/glosscorpus/images/favicons/favicon-32x32.ico" sizes="32x32" type="image/png">
	<link rel="icon" type="image/x-icon" href="/glosscorpus/images/favicons/favicon-16x16.ico" sizes="16x16" type="image/png">
	<link rel="icon" type="image/x-icon" href="/glosscorpus/images/favicons/favicon.ico">

	<!-- font and local stylesheet -->
	<link href="https://fonts.googleapis.com/css2?family=Fira+Sans:ital,wght@0,400;0,600;1,400;1,600&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="/glosscorpus/pages/template/gloss-corpus.css">
</head>

<body>

<nav class="navbar navbar-expand-sm navbar-dark bg-dark bg-gradient px-4">
	<a class="navbar-brand large" href="/glosscorpus/"><span class="h2">Gloss Corpus</span> <span class="navbar-text small text-muted"><?php echo $version; ?></span></a>

	<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
   <span class="navbar-toggler-icon"></span>
   </button>
    
	<div class="collapse navbar-collapse" id="navbarSupportedContent">
	<ul class="navbar-nav me-auto ms-5">
	<li class="nav-item ms-2"><a class="nav-link" href="/glosscorpus/">Home</a></li>
	<li class="nav-item ms-2"><a class="nav-link" href="/glosscorpus/?page=search">Search</a></li>
	<li class="nav-item ms-2"><a class="nav-link" href="/glosscorpus/?page=analytics">Analytics</a></li>
	<li class="nav-item ms-2"><a class="nav-link" href="/glosscorpus/?page=about">About</a></li>
	</ul>
	</div>
</nav>

<?php
	// carousel
	if ($carouselSlug == '') {
		echo '<main class="container mt-5 pt-2" style="min-height: 450px; ">';
	}
	else {
		showCarousel($carouselSlug);
		echo '<main class="container mt-0 pt-5" style="min-height: 450px; ">';
	}
}


// Template footer
function templateFooter() {
	global $version;
   ?>

</main>

<footer class="container-fluid bg-secondary border mt-5 px-3 pt-3 pb-5 text-light small">
	<div class="container">

<?php
echo 'Pádraic Moran, <i>Gloss Corpus</i>, ' . $version . ' ' .
	'&lt;http://www.glossing.org/glosscorpus/&gt; ' .
	'[accessed ' . date("j F Y") . ']';

?>

	</div>
</footer>


<!-- Mirador viewer -->
<div id="msViewer" class="fixed-bottom bg-light d-none" style="height: 50vh; ">
	<!-- close button -->
	<div id="msViewerClose" style="text-align: right; margin-top: -27px; ">
		<button class="btn btn-secondary btn-sm me-1" type="button" onclick="hideMsViewer(); return false;">Close MS viewer</button>
	</div>

	<script src="https://unpkg.com/mirador@latest/dist/mirador.min.js"></script>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500">

	<div id="miradorViewer" class="rounded shadow-lg" style="height: 50vh;">
		<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
		Loading image viewer…
	</div>
</div>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

<!-- Script for tooltips -->
<script language="JavaScript" type="text/javascript">

var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl)
})

</script>

</body>
</html>

<?php
}
?>