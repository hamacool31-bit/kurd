<?php
session_start();
$conn = new mysqli("localhost", "root", "", "login");
if ($conn->connect_error)
    die("DB Error: " . $conn->connect_error);
$conn->set_charset("utf8mb4");
if (!file_exists('uploads'))
    mkdir('uploads', 0777, true);

function isAdmin()
{
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}
