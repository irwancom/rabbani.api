<?php
$servername = "165.22.248.132";
$database = "main";
$username = "rabbani";
$password = "bC7ph2tZrRDqsZpc";

// Create connection

$conn = mysqli_connect($servername, $username, $password, $database);

// Check connection

if (!$conn) {

    die("Connection failed: " . mysqli_connect_error());

}
echo "Connected successfully";
mysqli_close($conn);
?>