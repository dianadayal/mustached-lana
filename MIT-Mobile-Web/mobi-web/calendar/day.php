<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/MIT-Mobile-Web/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/page_header.php";
require LIBDIR . "mit_calendar.php";

//defines all the variables related to being today
require WEBROOT . "calendar/calendar_lib.php";

$time = $_REQUEST['time'];
$current = day_info($time);
$next = day_info($time, 1);
$prev = day_info($time, -1);
$type = $_REQUEST['type'];
echo $type;
$Type = ucwords($type);
echo $Type;
$methodName = "Todays{$Type}Headers";
$events = MIT_Calendar::$methodName($current['date']);
var_dump($events);
require "$page->branch/day.html";
$page->output();

?>
