<?php

// Show header carousel
function showCarousel($carouselSlug) {
	// loal carousel details from config file
	global $carousel;
		
	echo '<div id="headerCarousel" class="carousel slide" data-bs-ride="carousel">';

	// slides
	echo '  <div class="carousel-inner">';
	if ($carouselSlug == '*') {	// display all slides, with animation
		$start = rand(0, sizeof($carousel) - 1);
		for ($n = 0; $n < sizeof($carousel); $n++) {
			if ($n == $start) echo '    <div class="carousel-item active">';
			else echo '    <div class="carousel-item">';
			echo '      <img src="images/headers/' . $carousel[$n][1] . '" class="d-block w-100" alt="' . $carousel[$n][2] . '">';
			echo '    </div>';
		}
	}
	else {	// display selected slide if found
		$match = -1;
		for ($n = 0; $n < sizeof($carousel); $n++) {
			if ($carousel[$n][0] == $carouselSlug) $match = $n;
		}
		if ($match > -1) {
			echo '    <div class="carousel-item active">';
			echo '      <img src="images/headers/' . $carousel[$match][1] . '" class="d-block w-100" alt="' . $carousel[$match][2] . '">';
			echo '    </div>';
		}
	}
	echo '  </div>';

	// arrows
	if ($carouselSlug == '*') {	// display prev & next arrows
		echo '<button class="carousel-control-prev" type="button" data-bs-target="#headerCarousel" data-bs-slide="prev">';
		echo '	<span class="carousel-control-prev-icon" aria-hidden="true"></span>';
		echo '	<span class="visually-hidden">Previous</span>';
		echo '</button>';
		echo '<button class="carousel-control-next" type="button" data-bs-target="#headerCarousel" data-bs-slide="next">';
		echo '	<span class="carousel-control-next-icon" aria-hidden="true"></span>';
		echo '	<span class="visually-hidden">Next</span>';
		echo '</button>';
	}

	echo '</div>';
}

?>