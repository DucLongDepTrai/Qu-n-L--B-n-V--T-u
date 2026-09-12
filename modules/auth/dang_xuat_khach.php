<?php
require_once __DIR__ . '/../../bootstrap.php';
unset($_SESSION['khach_hang']);
header('Location: ' . BASE_URL);
exit();
