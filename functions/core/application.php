<?php
// key shared files
require __DIR__ . "/config.php";
require __DIR__ . "/utils.php";

$link = mysqli_connect($dbHost, $dbUser, $dbPwd, $dbName);
mysqli_query($link, "SET NAMES 'utf8mb4'");
$link->set_charset("utf8mb4");

// Apache settings
header("Content-type: text/html; charset=utf-8");

if ($staging) {
   $version = 'STAGING';
}
else {
   $version = 'version ' . $versionNo . ' (' . $versionYear . ')';
}

?>