<?php
require_once __DIR__ . '/../config/config.php';
session_start();
unset($_SESSION['user_id']);
unset($_SESSION['username']);
header('Location: login.php');
exit;
