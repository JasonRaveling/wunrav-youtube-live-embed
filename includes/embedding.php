<?php
/*
Plugin Name: YouTube Live Streaming Auto Embed
Description: Detects when a YouTube account is live streaming and creates an embeded video for the stream using a shortcode. (Supports YouTube APIv3)
Plugin URI: https://github.com/webunraveling/wunrav-youtube-live-streaming-embed
Version: 0.2.1
Author: Jason Raveling
Author URI: https://webunraveling.com
*/

class WunravEmbedYoutubeLiveStreaming
{
    public $pluginSlug;

    public $channelCreds;

    public $jsonResponse; // pure server response
    public $objectResponse; // response decoded as object
    public $arrayRespone; // response decoded as array

    public $isLive; // true if there is a live streaming at the channel

    public $queryData; // query values as an array
    public $getAddress; // address to request GET
    public $getQuery; // data to request, encoded

    public $queryString; // Address + Data to request

    public $part;
    public $eventType;
    public $type;

    public $default_embed_width;
    public $default_embed_height;
    public $default_ratio;

    public $embed_code; // contain the embed code
    public $embed_autoplay;
    public $embed_width;
    public $embed_height;

    public $live_video_id;
    public $live_video_title;
    public $live_video_description;

    public $live_video_publishedAt;

    public $live_video_thumb_high;

    public $channel_title;

    public $options; // options entered into admin form

    public function __construct()
    {
        $this->pluginSlug = 'wunrav-live-youtube-embed';

        $this->part = "id,snippet";
        $this->eventType = "live";
        $this->type = "video";

        $this->getAddress = "https://www.googleapis.com/youtube/v3/search?";

        $this->default_embed_width = "800";
        $this->default_embed_height = "450";
        $this->default_ratio = $this->default_embed_width / $this->default_embed_height;

        $this->embed_width = $this->default_embed_width;
        $this->embed_height = $this->default_embed_height;

        $this->embed_autoplay = true;

        $this->queryIt();

        add_shortcode( 'live-youtube', array($this, 'shortcode') );
        add_action( 'wp_head', array($this, 'alert') );
        add_action( 'admin_menu', array($this, 'admin_menu_init') );
        add_action( 'admin_init', array($this, 'admin_page_init') );
    }

    /**************************************************
     * Setup for Admin Page and Settings
     *************************************************/

    // add a menu item
    public function admin_menu_init()
    {
        add_options_page(
            'YouTube Auto Live Embed Settings',
            'YouTube Auto Live Embed',
            'manage_options',
            $this->pluginSlug,
            array( $this, 'admin_page_create' )
        );
    }

    // create the admin page layout
    public function admin_page_create()
    {  
        $this->options = get_option( $this->pluginSlug . '_settings' );

        echo '<div class="wrap">';
        echo '<h1>YouTube Auto Live Embed</h1>';
        echo '<p>To use this plugin, just place the <code>[live-youtube]</code> shortcode in the page or post you would like your live feed to appear.</p>';
        if ( $this->isTesting() ) {
            echo '<h2 style="color:red;">NOTE: Your testing account is enabled.</h2>';
        }
        echo '<form method="post" action="options.php">';
        settings_fields( $this->pluginSlug . '_settings' );
        do_settings_sections( $this->pluginSlug );
        submit_button();
        echo '</form>';
        echo '<h1>Instructions</h1>';
        echo 'All you need is a channel ID and an API key for your channel to use this plugin. Here is how to get them.';
        echo '<h3>Getting the Channel ID</h3>';
        echo '<ol>';
        echo '<li>Login to <a target="_blank" href="https://youtube.com">YouTube.com</a>.</li>';
        echo '<li>Click on "My Channel".</li><li>Look at the URL. It should say something like <code>https://youtube.com/channel/[someRandomStuff]</code>. Copy the random stuff from the URL and paste it in the "Channel ID" field.</li>';
        echo '</ol>';
        echo '<h3>Getting the YouTube API Key</h3>';
        echo '<ol>';
        echo '<li>Create a project at google developer console (<a target="_blank" href="https://console.developers.google.com">console.developers.google.com</a>).</li>';
        echo '<li>Enable YouTube API for that project.</li>';
        echo '<li>Create a credential of type "Public API Access", a "Server key", with the IP of your website. This will create an API key for you.</li>';
        echo '<li>Copy the API key and paste into the API Key field above.</li>';
        echo '</ol>';
        echo '</div>';
    }

