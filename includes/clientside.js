/*
 * When enabled in WP admin settings, this JS will be used to ping WP cron
 * and then check the JSON pulled from the YouTube API. The API calls are
 * being done serverside and saved to a file called channel.json
 */

var keepListening = setInterval(checkYouTube, 18000);

var siteURL = document.domain;
var pluginURL = siteURL + "/wp-content/plugins/wunrav-youtube-live-embed";

checkYouTube(); // initial check before interval checking

function checkYouTube() {

    jQuery.get('//' + siteURL + '/wp-cron.php');

    setTimeout( function() { // give wp-cron a moment to query the API and write JSON

        jQuery.getJSON('//' + pluginURL + '/channel.json', // JSON created by serverside.php
                function(data) {

                    if ( data.items[0] == null ) {
                        return;
                    } else {
                        var vidURL = 'https://www.youtube.com/embed/' + data.items[0].id.videoId + '?autoplay=1&color=white';

                        jQuery('#wunrav-youtube-embed-slideout').css('display', 'block');
                        jQuery('#wunrav-youtube-embed-iframe').attr('src', vidURL);
                        jQuery('#wunrav-youtube-embed-iframe').css('display', 'inline-block');
                        jQuery('#wunrav-youtube-embed-offair').remove();

                        clearInterval(keepListening);
                    }

                });
    }, 1700);

    return;
}
