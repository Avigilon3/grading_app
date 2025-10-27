<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
function set_flash($k,$v){ $_SESSION['flash'][$k]=$v; }
function get_flash($k){ if(isset($_SESSION['flash'][$k])){ $v=$_SESSION['flash'][$k]; unset($_SESSION['flash'][$k]); return $v; } return null; }
