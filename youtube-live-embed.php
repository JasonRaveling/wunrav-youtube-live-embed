<?php
/*
Plugin Name: YouTube Live Streaming Auto Embed
Description: Detects when a YouTube account is live streaming and creates an embeded video for the stream using a shortcode. (Supports YouTube APIv3)
Plugin URI: https://github.com/webunraveling/wunrav-youtube-live-streaming-embed
Version: 1.1.2
Author: Jason Raveling
Author URI: https://webunraveling.com
*/

// Handles admin and functionality of embedding the video
include_once( plugin_dir_path(__FILE__) . '/includes/serverside.php' );

// its alive!
$WunravLiveYoutube = new WunravEmbedYoutubeLiveStreaming();
