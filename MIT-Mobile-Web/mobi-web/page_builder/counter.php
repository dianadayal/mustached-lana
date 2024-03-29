<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/MIT-Mobile-Web/mobi-config/mobi_web_constants.php";
require_once WEBROOT . 'home/Modules.php';
require_once LIBDIR . 'db.php';

class PageViews {

  public static function log_api($module, $platform, $time=NULL) {
    $extra = serialize($_GET);
    self::log_item('api', $module, $platform, $extra, $time);
  }

  public static function increment($module, $platform, $time=NULL) {
    self::log_item('web', $module, $platform, "", $time);
  }

  public static function log_item($system, $module, $platform, $extra, $time) {
    if ($time === NULL)
      $time = time();

    if ($system == 'web')
      $logfile = WEB_CURRENT_LOG_FILE;
    else // assume 'api'
      $logfile = API_CURRENT_LOG_FILE;
    
   $fh = fopen($logfile, 'a');
     //date.timezone = 'America/New_York';
    date_default_timezone_set('GMT');
    fwrite($fh, sprintf("%s %s %s: %s\n",
			date(LOG_DATE_FORMAT, $time),
			$platform, $module, $extra));
    fclose($fh);
  }

  private function increment_array(&$array, $day, $platform, $module) {
    if (!array_key_exists($day, $array))
      $array[$day] = array();
    if (!array_key_exists($platform, $array[$day]))
      $array[$day][$platform] = array();
    if (!array_key_exists($module, $array[$day][$platform]))
      $array[$day][$platform][$module] = 1;
    else
      $array[$day][$platform][$module] += 1;
  }

