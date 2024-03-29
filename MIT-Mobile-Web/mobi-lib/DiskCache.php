<?php

class DiskCache {

  private $path = '/Users/Shared/logs';
  private $timeout = PHP_INT_MAX;
  private $error;
  private $prefix = "";
  private $suffix = "";
  private $serialize = TRUE;

  public function __construct($path, $timeout=NULL, $mkdir=FALSE) {
    $this->path = $path;

    if ($mkdir) {
      if (!file_exists($path)) {
        if (!mkdir($path, 0775)) // want cache files to be group writeable
          error_log("could not create $path");
        chmod($path, 0775); // also setting mode in mkdir doesn't seem to work
        chgrp($path, 'apache');
      }
    }

    if ($timeout !== NULL)
      $this->timeout = $timeout;
  }

  public function preserveFormat() {
    $this->serialize = FALSE;
  }

  public function setTimeout($timeout) {
    $this->timeout = $timeout;
  }

  public function setPrefix($prefix) {
    $this->prefix = $prefix;
  }

  public function getPrefix() {
    return $this->prefix;
  }

  public function getSuffix() {
    return $this->suffix;
  }

  public function setSuffix($suffix) {
    $this->suffix = $suffix;
  }

  public function getError() {
    return $this->error;
  }

  public function getFullPath($filename=NULL) {
    if ($filename === NULL) {
      return $this->path;
    } else {
      // this replaces %20 with + signs
      $filename = urlencode(urldecode($filename));

      return $this->path . '/' 
           . $this->prefix 
           . $filename 
           . $this->suffix;
    }
  }

  public function writeImage($image, $filename) {
    $success = FALSE;
    $path = $this->getFullPath($filename);
    $suffix = $this->suffix ? $this->suffix : substr($filename, -4);
    switch ($suffix) {
     case '.png':
       $success = imagepng($image, $path);
       break;
     case '.jpg':
       $success = imagejpeg($image, $path);
       break;
     case '.gif':
       $success = imagegif($image, $path);
       break;
    }
    return $success;
  }

  public function readImage($filename) {
    $path = $this->getFullPath($filename);
    $suffix = $this->suffix ? $this->suffix : substr($filename, -4);
    switch ($suffix) {
     case '.png':
       $success = imagecreatefrompng($image, $path);
       break;
     case '.jpg':
       $success = imagecreatefromjpeg($image, $path);
       break;
     case '.gif':
       $success = imagecreatefromgif($image, $path);
       break;
    }
  }

  public function write($object, $filename=NULL) {
    if (!$object) {
      $this->error = "tried to cache a non-object";
    }

    $path = $this->getFullPath($filename);
    if (!file_exists($path)) {
      touch($path);
      chmod($path, 0664); // want cache files to be group writeable
      chgrp($path, 'apache');
    }
    $fh = fopen($path, 'w');
    if ($fh !== FALSE) {
      if ($this->serialize) {
        fwrite($fh, serialize($object));
      } else {
        fwrite($fh, $object);
      }
      fclose($fh);
      return TRUE;

    } else {
      $this->error = "could not open $path for writing";
    }

    // stop doing this here after users handle error on their own
    if ($this->error)
      error_log($this->error);

    return FALSE;
  }

  public function read($filename=NULL) {
    $path = $this->getFullPath($filename);
    if (file_exists($path) && $this->isFresh($filename)) {
      if ($contents = file_get_contents($path)) {
        if ($this->serialize) {
          return unserialize($contents);
        } else {
          return $contents;
        }
      }
      $this->error = "could not get contents of $path";
      error_log($this->error, 0);
    }
    return FALSE;
  }

  public function exists($filename) {
    $path = $this->getFullPath($filename);
    return (file_exists($path) && filesize($path) > 0);
  }

  public function isFresh($filename=NULL, $timeout=NULL) {
    $path = $this->getFullPath($filename);
    if ($timeout === NULL)
      $timeout = $this->timeout;
    return ($this->getAge($filename) < $timeout);
  }

  public function getAge($filename=NULL) {
    if ($this->exists($filename)) {
      $path = $this->getFullPath($filename);
      return time() - filemtime($path);
    }
    return PHP_INT_MAX;
  }

}





