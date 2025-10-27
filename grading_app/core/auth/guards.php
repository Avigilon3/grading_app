<?php
require_once __DIR__ . '/../config/config.php';

function requireLogin(){
  if (session_status() === PHP_SESSION_NONE) { session_start(); }
  if (empty($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/login.php'); // absolute-from-root URL
    exit;
  }
}

function requireAdmin(){ requireLogin(); if(!in_array($_SESSION['user']['role']??'', ['admin','registrar'])){ http_response_code(403); echo "Unauthorized."; exit; } }
function requireProfessor(){ requireLogin(); if(($_SESSION['user']['role']??'')!=='professor'){ http_response_code(403); echo "Unauthorized."; exit; } }
function requireStudent(){ requireLogin(); if(($_SESSION['user']['role']??'')!=='student'){ http_response_code(403); echo "Unauthorized."; exit; } }
