Exam by: [Zeth Raphael T. Arguelles], [Sept. 16, 2023 ], [Sept. 12, 2023 4:00 PM], [Sept. 16, 2023 12:25 AM]

## Description:
This is my output for a technical exam. The requirements for this activity was to create a program that would 
retieve information from Youtube channels of your choosing. The program would store this information in a database
then display them using an html page. The technologies used were [ThingEngineer PHP-MySQLi-Database-Class](https://github.com/ThingEngineer/PHP-MySQLi-Database-Class), a Database management system (in my case I used the one built-in in Xampp), 
and Vue.js.

## Prerequisite:

1. Have a database management system ready in computer you are using

2. Have a Youtube API key ready, if you do not have one here is a link to [get started](https://developers.google.com/youtube/v3/getting-started)

Once you have the API key:

![Line and variable location](https://github.com/zrta2480/zeth-arguelles-exam/blob/master/read_me_images/api_key_variable_location.jpg)

In Line 178 in the syn_youtube_channel.php file, please set your Youtube API key in the $api_key variable.

##Set up

1. Clone the repository to a location where it can be deployed by your local server. In my case, I used Xampp. To
properly deploy pages using Xampp I had to store them in C:\xampp\htdocs.

2. Import the youtube_db.sql file to the database

3. Access the main html page. The link will look something like this: http://localhost/../../show_youtube_channel.html


# Explanation for each page file:
1. sync_youtube_channel.php
This file uses the Youtube API key to retrieve information from a specified Youtube channel based on the inputed channel ID
the used has inputed in show_youtube_channel.html. The information retrieved is the name, description and link to profile picture
of the youtube channel as well as the video link, video ID, description and link to thumbnail of the 100 latest videos of that channel. All of these are then stored in the youtube_db database using methods in the MySQLi database class. 
Everytime a new channel is inputted, the program will insert new records for that channel and everytime an already existing
channel is inputted, each row assoicated with that channel is only updated. The only time new insertion will occur on channels
already existing in the database is when the channel had less than 100 videos last insertion/update.

2. youtube_channel_json.php
Takes the information associated with the channel inputted by user from the database then sends it through a JSON feed

3. show_youtube_channel.html
Gets input from user, the input should be a valid Youtube channel ID. To get the channel ID, go the about section on a
youtube channel then click the share button.
Recieves the information through the JSON provided by the youtube_channel_json.php file, then uses Vue.js to display the 
relevant information

![how to get channel ID](https://github.com/zrta2480/zeth-arguelles-exam/blob/master/read_me_images/how-to-get-channel-id.jpg)
