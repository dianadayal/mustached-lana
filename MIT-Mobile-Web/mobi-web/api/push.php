<?
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/MIT-Mobile-Web/mobi-config/mobi_web_constants.php";
require_once(LIBDIR . 'StellarData.php');
StellarData::init();

$uid = $_REQUEST['uid'];
$subject = $_REQUEST['subject'];

if (isset($_REQUEST['subscribe'])) {
  StellarData::push_subscribe($subject, $uid);
} elseif (isset($_REQUEST['unsubscribe'])) {
  StellarData::push_unsubscribe($subject, $uid);
}


?>