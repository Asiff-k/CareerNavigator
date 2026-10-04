<?php
require_once 'includes/init.php';

// Clear everything in the session and switch to a new session ID
$_SESSION = [];
session_regenerate_id(true);

flash('success', 'You have been signed out.');
redirect('login.php');
