<?php
require "../functions/core/application.php";
require "common.php";

require "../pages/template/template.php";
templateHeader('CMS', '');

$alertTypes = initaliseAlerts();
?>

<a class="float-end btn btn-primary btn-sm btn-secondary" href="./">CMS home</a>
<h2>Updating totals of published primary texts, collections, glosses</h2>

<?php
updateTotals();
templateFooter();
?>