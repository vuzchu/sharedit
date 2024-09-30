<?php

$servername = "localhost";
$username = "root";
$password = "123456";
$dbname = "projects";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
