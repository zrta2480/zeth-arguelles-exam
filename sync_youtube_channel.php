<!DOCTYPE html>
<html>
    <head>

    </head>
    <body>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            Youtube Channel ID: <input type="text" name="channel_id" />
            <br />
            <input type="submit" name="submit_channel_id" value="Save" />
        </form>
    </body>
</html>

<?php
//Initialize functions to be used:
function trimVideoInfoIntoArray($videos_info) {
    if($videos_info->pageInfo->totalResults == 0 || empty($videos_info)) {
        echo "No videos available";
        echo "<br / >";
        return array();
    }

    $v_link = "https://www.youtube.com/watch?v=";
    $video_data = Array();
    foreach($videos_info->items as $video) {
        if(isset($video->id->videoId)) {
            $temp = $video->id->videoId;
        }
        $temp_arr = Array(
            "video_link" => $v_link . $temp,
            "title" => $video->snippet->title,
            "description" => $video->snippet->description,
            "thumbnail_url"=> $video->snippet->thumbnails->high->url,
            "channel_id" => $video->snippet->channelId
        );
        //$video_data[$key] = array_merge($video_data[$key], $temp_arr);
        array_push($video_data, $temp_arr);
    }
    //echo var_dump($video_data);
    return $video_data;
}

function checkIfChannelIsInDB($db, $channel_id) {
    if(empty($channel_id)) {
        echo "Please enter a valid channel ID";
        echo "<br / >";
        die();
    }

    $db->where("id", $channel_id);
    return $db->getOne("youtube_channels");
}


function insertNewChannelIntoDB($db, $channel_info) {
    $channel_data = Array(
        "id" => $channel_info->items[0]->id,
        "profile_picture_url" => $channel_info->items[0]->snippet->thumbnails->high->url,
        "name" => $channel_info->items[0]->snippet->title,
        "description" => $channel_info->items[0]->snippet->description
    );

    $check = $db->insert("youtube_channels", $channel_data);
    if($check) {
        echo "New Channel Added";
        echo "<br / >";
    } else {
        echo "Insertion Failed: " . $db->getLastError();
        echo "<br />";

    }
}

function insertVideosInfo($db, $total_video_data) {
    if(!empty($total_video_data)) {
        $check = $db->insertMulti("youtube_channel_videos", $total_video_data);
        if($check) {
            echo "New Videos Added";
            echo "<br / >";
        } else {
            echo "Insertion Failed: " . $db->getLastError();
            echo "<br />";
    
        }
    }
}

function updateChannelInfo($db, $channel_info, $channel_id) {
    $channel_data = Array(
        "profile_picture_url" => $channel_info->items[0]->snippet->thumbnails->high->url,
        "name" => $channel_info->items[0]->snippet->title,
        "description" => $channel_info->items[0]->snippet->description
    );
    $db->where("id", $channel_id);
    if($db->update('youtube_channels', $channel_data)) {
        echo "Updated Success!";
        echo "<br />";
    } else {
        echo "Error in Updating: " . $db->getLastError();
        echo "<br />";
    }
}

function updateVideoInfo($db, $total_data, $channel_id) {
    $db->where("channel_id", $channel_id);
    $ids = $db->getValue("youtube_channel_videos", "id", null);
    echo var_dump($ids);
    echo var_dump($total_data);
    for($x = 0; $x < sizeof($ids); $x++) {
        $db->where('id', $ids[$x]);
        $check = $db->update('youtube_channel_videos', $total_data[$x]);
        if(!$check) {
            echo "Error in Updating: " . $db->getLastError();
            echo "<br />";
        }
    }
    if(sizeof($total_data) > sizeof($ids)) {
        for($i = sizeof($ids); sizeof($ids) < sizeof($total_data); $i++) {
            $db->where('id', $ids[$i]);
            $check = $db->update('youtube_channel_videos', $total_data[$i]);
        }
    }
    echo "Updated Video INfo!";
    echo "<br />";
}

