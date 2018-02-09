// This code has been copied from local machine... was used for testing and work stand alone. We need to work it in to the plugin

var keepListening = setInterval(checkYouTube, 10000);

function checkYouTube() {

    // jason raveling channel ID: UC_x5XG1OV2P6uZZ5FSM9Ttw
    // jason channel ID: UCqLNcDDtg_FxIxw4okQBu-A

    // this needs to change to JSON request to our server.... our server should be using the API key with its IP whitelisted
    jQuery.getJSON('https://www.googleapis.com/youtube/v3/search?part=snippet&channelId=UCqLNcDDtg_FxIxw4okQBu-A&eventType=live&maxResults=1&type=video&key=AIzaSyApbZwsiTysEw6lP-5eYNEWQUspWmBmt_I',
            function(data) {

                if ( data.items[0] == null ) {
                    jQuery('#msg').text("When the channel goes live, a video will appear here.");
                    return;
                } else {
                    jQuery('#msg').text("The channel is live. keepListening = " + keepListening);
                    clearInterval(keepListening);
                }

                var vidURL = 'https://www.youtube.com/embed/' + data.items[0].id.videoId + '?autoplay=1&color=white';
                jQuery('#liveYTFeed').attr('src', vidURL);
                jQuery('#json').text(JSON.stringify(data, null, "    "));
            });
}

checkYouTube(); // initial check
