<?php
require __DIR__ . '/../includes/bootstrap.php';
Auth::logout();
flash('success', 'You have been logged out.');
redirect('login.php');