    // generate the admin options
    public function admin_page_init()
    {
        register_setting(
            $this->pluginSlug . '_settings', // option group
            $this->pluginSlug . '_settings', // option name
            array($this, 'sanitize')
        );

        /*****************************************
         * Form fields for verbage / customization
         ****************************************/
        add_settings_section(
            $this->pluginSlug . '-settings-customization', // section ID
            'Customization', // section header name
            array($this, 'printSection_customization'), //callback
            $this->pluginSlug // page
        );

        add_settings_field(
            'alertTitle',
            'Header / Title',
            array($this, 'alertTitle_callback'),
            $this->pluginSlug, // page
            $this->pluginSlug . '-settings-customization' //section
        );

        add_settings_field(
            'alertMessage',
            'Message',
            array($this, 'alertMessage_callback'),
            $this->pluginSlug, //page
            $this->pluginSlug . '-settings-customization' //section
        );

        /*****************************************
         * Form fields for production account
         ****************************************/
        add_settings_section(
            $this->pluginSlug . '-settings-production', // section ID
            'Production Account', // section header name
            array($this, 'printSection_production'), // callback
            $this->pluginSlug // page
        );

        add_settings_field(
            'channelID',
            'Channel ID',
            array($this, 'channelID_callback'),
            $this->pluginSlug, // page
            $this->pluginSlug . '-settings-production' // section
        );

        add_settings_field(
            'apiKey', // ID (in form I think)
            'API Key', // Title
            array($this, 'apiKey_callback'), // callback
            $this->pluginSlug, // page
            $this->pluginSlug . '-settings-production' // section
        );

        /*****************************************
         * Form fields for TESTING account
         ****************************************/
        add_settings_section(
            $this->pluginSlug . '-settings-testing', // section ID
            'Testing Account', // section header name
            array($this, 'printSection_testing'), // callback
            $this->pluginSlug // page
        );

        add_settings_field(
            'testing-toggle',
            'Enable Testing Account',
            array($this, 'testingToggle_callback'), // callback
            $this->pluginSlug,
            $this->pluginSlug . '-settings-testing' // section
        );

        add_settings_field(
            'debugging-toggle',
            'Debugging', // Title
            array($this, 'debuggingToggle_callback'), // callback
            $this->pluginSlug, // page
            $this->pluginSlug . '-settings-testing' // section
        );

        add_settings_field(
            'channelID-testing',
            'Channel ID',
            array($this, 'channelID_testing_callback'),
            $this->pluginSlug, // page
            $this->pluginSlug . '-settings-testing' // section
        );

        add_settings_field(
            'apiKey-testing',
            'API Key', // Title
            array($this, 'apiKey_testing_callback'), // callback
            $this->pluginSlug, // page
            $this->pluginSlug . '-settings-testing' // section
        );
    }

    /****************************************
     * Output sections
     ***************************************/
    public function printSection_customization()
    {
        echo 'Customize the slide out alert that appears when youi\'re live.';
    }

    public function printSection_production()
    {
        // nothing to do here for now
    }

    public function printSection_testing()
    {
        echo '<strong>NOTE:</strong> Use caution with debugging. It will show both your testing and production API keys.';
    }

    // sanitize user input
    public function sanitize( $input )
    {
        $new_input = array();

        if ( isset($input['channelID']) ) {
            $new_input['channelID'] = sanitize_text_field($input['channelID']);
        }

        if ( isset($input['apiKey']) ) {
            $new_input['apiKey'] = sanitize_text_field($input['apiKey']);
        }
        
        if ( isset($input['testing-toggle']) ) {
            $new_input['testing-toggle'] = $input['testing-toggle'];
        }

        if ( isset($input['debugging-toggle']) ) {
            $new_input['debugging-toggle'] = $input['debugging-toggle'];
        }

        if ( isset($input['channelID-testing']) ) {
            $new_input['channelID-testing'] = sanitize_text_field($input['channelID-testing']);
        }

        if ( isset($input['apiKey-testing']) ) {
            $new_input['apiKey-testing'] = sanitize_text_field($input['apiKey-testing']);
        }

        return $new_input;
    }