  public static function export_stats($system) {
    if ($system == 'web') {
      $table = PAGE_VIEWS_TABLE;
      $logfile = WEB_CURRENT_LOG_FILE;
      $target = WEB_LOG_FILE;
    } else {// assume 'api'
      $table = API_STATS_TABLE;
      $logfile = API_CURRENT_LOG_FILE;
      $target = API_LOG_FILE;
    }
    
    $today = date('Ymd', time());
    
    if (file_exists($target) && date('Ymd', filemtime($target)) == $today)
      return; // we have already exported today
    $logfilecopy = "/tmp/mobi_log_copy.$today";
    
    if (!$outfile = fopen($target, 'a')) {
      error_log("could not open $target for writing");
      return;
    }
    
    if (!rename($logfile, $logfilecopy)) {
      error_log("failed to rename $logfile to $logfilecopy");
      return; 
    }

    if (!touch($logfile)) {
      error_log("failed to create empty $logfile");
      return; 
    }
    
    $db = db::$connection;

    $result = $db->query(
      "SELECT day, platform, module, viewcount FROM $table
        WHERE day=(SELECT MAX(day) FROM $table)");

    $stats = Array();
    while ($row = $result->fetch_assoc()) {
      self::increment_array($stats, $row['day'], $row['platform'], $row['module']);      
    }

    $infile = fopen($logfilecopy, 'r');
    $date_length = strlen(date(LOG_DATE_FORMAT));
    while (!feof($infile)) {
      $line = fgets($infile, 1024);
      fwrite($outfile, $line);

      if (preg_match(LOG_DATE_PATTERN, $line, $matches) == 0)
	continue;

      // the following match positions should also be defined where
      // the date regex is defined
      $day = sprintf("%s-%s-%s", $matches[3], $matches[1], $matches[2]);
      preg_match('/^.{' . $date_length . '} (\w+) (\w+):/', $line, $matches);
      $platform = $matches[1];
      $module = $matches[2];
      if ($module) {
        self::increment_array($stats, $day, $platform, $module);
      }
    }
    fclose($outfile);
    fclose($infile);

    if ($stats) {
      $db->query('LOCK TABLE $table WRITE');
      $db->query("DELETE FROM $table WHERE day=(SELECT MAX(day) FROM $table)");
      foreach ($stats as $day => $platforms) {
	foreach ($platforms as $platform => $modules) {
	  foreach ($modules as $module => $count) {
	    $sql = "INSERT INTO $table ( day, platform, module, viewcount )
                         VALUES ('$day', '$platform', '$module', $count)";
	    if (!$db->query($sql)) {
	      error_log("mysql query failed: $sql");
	    }
	  }
	}
      }
      $db->query("UNLOCK TABLE");
    }

    unlink($logfilecopy);
  }

  public static function quarter_of($timestamp) {
    $m = date('n', $timestamp) - 1; // need zero-based counting
    $m = $m - ($m % 3) + 1;
    $y = date('Y', $timestamp);
    return mktime(0, 0, 0, $m, 1, $y);
  }

  /* get total viewcount for platform $platform (default all platforms),
   * module $module (default all modules),
   * between dates $start and $end (any string compatible with strtotime)
   */
  private static function getTimeSeries($system, $start, $platform=NULL, $module=NULL, $end=NULL) {
    self::export_stats($system);

    $db = db::$connection;
    $sql_fields = Array();
    $sql_criteria = Array();

    if ($system == 'web')
      $table = PAGE_VIEWS_TABLE;
    else // assume 'api'
      $table = API_STATS_TABLE;

    if (($end === NULL) || (strtotime($end) - strtotime($start) == 86400)) {
      $sql_criteria[] = "day='$start'";
    } else {
      $sql_criteria[] = "day >= '$start' AND day < '$end'";
      $groupby = Array();
    }

    if ($platform !== NULL) {
      $sql_criteria[] = "platform='$platform'";
    } else {
      $sql_fields[] = 'platform';
    }

    if ($module !== NULL) {
      $sql_criteria[] = "module='$module'";
    } else {
      $sql_fields[] = 'module';
    }

    if (count($sql_fields) == 2 && !isset($groupby)) {
      $sql_fields[] = 'viewcount';
    } else {
      $groupby = $sql_fields;
      $sql_fields[] = 'SUM(viewcount) AS viewcount';
    }

    $sql = "SELECT " . implode(', ', $sql_fields);
    //array_pop($sql_fields);
    $sql .= ' FROM ' . $table . ' WHERE ' . implode(' AND ', $sql_criteria);
    $sql .= (isset($groupby) && count($groupby)) ? ' GROUP BY ' . implode(', ', $groupby) : '';

    //var_dump($sql);
    $result = $db->query($sql);

    $output = Array();
    // results are returned as (not necessarily in this order):
    // Array('module' => ..., 'platform' => ..., 'viewcount' => ...)
    // one row per platform/module combo
    while($row = $result->fetch_assoc()) {
      $output[] = $row;
    }

    if (count($output) == 1 && $output[0]['viewcount'] === NULL) {
      return NULL;
    }
    return $output;
  }

  public static function view_past($system, $time_unit, $duration) {
    $increments = Array();
     // figure out value to use for $begin in sql query
    // and number of seconds for each $increment
    $time = time();
    switch ($time_unit) {
    case 'day':
      $begin = $time - $duration * 86400;
      $increments = array_pad($increments, $duration, 86400);
      break;
    case 'week':
      $begin = $time - $duration * 86400 * 7;
      $increments = array_pad($increments, $duration, 86400 * 7);
      break;
    case 'month':
      $month = date('n', $time) + 1;
      $year = date('Y', $time) - 1;
      if ($month > 12) {
	$month = 1;
	$year += 1;
      }
      $begin = mktime(0, 0, 0, $month, 1, $year);
      $last_begin = $begin;
      for ($i = 0; $i < $duration; $i++) {
	$month += 1;
	if ($month > 12) {
	  $month = 1;
	  $year += 1;
	}
	$next_begin = mktime(0, 0, 0, $month, 1, $year);
	$increments[] = $next_begin - $last_begin;
	$last_begin = $next_begin;
      }
      break;
    case 'quarter':
      $current_quarter = PageViews::quarter_of($time);
      $month = date('n', $current_quarter) + 3;
      $year = date('Y', $current_quarter) - 3;
      if ($month > 12) {
	$month -= 12;
	$year += 1;
      }
      $begin = mktime(0, 0, 0, $month, 1, $year);
      $last_begin = $begin;
      for ($i = 0; $i <= $duration; $i++) {
	$month += 3;
	if ($month > 12) {
	  $month -= 12;
	  $year += 1;
	}
	$next_begin = mktime(0, 0, 0, $month, 1, $year);
	$increments[] = $next_begin - $last_begin;
	$last_begin = $next_begin;
      }
      break;
    }
    $views = Array();
    for ($i = 0; $i < $duration; $i++) {
      $sql_start_date = date('Y-m-d', $begin);
      $end = $begin + $increments[$i];
      $sql_end_date = date('Y-m-d', $end);

      $new_view = Array('date' => $begin, 'total' => 0);

      // array below has index for each module, bucket
      // and the index 'day' for the day or first day of week/month
      $results = self::getTimeSeries($system, $sql_start_date, NULL, NULL, $sql_end_date);
      foreach ($results as $row) {
	if (array_key_exists('platform', $row)) {
          if (!array_key_exists($row['platform'], $new_view))
            $new_view[$row['platform']] = 0;
	  $new_view[$row['platform']] += $row['viewcount'];
        }
	if (array_key_exists('module', $row)) {
          if (!array_key_exists($row['platform'], $new_view))
            $new_view[$row['platform']] = 0;
	  $new_view[$row['module']] += $row['viewcount'];
        }
	$new_view['total'] += $row['viewcount'];
      }
      $views[] = $new_view;
      $begin = $end;
    }
    return $views;
  }

  public static function count_iphone_tokens() {
    $sql = "SELECT count(*) FROM AppleDevice WHERE device_token IS NOT NULL and active = 1";
    $result = db::$connection->query($sql);
    $row = $result->fetch_assoc();
    return $row;
  }

}

?>
