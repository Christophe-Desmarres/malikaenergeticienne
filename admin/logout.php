<?php
require_once 'config.php';
auth_start();
session_destroy();
header('Location: login.php');
exit;
