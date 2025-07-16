<?php
require_once 'includes/bootstrap.php';

// Logout the user
$user->logout();

// Redirect to login page with success message
header('Location: login.php?logout=success');
exit;
?> 