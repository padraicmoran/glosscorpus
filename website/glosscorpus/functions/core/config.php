<?php

$staging = false;    // switch for local mirror of live or staging sites
$versionNo = "1.0";
$versionYear = "2024";
$baseURL = '/glosscorpus/';

$dbHost = '';
$dbUser = '';
$dbPwd = '';
$dbName = '';

$carousel = array(	// slug, image name, heading; images in folder images/headers
	array('priscian', 'priscian.jpg', 'Glosses on Priscian'),
	array('isidore', 'isidore_reims426_9r.jpg', 'Glosses on Isidore'),
	array('paul', 'paul_wb_9r.jpg', 'Glosses on Paul\'s Epistles')
);

// database connection
// local development server (live and staging versions)
if ($_SERVER['SERVER_NAME'] == 'glosscorpus') {
   $dbHost = 'localhost';
   $dbUser = 'root';
   $dbPwd = 'comedici';
   if ($staging) $dbName = 'gcorpus_stage';
   else $dbName = 'glosscorpus';

   ini_set('display_errors', '1');
   error_reporting(E_ALL);
}

// remote staging server
elseif ($_SERVER['SERVER_NAME'] == 'staging.glossing.org') {
   $staging = true;
   $dbHost = 'mysql4543int.cp.blacknight.com';
   $dbUser = 'u1161464_gcstage';
   $dbPwd = 'kn5ReqIJ}';
   $dbName = 'db1161464_gcorpus_stage';

   ini_set('display_errors', '1');
   error_reporting(E_ALL);
}

// remote live server
else {
   $staging = false;
   $dbHost = 'mysql4544int.cp.blacknight.com';
   $dbUser = 'u1161464_gcorp';
   $dbPwd = '4REeusala&@';
   $dbName = 'db1161464_glosscorpus';

   ini_set('display_errors', '0');
   ini_set('log_errors', '1'); 
   ini_set('error_log', '/glosscorpus/logs/error.log'); 
   error_reporting(E_ALL); // Report all errors but do not display them
}

?>