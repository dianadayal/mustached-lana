<?php
    
    $docRoot = getenv("DOCUMENT_ROOT");
    
    require_once $docRoot . "/MIT-Mobile-Web/mobi-config/mobi_web_constants.php";
    require WEBROOT . "page_builder/page_header.php";
    require LIBDIR . "/mit_calendar.php";
    require WEBROOT . "calendar/calendar_lib.php";
    
  
        define("ACADEMIC_CALENDAR_URL_ROOT",'http://registrar.unc.edu/academic-calendar/academic-years-');
        
        date_default_timezone_set('America/New_York');
        
        $folder_dir = $docRoot . "/MIT-Mobile-Web/mobi-web/calendar/CACHE_DIRACADEMIC_CALENDAR/";
        
        if(!is_dir($folder_dir)){
            if(!mkdir($folder_dir, 0755))
            {
                die('Could not create folder');
            }
        }
        
        $fiscal_year = date("Y");
        $current_academic_calendar_url = ACADEMIC_CALENDAR_URL_ROOT . ($fiscal_year - 1) . '-' . ($fiscal_year) . '-and-' . ($fiscal_year) . '-' . ($fiscal_year + 1) . '/';
        //echo $current_academic_calendar_url;
        $academic_calendar_raw = file_get_contents($current_academic_calendar_url);
        $search = '<tr>' . "\n" . '<' . 'td style=' . '"' . 'text-align: left' . ';' . '"' . '>';
        echo $search;
        $academic_calendar_raw = str_replace($search,'MSG_IND:', $academic_calendar_raw);
        
        $academic_calendar_raw = str_replace('<' . 'td style=' . '"' . 'text-align: left' . ';' . '"' . '>','MONTH_IND:' ,$academic_calendar_raw);
        
        $academic_calendar_raw = str_replace('</td>', '', $academic_calendar_raw);
        $academic_calendar_raw = str_replace('</tr>', '', $academic_calendar_raw);
    
    
    $newline_event = substr($academic_calendar_raw, strpos('$academic_calendar_raw', 'MONTH_IND'));
    echo $newline_event;
    echo $folder_dir;
    $file = "academic_year" . $fiscal_year . "-" . ($fiscal_year+1);
    
    file_put_contents($folder_dir . $file, $newline_event . ';');
    
    
    
    
    echo $academic_calendar_raw;
    
    
    function return_months($date){}
    
    
    ?>
