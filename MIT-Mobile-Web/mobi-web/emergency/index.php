<?php

$docRoot = getenv("DOCUMENT_ROOT");
require_once $docRoot . "/MIT-Mobile-Web/mobi-config/mobi_web_constants.php";
require_once LIBDIR . "rss_services.php";

$contacts = json_decode(file_get_contents(LIBDIR . "EmergencyContacts.json"), TRUE);

$emergency_message = "Coming Soon: Emergency Updates"; 
$Emergency = new Emergency();
$emergency = $Emergency->get_feed_html();
////UNC EDIT
    $alert_carolina_page = file_get_contents("http://m.alertcarolina.unc.edu/go/doc/1395/1319331/?sp=mobile");
    
    
   
///UNC EDIT

if($emergency === False) {
  $paragraphs = array('Emergency information is currently not available');
    $paragraphs = array();
} else {
$paragraphs = array($emergency[0]['text']);
}
//VERY STRING AND CHEWING GUM UNC EDIT
    if($alert_carolina_page == False){
        $paragraphs = array('Emergency information is currently not available');}
    else{
    $alert_carolina_page = substr($alert_carolina_page, stripos($alert_carolina_page, "headline") + 14);
    $alert_carolina_page = substr($alert_carolina_page, 0, stripos($alert_carolina_page, "span"));
    $paragraphs = array($alert_carolina_page);
    }
//I APOLOGIZE! I'M REALLY REALL SORRY YOU HAD TO SEE THAT!
    
// the logic to implement the page begins here
require WEBROOT . "page_builder/page_header.php";

if(isset($_REQUEST['contacts'])) {
  require "$page->branch/contacts.html";
} else {
  $main = array_splice($contacts, 0, 3);
  require "$page->branch/index.html";
}

$page->output();

function contactsURL() {
  return "./?contacts=true";
}

function number_text($item, $convert=FALSE) {
  $num = ($convert) ? replace_letters($item['phone']) : $item['phone'];
  return insert_dots($num);
}

function message_text($item) {
  $message = $item['description'];
  return ($message) ? $message . ': ' : '';
}

function dialURL($item) {
  $num = $item['phone'];
  return 'tel:1' . replace_letters($num);
}

function insert_dots($num) {
  return substr($num, 0, 3) . "." . substr($num, 3, 3) . "." . substr($num, 6, 4);
}

function replace_letters($phone) {
  $letters = array("/[A-C]/", "/[D-F]/", "/[G-I]/", "/[J-K]/", "/[M-O]/", "/[P-S]/", "/[T-V]/", "/[W-Z]/");
  $replacements = array('2', '3', '4', '5', '6', '7', '8', '9');
  return preg_replace($letters, $replacements, $phone);
}

?>
