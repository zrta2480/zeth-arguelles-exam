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

function redirectToShowPage() {
    $url = "show_youtube_channel.html";
    echo '<script type="text/javascript">';
        echo 'window.location.href="'.$url.'";';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
        echo '</noscript>'; exit;
}

function backButton() {
    echo "<h4> Something Went Wrong! </h4>";
    echo "<form action='show_youtube_channel.html' method='post'>";
    echo "<input type='submit' name='back_button' value='Go Back' />";
    echo "</form>";
    die();
}

function trimVideoInfoIntoArray($videos_info) {
    if($videos_info->pageInfo->totalResults == 0 || empty($videos_info)) {
        echo "No videos available";
        echo "<br / >";
        backButton();
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
    
    return $video_data;
}

function checkIfChannelIsInDB($db, $channel_id) {
    if(empty($channel_id)) {
        echo "No valid channel ID entered. Please enter a valid channel ID";
        echo "<br / >";
        backButton();
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
        backButton();
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
            backButton();
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
        backButton();
    }
}

function updateVideoInfo($db, $total_data, $channel_id) {
    $db->where("channel_id", $channel_id);
    $ids = $db->getValue("youtube_channel_videos", "id", null);
    
    for($x = 0; $x < sizeof($ids); $x++) {
        $db->where('id', $ids[$x]);
        $check = $db->update('youtube_channel_videos', $total_data[$x]);
        if(!$check) {
            echo "Error in Updating: " . $db->getLastError();
            echo "<br />";
            backButton();
        }
    }

    // Not yet tested
    if(sizeof($ids) < 100) {
        if(sizeof($total_data) > sizeof($ids)) {
            for($i = sizeof($ids); $i < sizeof($total_data); $i++) {
                $check = $db->insert("youtube_channel_videos", $total_data[$i]);
                if(!$check) {
                    echo "Insertion Failed: " . $db->getLastError();
                    echo "<br />";
                    backButton();
                } 
            }
        }
    }
    echo "Updated Video INfo!";
    echo "<br />";
}

//Include db_connect to access database
include "db_connect.php";
//check connection
if(!$db->ping()) {
    backButton();
    die("Connection Failed" . $db->getLastError());
} else {
    echo "Database Connected!";
    echo "<br />";
}

//Start Session
session_start();
//Remove previous channel ID
session_unset();


if(isset($_POST['submit_channel_id'])) {
    $api_key = "AIzaSyBSoxTBm6Kv-1rLiZyCpVre1UCjb-3ak5E"; // SET API KEY HERE!!!
    $base_url = "https://www.googleapis.com/youtube/v3";
    $max_num_result = 50;
    $channel_id = $_POST['channel_id'];
    //Set session to store channel ID:
    $_SESSION['channel_id'] = $channel_id;

    $api_url = $base_url . "/channels?part=snippet&id=" . $channel_id . "&key=" . $api_key; // Form url to call API
    $channel_info = json_decode(file_get_contents($api_url)); //Extract channel information from API

    $v_api_url = $base_url . "/search?order=date&part=snippet&channelId=" . $channel_id . 
    "&maxResults=" . $max_num_result . "&key=" . $api_key; // Form url to call API
    $videos_info = json_decode(file_get_contents($v_api_url)); //Extract videos information from API

    $total_video_data = array(); // Initialize array to store videos information

    //Check if API responds
    if(empty($channel_info)) {
        echo "API not responding";
        echo "<br / >";
        backButton();
        die();
    }

    //Verify if youtube channel exists
    if($channel_info->pageInfo->totalResults == 0) {
        echo "Channel Does not exist";
        echo "<br / >";
        backButton();
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

    unset($_POST['submit_channel_id']);
    unset($_POST['channel_id']);
    redirectToShowPage();
}

?>