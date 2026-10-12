<?php
// dbcon.php - WALANG session_start() dito!

$host = "sql104.infinityfree.com";
$username = "if0_41997184";
$password = "Xianne08";
$database = "if0_41997184_issang_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>