<?php

/*
 * TimeRange: class describing a time interval.
 * DayRange: child class of TimeRange describing a full day
 */
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/MIT-Mobile-Web/mobi-config/mobi_web_constants.php";
require_once(LIBDIR . 'datetime_lib.php');

class TimeRange {

  protected $start;
  protected $end;

  protected static $precedence = Array(
    's', //second
    'i', //minute
    'G', 'g', 'H', 'h', //hour
    'A', 'a', //half day
    'D', 'l', 'N', 'w', //weekday
    'd', 'j', 'S', //monthday
    'z', //yearday
    'W', //week
    'F', 'M', 'm', 'n', 't', //month
    'Y', 'y', //year
    );

  public function get_start() {
    return $this->start;
  }

  public function set_start($start) {
    $this->start = $start;
  }

  public function get_end() {
    return $this->end;
  }

  public function set_end($end) {
    $this->end = $end;
  }

  public function format($format, $compress=TRUE) {
    if ($this->start == $this->end) {
      return date($format, $this->start);
    } else {
      $token_types = Array();
      // the letters in this regex are the parameters for date()
      preg_match_all('/([AaDdFGgHhijlMmNnoSstWwYyz])/', $format, $matches);
      foreach ($matches[1] as $match) {
	$prec = array_search($match, self::$precedence);
	$token_types[$prec] = $match;
      }
      krsort($token_types);

      $left = Array();  // string to show left of hyphen
      $right = Array(); // string to show right of hyphen

      if ($compress) {
	// iterate through tokens in decreasing grain size
	// until a tokens are different for start and end date
	while (list($prec, $token_type) = each ($token_types)) {
	  $start = date($token_type, $this->start);
	  $end = date($token_type, $this->end);
	  $left[strpos($format, $token_type)] = $start;
	  if ($start != $end) {
	    $right[strpos($format, $token_type)] = $end;
	    break;
	  }
	}
      }

      // iterate through the rest of the array
      while (list($prec, $token_type) = each ($token_types)) {
	$start = date($token_type, $this->start);
	$end = date($token_type, $this->end);
	$left[strpos($format, $token_type)] = $start;
	$right[strpos($format, $token_type)] = $end;
      }

      $formatted = '';
      $buffer = '';
      for ($i = 0; $i < strlen($format); $i++) {
	if (array_key_exists($i, $left)) {
	  if (array_key_exists($i, $right)) {
	    $buffer .= $right[$i];
	  } elseif ($buffer != '') {
	    $formatted = rtrim($formatted, ' ,') . '-' . $buffer;
	    //$formatted .= '-' . $buffer;
	    $buffer = '';
	  }
	  $formatted .= $left[$i];
	} else {
	  $formatted .= substr($format, $i, 1);
	  if ($buffer != '') {
	    $buffer .= substr($format, $i, 1);
	  }
	}
      }
      if ($buffer != '') {
	$formatted = rtrim($formatted, ' ,') . '-' . $buffer;
	//$formatted .= '-' . $buffer;
      }

      return $formatted;
    }
  }

  public function overlaps(TimeRange $range) {
    if ($range->get_start() >= $this->end) return FALSE;
    elseif ($range->get_end() <= $this->start) return FALSE;
    else return TRUE;
  }

  public function contains(TimeRange $range) {
    if (!$this->overlaps($range)) return FALSE;
    elseif ($range->get_start() < $this->start) return FALSE;
    elseif ($range->get_end() > $this->end) return FALSE;
    else return TRUE;
  }

  public function contained_by(TimeRange $range) {
    if (!$this->overlaps($range)) return FALSE;
    elseif ($range->get_start() > $this->start) return FALSE;
    elseif ($range->get_end() < $this->end) return FALSE;
    else return TRUE;
  }

  public function contains_point($time) {
    return ($this->start <= $time && $this->end > $time);
  }

  public function __construct($start, $end=NULL) {
    if ($end === NULL) { // instantaneous event
      $end = $start;
    } elseif ($start > $end) {
      throw new Exception('argument 1 (start time) must be <= argument 2 (end time)');
    }

    $this->start = $start;
    $this->end = $end;
  }
}

class DayRange extends TimeRange {
  public function __construct($time, $tzid=NULL) {
    $this->start = day_of($time, $tzid);
    // define the end of the day by 11:59:59pm
    $this->end = increment_day($this->start) - 1;
  }
}

?>