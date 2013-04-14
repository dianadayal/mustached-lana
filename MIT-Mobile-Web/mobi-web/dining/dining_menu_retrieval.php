<?php
    
    $docRoot = getenv("DOCUMENT_ROOT");
    
    require_once $docRoot . "/MIT-Mobile-Web/mobi-config/mobi_web_constants.php";
    require WEBROOT . "page_builder/page_header.php";
    require LIBDIR . "/mit_calendar.php";
    require WEBROOT . "calendar/calendar_lib.php";
    require WEBROOT . "dining/menu_formatter.php";

    
    class dining_menu_retrieval {
        function retrieve_today(){
        date_default_timezone_set('America/New_York');
            echo $docRoot;
        $folder_dir = $docRoot . "/MIT-Mobile-Web/mobi-web/dining/dining_menu_cache/";
        
        if(!is_dir($folder_dir)){
            if(!mkdir($folder_dir, 0755))
            {
                die('Could not create folder');
            }
        }
    
            $menu = new menu_formatter();
            $filename = $menu->getMenu(date("d"), date("m"), date("Y"));
            file_put_contents($folder_dir . "menu_". strval(date("m_d_Y")) . ".html", $full_file);
        }

    }
    ?>
