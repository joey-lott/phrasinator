<?php

use App\DbLog;

function dblog($message, $context = "") {
  $log = new DbLog();
  $log->message = $message;
  $log->context = $context;
  $log->save();
}
