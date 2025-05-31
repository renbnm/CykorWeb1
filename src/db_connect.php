<?php
$host = 'db';
$user = 'root';
$pass = 'root';
$db = 'user_info';

$connect = mysqli_connect($host, $user, $pass, $db);

if (!$connect){
    echo("Connection failed!");
}

?>