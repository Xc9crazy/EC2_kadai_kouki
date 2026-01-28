<?php
require_once __DIR__ . '/includes/common.php';

// Logout user
logout();

// Redirect to login page
redirect('/login.php');
