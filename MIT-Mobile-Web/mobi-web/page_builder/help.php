<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/MIT-Mobile-Web/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/page_header.php";

require "../$page->branch/help.html";

$page->cache();
$page->output();

?>
