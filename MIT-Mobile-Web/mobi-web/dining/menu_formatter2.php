<?php
    
    
    class menu_formatter {
    function getMenu($day, $month, $year, $branch){
        date_default_timezone_set('America/New_York');

        $time = mktime(0,0,0,$month,$day,$year);
        $fp = file_get_contents('http://www.dining.unc.edu/Menus?dt=' . date('m/d/Y', $time));
        $tp = "";
        $bo= 0;
        $in = 0;
        for($in = 0 ; $in < strlen($fp) ; $in++){
            if($fp{$in} == "<"){
                $bo++;
            }
            if($bo==0){
                $tp = $tp . $fp{$in};
            }
            
            if(strpos(substr($fp, $in), "PCTMealCardItem") == 1){
                $tp = $tp . "\r\n" . "   ";
            }
            if($fp{$in} == ">"){
                $bo--;
            }
        }
        
        $date = date('M d',$time);
        $tp = substr($tp, strrpos($tp, $date) + strlen($date));
        $tp = substr($tp, 0, strrpos($tp, "//"));
        $tp = '<p class="nav">' . $tp . '</p>';
        $tp = str_replace(date("l").' Breakfast', 'HIDDEN1', $tp);
        $tp = str_replace('Breakfast', '<li><a href="./?page=breakfast">Breakfast</a></li>', $tp);
        $tp = str_replace('HIDDEN1', date("l").' Breakfast', $tp);
        $tp = str_replace('Dinner', '<li><a href="./?page=dinner">Dinner</a></li>', $tp);
        $tp = str_replace('Lunch', '<li><a href="./?page=lunch">Lunch</a></li>', $tp);
        $tp = str_replace("See menu details...", "" ,$tp);
        $tp = '<?php $page->title(' . "'DiningMenu'" . ') ->header(' . "'Dining Menu'" . '); $page->content_begin(); ?>' . $tp . '<? $page->content_end(); $page->help_off(); ?>';
        
        
        
        return $tp;
    }
}
?>