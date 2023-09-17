<?php
//Include db_connect to access database
include "db_connect.php";
//Initialize Array to contain all information
$all_data = array(
    'channel_info' => [],
    'videos_info' => [],
    'error'=>''
);
//check connection
if(!$db->ping()) {
    $all_data['error'] = "Connection Failed" . $db->getLastError();
} 

//Initialize session to access channel_id variable
session_start();



if(isset($_SESSION['channel_id'])) {
    // $channel_id = "UCxnUFZ_e7aJFw3Tm8mA7pvQ";
    $channel_id = $_SESSION['channel_id'];

    // Retrieve Channel Information
    $cols = array("profile_picture_url", "name", "description");
    $db->where("id", $channel_id);
    $channel_info = $db->get("youtube_channels", null, $cols);
    
    //Retrieve Video Information:
    $v_cols = array("video_link", "title", "description", "thumbnail_url");
    $db->where("channel_id", $channel_id);
    $videos_info = $db->get("youtube_channel_videos", null ,$v_cols);
    
    // Store retrieved information to main array
    $all_data['channel_info'] = $channel_info;
    $all_data['videos_info'] = $videos_info;
    
    if(empty($all_data['$channel_info'])) {
        $all_data['error'] = "Channel Does not exist";
    }
}

// Clear session and its data
session_unset();
session_destroy();


header('Content-Type: application/json');

// Encode array into json then return 
echo json_encode($all_data);

exit;
?>