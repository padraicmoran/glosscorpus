<?php
require "functions/core/application.php";

// Get page selector
$page = cleanInput('page');
$sectionID = intval(cleanInput('sec'));

// Prepare for page routing
// If a base stext section is selected, route to collection page
if ($sectionID > 0) {
	$page = 'collection';
}

// Page routing
switch($page) {
	case 'collection':
		// Show collection (for this sectionID)
		require 'pages/collection.php';
		break;

	case 'search':
		// Load search page
		$pageTitle = 'Search';
		$carouselSlug = 'isidore';
		require 'pages/search.php';
		break;

	case 'analytics':
		// Load analytics page
		$pageTitle = 'Analytics';
		$carouselSlug = 'priscian';
		require 'pages/analytics.php';
		break;

	case 'publication':
		// Load publications page
		require 'pages/publication.php';
		break;

	case 'about':
		// Load about page
		$pageTitle = 'About';
		$carouselSlug = 'paul';
		require 'pages/about.php';
		break;

	default:
		// Default to home page if no match
		$pageTitle = '';
		$carouselSlug = '*';
		require 'pages/home.php';
		break;
}

?>