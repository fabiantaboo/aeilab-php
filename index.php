<?php
require_once 'includes/bootstrap.php';

// Redirect based on login status
if ($user->isSessionValid()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
?> 