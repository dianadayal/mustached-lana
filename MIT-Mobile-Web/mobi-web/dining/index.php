<?php
$docRoot = getenv("DOCUMENT_ROOT");



    require_once $docRoot . "/MIT-Mobile-Web/mobi-config/mobi_web_constants.php";

require WEBROOT . "page_builder/page_header.php";
require WEBROOT . "mobile-about/WhatsNew.php";
date_default_timezone_set('America/New_York');
// dynamic pages need to include dynamics scripts
switch($_REQUEST['page']) {

  // dynamic cases
  case "statistics":
    require "statistics.php";
    break;

  // static cases
  case "background":
    $device_phrases = array(
      "Webkit" => "iPhone, Android, and Palm webOS phones",
      "Touch" => "touchscreen phones",
      "Basic" => "non-touchscreen phones"
    );
    $device_phrase = $device_phrases[$page->branch];

  case "requirements":
  case "credits":
    require "$page->branch/{$_REQUEST['page']}.html";
    $page->cache();
    $page->output();
    break;

  case "about":
  default:
// $whats_new = new WhatsNew();
//    $whats_new_count = $whats_new->count(WhatsNew::getLastTime());
//    require "$page->branch/index.html";
        $todays_menu = new dining_menu_retrieval();
        $menu_file = WEBROOT . "dining/$page->branch/dining_menu_cache/menu_". strval(date("m_d_Y")) . ".html";
        
        if(!file_exists($menu_file)){
           $todays_menu->retrieve_today("$page->branch");
        }
    
        require "$page->branch/dining_menu_cache/menu_". strval(date("m_d_Y")) . ".html";
        
        $page->output();
}
    
    
    class dining_menu_retrieval {
        
        function retrieve_today($branch){
            require WEBROOT . "dining/menu_formatter.php";
            date_default_timezone_set('America/New_York');
            $docRoot = getenv("DOCUMENT_ROOT");
           // $menu_folder_dir = $docRoot . "/MIT-Mobile-Web/mobi-web/dining/Basic/dining_menu_cache/";
            $menu_folder_dir = $docRoot . "/MIT-Mobile-Web/mobi-web/dining/".$branch."/dining_menu_cache";
           
            if(!is_dir($menu_folder_dir)){
                if(!mkdir($menu_folder_dir, 0755))
                {
                    die('Could not create folder');
                }
            }
            
            $menu = new menu_formatter();
            $full_file = $menu->getMenu(date("d"), date("m"), date("Y"), $branch);
            
            //26 $full_file = $menu->getMenu(26, date("m"), date("Y"), $branch);
            file_put_contents($menu_folder_dir . "/menu_". strval(date("m_d_Y")) . ".html", $full_file);
}
}

    
    
    
    


?>
