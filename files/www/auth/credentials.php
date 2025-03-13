<?php
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    header('Location: /');
    exit;
}
return [
    'username' => 'admin',
    'hashed_password' => '$2y$10$vus0vO2fKBIxW9JqYreCIenXsN843CnnWef20PXgGkn6OGPNjM3Cq',
];