    /****************************************
     * Callbacks for form fields
     ***************************************/
    public function channelID_callback()
    {
        printf(
            '<input type="text" id="channelID" name="' . $this->pluginSlug . '_settings[channelID]" value="%s" size="55" maxlength="24" />',
            isset( $this->options['channelID'] ) ? esc_attr( $this->options['channelID']) : ''
        );
    }

    public function apiKey_callback()
    {
        printf(
            '<input type="text" id="apiKey" name="' . $this->pluginSlug .'_settings[apiKey]" value="%s" size="55" maxlength="39" />',
            isset( $this->options['apiKey'] ) ? esc_attr( $this->options['apiKey']) : ''
        );
    }

    public function testingToggle_callback()
    {
        printf(
            '<input type="checkbox" id="testing-toggle" name="' . $this->pluginSlug . '_settings[testing-toggle]" %s />',
            checked ( isset($this->options['testing-toggle']), true, false )
        );
    }

    public function debuggingToggle_callback()
    {
        printf(
            '<input type="checkbox" id="debugging-toggle" name="' . $this->pluginSlug . '_settings[debugging-toggle]" %s />',
            checked ( isset($this->options['debugging-toggle']), true, false )
        );

    }

    public function channelID_testing_callback()
    {
        printf(
            '<input type="text" id="channelID-testing" name="' . $this->pluginSlug . '_settings[channelID-testing]" value="%s" size="55" maxlength="24" />',
            isset( $this->options['channelID-testing'] ) ? esc_attr( $this->options['channelID-testing']) : ''
        );
    }

    public function apiKey_testing_callback()
    {
        printf(
            '<input type="text" id="apiKey-testing" name="' . $this->pluginSlug .'_settings[apiKey-testing]" value="%s" size="55" maxlength="39" />',
            isset( $this->options['apiKey-testing'] ) ? esc_attr( $this->options['apiKey-testing']) : ''
        );
    }

    public function alertToggle_callback()
    {
        printf(
            '<input type="checkbox" id="alert-toggle" name="' . $this->pluginSlug . '_settings[alert-toggle]" %s />',
            checked ( isset($this->options['alert-toggle']), true, false )
        );
    }


    /**************************************************
     ************** FRONT END *************************
     **************************************************
     * Begin using the info to output embed code or a
     * default message if no live feed is occuring
     *************************************************/

    public function shortcode()
    {
        if ( $this->isLive() ) {
            echo $this->embedCode();
        } else {
            // this will be user generated soon.
            echo $this->offAirMessage();
        }

        echo $this->debugging();
    }

    public function isTesting()
    {
        $this->options = get_option( $this->pluginSlug . '_settings' );

        if ( isset($this->options['testing-toggle']) && isset($this->options['alert-toggle']) ) {
            return 2;
        } elseif ( isset($this->options['testing-toggle']) ) {
            return 1;
        }
    }

    public function getChannel()
    {
        $this->options = get_option( $this->pluginSlug . '_settings' );

        $out['channelID'] = $this->options[($this->isTesting() ? 'channelID-testing' : 'channelID')]; // make required
        $out['apiKey'] = $this->options[($this->isTesting() ? 'apiKey-testing': 'apiKey')]; // make required

        return $out;
    }

    public function debugging()
    {
        $this->options = get_option( $this->pluginSlug . '_settings' );

        if ( isset($this->options['debugging-toggle']) && $this->isTesting() ) {
            $out =  '--------------------------------------------------<br />';
            $out .= ' DEBUGGING<br />';
            $out .= ' note: slideout is always on when debugging is on<br />';
            $out .= '--------------------------------------------------<br />';
            $out .= '<pre>' . print_r($this->options, true) . '</pre>';
            $out .= '--------------------------------------------------<br />';
            $out .= ' END DEBUGGING<br />';
            $out .= '--------------------------------------------------<br />';
            
            return $out;
        } else {
            return;
        }
    }

    public function offAirMessage()
    {
        /* allow user in put here eventually, using wp_editor().*/
        $out = '<h4>If you don\'t see a video, we aren\'t live quite yet. <strong><a href="javascript:window.location.reload()">Refresh the page</a></strong> in a moment.</h4>';

        return $out;
    }

