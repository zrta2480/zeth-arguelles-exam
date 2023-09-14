<?php
//import required class
require_once('MysqliDb.php');
//connect to database
$db = new MysqliDb('localhost', 'root', '', 'youtube_db');
//check connection
if(!$db->ping()) {
    die("Connection Failed" . $db->getLastError());
} 

//Initialize Array to contain all information
$all_data = array(
    'channel_info' => [],
    'videos_info' => []
);

$channel_id = "UCxnUFZ_e7aJFw3Tm8mA7pvQ";

// Retrieve Channel Information
$cols = array("profile_picture_url", "name", "description");
$db->where("id", $channel_id);
$channel_info = $db->get("youtube_channels", null, $cols);
//print_r($channel_info);

//Retrieve Video Information:
$v_cols = array("video_link", "title", "description", "thumbnail_url");
$db->where("channel_id", $channel_id);
$videos_info = $db->get("youtube_channel_videos", null ,$v_cols);

$all_data['channel_info'] = $channel_info;
$all_data['videos_info'] = $videos_info;


header('Content-Type: application/json');


//echo json_encode($channel_info);
// echo json_encode($videos_info);
echo json_encode($all_data);
// echo "<pre>";
// print_r($videos_info);
// echo "</pre>";
exit;
?>