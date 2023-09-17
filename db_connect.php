<?php
// Configure parameters here to access Database

//import required class
require_once('MysqliDb.php');
//connect to database
$db = new MysqliDb('localhost', 'root', '', 'youtube_db');

?>