//import required class
require_once('MysqliDb.php');
//connect to database
$db = new MysqliDb('localhost', 'root', '', 'youtube_db');
//check connection
if(!$db->ping()) {
    die("Connection Failed" . $db->getLastError());
} else {
    echo "Database Connected!";
    echo "<br />";
}


if(isset($_POST['submit_channel_id'])) {
    $api_key = "AIzaSyBSoxTBm6Kv-1rLiZyCpVre1UCjb-3ak5E";
    $base_url = "https://www.googleapis.com/youtube/v3";
    $max_num_result = 50;
    // $channel_id = "UCxnUFZ_e7aJFw3Tm8mA7pvQ";
    $channel_id = $_POST['channel_id'];

    $api_url = $base_url . "/channels?part=snippet&id=" . $channel_id . "&key=" . $api_key;
    //echo $api_url;
    $channel_info = json_decode(file_get_contents($api_url));

    $v_api_url = $base_url . "/search?order=date&part=snippet&channelId=" . $channel_id . 
    "&maxResults=" . $max_num_result . "&key=" . $api_key;
    // echo $v_api_url;
    // echo "<br />";
    $videos_info = json_decode(file_get_contents($v_api_url));
    // $page2_token = $videos_info->nextPageToken;
    // $page2_videos = $base_url . "/search?pageToken=". $page2_token ."&order=date&part=snippet&channelId=" . $channel_id . 
    // "&maxResults=" . $max_num_result . "&key=" . $api_key;;
    // $page2_videos_info = json_decode(file_get_contents($page2_videos));

    $total_video_data = array();

    //Check if API responds
    if(empty($channel_info)) {
        echo "API not responding";
        echo "<br / >";
        die();
    }

    //Verify if youtube channel exists
    if($channel_info->pageInfo->totalResults == 0) {
        echo "Channel Does not exist";
        echo "<br / >";
        die();
    }

   
    //Checks if the channels contains any videos and if it less than 50
    if($videos_info->pageInfo->totalResults > 50) {
        $page2_token = $videos_info->nextPageToken;
        $page2_videos = $base_url . "/search?pageToken=". $page2_token ."&order=date&part=snippet&channelId=" . $channel_id . 
        "&maxResults=" . $max_num_result . "&key=" . $api_key;;
        $page2_videos_info = json_decode(file_get_contents($page2_videos));

        $page1_info = trimVideoInfoIntoArray($videos_info);
        $page2_info = trimVideoInfoIntoArray($page2_videos_info);
        $total_video_data = array_merge($page1_info, $page2_info);
    } elseif($videos_info->pageInfo->totalResults == 0) {
        $total_video_data = array();
    } 
    else {
        $page1_info = trimVideoInfoIntoArray($videos_info);
        $total_video_data = $page1_info;
    }

    
    //insertVideosInfo($db, $videos_info);
    
    if(checkIfChannelIsInDB($db, $channel_id)) {
        echo "Channel is in DB";
        echo "<br / >";
        updateChannelInfo($db, $channel_info, $channel_id);
        updateVideoInfo($db, $total_video_data, $channel_id);
    } else {
        echo "Channel not yet saved";
        echo "<br / >";
        insertNewChannelIntoDB($db, $channel_info);
        insertVideosInfo($db, $total_video_data);
    }
    // echo "<pre>";
    // print_r($channel_info);  
    // echo "</pre>";

    // echo "Channel Info: <br />";
    // echo "Channel ID: " . $channel_info->items[0]->id;
    // echo "<br />";
    // echo "Channel Title: " . $channel_info->items[0]->snippet->title;
    // echo "<br />";
    // echo "Channel Description: " . $channel_info->items[0]->snippet->description;
    // echo "<br />";


    

    foreach($videos_info->items as $video) {
        echo "Video Title: " . $video->snippet->title;
        echo "<br />";
    }

    unset($_POST['submit_channel_id']);
    unset($_POST['channel_id']);
}

?>