    public function queryIt()
    {
        $this->queryData = array(
            "part" => $this->part,
            "channelId" => $this->getChannel()['channelID'],
            "eventType" => $this->eventType,
            "type" => $this->type,
            "key" => $this->getChannel()['apiKey'],
        );
        $this->getQuery = http_build_query($this->queryData); // transform array of data in url query
        $this->queryString = $this->getAddress . $this->getQuery;

        $this->jsonResponse = file_get_contents($this->queryString); // pure server response
        $this->objectResponse = json_decode($this->jsonResponse); // decode as object
        $this->arrayResponse = json_decode($this->jsonResponse, TRUE); // decode as array

        $this->isLive();
        if( $this->isLive ) {

            $this->live_video_id = $this->objectResponse->items[0]->id->videoId;

            // Can return many variables but we only need the one above for the embed code
            // $this->live_video_title = $this->objectResponse->items[0]->snippet->title;
            // $this->live_video_description = $this->objectResponse->items[0]->snippet->description;
            // $this->live_video_published_at = $this->objectResponse->items[0]->snippet->publishedAt;
            // $this->live_video_thumb_high = $this->objectResponse->items[0]->snippet->thumbnails->high->url;
            // $this->channel_title = $this->objectResponse->items[0]->snippet->channelTitle;

            $this->embedCode();
        }
    }

    public function isLive( $getOrNot = false )
    {
        if( $getOrNot == true ) {
            $this->queryIt();
        }

        $live_items = count($this->objectResponse->items);

        if( $live_items > 0 ) {
            $this->isLive = true;
            return true;
        } else {
            $this->isLive = false;
            return false;
        }
    }

    public function setEmbedSizeByWidth( $width, $refill_code = true )
    {
        $ratio = $this->default_embed_width / $this->default_embed_height;
        $this->embed_width = $width;
        $this->embed_height = $width / $ratio;

        if( $refill_code == true ) { $this->embedCode(); }
    }

    public function setEmbedSizeByHeight( $height, $refill_code = true )
    {
        $ratio = $this->default_embed_width / $this->default_embed_height;
        $this->embed_height = $height;
        $this->embed_width = $height * $ratio;

        if( $refill_code == true ) { $this->embedCode(); }
    }

    public function embedCode()
    {
        $autoplay = $this->embed_autoplay ? '?autoplay=1' : '';

        $this->embed_code = <<<EOT
<iframe
        width="{$this->embed_width}"
        height="{$this->embed_height}"
        src="//youtube.com/embed/{$this->live_video_id}{$autoplay}"
        frameborder="0"
        allowfullscreen>
</iframe>
EOT;

        return $this->embed_code;
    }

    // create an alert on every page
    public function alert()
    {

        if ( $this->isLive() || $this->isTesting() == 2 ) {
	    /***************************
             * CUSTOM CSS
             **************************/
            $out =  '<style type="text/css">';
            $out .= '.youtube-live-embed-wrapper{width:100%;height:0;position:relative;padding-bottom:56.5%;margin-bottom:1.15rem;}.youtube-live-embed-wrapper iframe{position:absolute;top:0;left:0;width:100%;height:100%;};';
            $out .= '</style>';
    
            /***************************
             * SLIDEOUT
             **************************/
            
            // content variables
            $alertTitle = 'We Are Live';
            $alertVerbage = 'We are streaming live right now!';
            $alertButtonURL = '';
            $alertButtonText = 'Watch Now';
            $alertTabText = 'ON AIR'; // The text that appears on the toggle/tab

            // javascript that creates a cookie to stop the alert from
            // taking focus every time the page is loaded
            $out .= '<script type="text/javascript" src="' . plugins_url('includes/live-feed-cookie.js', __FILE__) . '"></script>';

            // lets do the work
            $out .= '<input type="checkbox" id="slideout-button" name="slideout-button">';
            $out .= '<div class="live-feed-slideout" onload="lptv_slidout_onload()">';
            $out .= '<div class="slideout-content-wrap">';
            $out .= '<div class="slideout-content">';
            $out .= '<h2>' . $alertTitle . '</h2>';
            $out .= '<p>' . $alertVerbage . '</p>';
            $out .= '<a href="' . $alertButtonURL . '"><h4 class="lptv-blue-button-big">' . $alertButtonText . '</h4></a>';
            $out .= '</div>';
            $out .= '</div>';
            $out .= '<label for="slideout-button" id="slideout-trigger" class="slideout-trigger onAir"><img src="/wp-content/themes/worldwide-v1-01/images/arrow-triangle.png" /><br />' . implode( "<br />", str_split($alertTabText) ) . '</label>';
            $out .= '</div>';

            echo $out;

        }

    }

}
