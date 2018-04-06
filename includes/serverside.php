<?php
/*
Plugin Name: YouTube Live Streaming Auto Embed
Description: Detects when a YouTube account is live streaming and creates an embeded video for the stream using a shortcode. (Supports YouTube APIv3)
Plugin URI: https://github.com/webunraveling/wunrav-youtube-live-streaming-embed
Version: 1.0.0
Author: Jason Raveling
Author URI: https://webunraveling.com
*/

class WunravEmbedYoutubeLiveStreaming
{

    public $pluginSlug = 'wunrav-youtube-live-embed';

    public $jsonResponse; // pure server response
    public $objectResponse; // response decoded as object

    public $getAddress =  "https://www.googleapis.com/youtube/v3/search?"; // address to request GET
    public $queryData; // query values as an array
    public $getQuery; // data to request, encoded

    public $queryString; // Address + Data to request

    // args for API query
    public $part = 'id,snippet';
    public $eventType = 'live';
    public $type = 'video';

    public $embed_width = 800;
    public $embed_height = 450;
    public $live_video_id;

    public $options; // options entered into wp-admin form

    public function __construct()
    {
        register_deactivation_hook( __FILE__, array($this, 'deactivate') );

        add_shortcode( 'youtube-live', array($this, 'shortcode') );
        add_action( 'wp_head', array($this, 'loadScripts') );
        add_action( 'wp_head', array($this, 'alert') );
        add_action( 'admin_menu', array($this, 'admin_menu_init') );
        add_action( 'admin_init', array($this, 'admin_page_init') );
        add_filter( 'cron_schedules', array($this, 'addWPCronSchedule') );

        $this->queryIt();
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
        echo '<p>To use this plugin, just place the <code>[youtube-live]</code> shortcode in the page or post you would like your live feed to appear. Instructions on <a href="https://github.com/webunraveling/wunrav-youtube-live-embed">how to setup this plugin</a> are available on GitHub.</p>';

        // notify user when testing is enabled
        if ( $this->isTesting() ) {
            echo '<h2 style="color:red;">NOTE: Your testing account '  . ( $this->isTesting() == 2 ? 'and debugging are both' : 'is' ) . ' enabled.</h2>';
        }
        echo '<form method="post" action="options.php">';
        settings_fields( $this->pluginSlug . '_settings' );
        do_settings_sections( $this->pluginSlug );
        submit_button();
        echo '</form>';
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
            'Slideout / Notification', // section header name
            array($this, 'printSection_customization'), //callback
            $this->pluginSlug // page
        );

        add_settings_field(
            'alertTitle',
            'Header / Title <span class="required">*</span>',
            array($this, 'alertTitle_callback'),
            $this->pluginSlug, // page
            $this->pluginSlug . '-settings-customization' //section
        );

        add_settings_field(
            'alertMsg',
            'Message',
            array($this, 'alertMsg_callback'),
            $this->pluginSlug, //page
            $this->pluginSlug . '-settings-customization' //section
        );

        add_settings_field(
            'alertBtn',
            'Button Text <span class="required">*</span>',
            array($this, 'alertBtn_callback'),
            $this->pluginSlug, //page
            $this->pluginSlug . '-settings-customization' //section
        );

        add_settings_field(
            'alertBtnURL',
            'Button URL <span class="required">*</span>',
            array($this, 'alertBtnURL_callback'),
            $this->pluginSlug, //page
            $this->pluginSlug . '-settings-customization' //section
        );


        /*****************************************
         * Form fields for JavaScript options
         ****************************************/
        add_settings_section(
            $this->pluginSlug . '-settings-jsOptions', // section ID
            'Automatic Updating', // section header name
            array($this, 'printSection_jsOptions'), // callback
            $this->pluginSlug // page
        );

        add_settings_field(
            'useJS',
            'Auto Load Alert & Video Embed',
            array($this, 'useJS_callback'),
            $this->pluginSlug, // page
            $this->pluginSlug . '-settings-jsOptions' // section
        );

        add_settings_field(
            'loadjQuery',
            'Load jQuery',
            array($this, 'loadjQuery_callback'),
            $this->pluginSlug, // page
            $this->pluginSlug . '-settings-jsOptions' // section
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
            'Channel ID <span class="required">*</span>',
            array($this, 'channelID_callback'),
            $this->pluginSlug, // page
            $this->pluginSlug . '-settings-production' // section
        );

        add_settings_field(
            'apiKey', // ID (in form I think)
            'API Key <span class="required">*</span>',
            array($this, 'apiKey_callback'),
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
            'Testing Account',
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
        echo 'Customize your on-air notification here.';
    }

    public function printSection_production()
    {
        // nothing to do here for now
    }

    public function printSection_jsOptions()
    {
        echo 'This option enables your site to automatically display the alert without the page being reloaded.';
    }

    public function printSection_testing()
    {
        echo '<strong>NOTE:</strong> Use caution with debugging. It will show both your testing and production API keys.';
    }

    // sanitize user input
    public function sanitize( $input )
    {
        $new_input = array();

        if ( isset($input['alertTitle']) ) {
            $new_input['alertTitle'] = sanitize_text_field($input['alertTitle']);
        }

        if ( isset($input['alertMsg']) ) {
            $new_input['alertMsg'] = sanitize_text_field($input['alertMsg']);
        }

        if ( isset($input['alertBtn']) ) {
            $new_input['alertBtn'] = sanitize_text_field($input['alertBtn']);
        }

        if ( isset($input['alertBtnURL']) ) {
            $new_input['alertBtnURL'] = esc_url_raw($input['alertBtnURL'], array('http','https'));
        }

        if ( isset($input['useJS']) ) {
            $new_input['useJS'] = filter_var($input['useJS'], FILTER_VALIDATE_BOOLEAN);
        }

        if ( isset($input['loadjQuery']) ) {
            $new_input['loadjQuery'] = filter_var($input['loadjQuery'], FILTER_VALIDATE_BOOLEAN);
        }

        if ( isset($input['channelID']) ) {
            $new_input['channelID'] = sanitize_text_field($input['channelID']);
        }

        if ( isset($input['apiKey']) ) {
            $new_input['apiKey'] = sanitize_text_field($input['apiKey']);
        }
        
        if ( isset($input['testing-toggle']) ) {
            $new_input['testing-toggle'] = filter_var($input['testing-toggle'], FILTER_VALIDATE_BOOLEAN);
        }

        if ( isset($input['debugging-toggle']) ) {
            $new_input['debugging-toggle'] = filter_var($input['debugging-toggle'], FILTER_VALIDATE_BOOLEAN);
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
    public function alertTitle_callback()
    {
        printf(
            '<input type="text" id="alertTitle" name="' . $this->pluginSlug . '_settings[alertTitle]" value="%s" size="60" maxlength="500" required />',
            isset( $this->options['alertTitle'] ) ? esc_attr( $this->options['alertTitle']) : ''
        );
    }

    public function alertMsg_callback()
    {
        printf(
            '<textarea id="alertMsg" name="' . $this->pluginSlug . '_settings[alertMsg]" cols="65" rows="3" maxlength="800">%s</textarea>',
            isset( $this->options['alertMsg'] ) ? esc_attr( $this->options['alertMsg']) : ''
        );
    }

    public function alertBtn_callback()
    {
        printf(
            '<input type="text" id="alertBtn" name="' . $this->pluginSlug . '_settings[alertBtn]" value="%s" size="60" maxlength="500" required />',
            isset( $this->options['alertBtn'] ) ? esc_attr( $this->options['alertBtn']) : ''
        );
    }

    public function alertBtnURL_callback()
    {
        printf(
            '<input type="text" id="alertBtnURL" name="' . $this->pluginSlug . '_settings[alertBtnURL]" value="%s" size="60" maxlength="800" placeholder="must start with http:// or https://" required />',
            isset( $this->options['alertBtnURL'] ) ? esc_attr( $this->options['alertBtnURL']) : ''
        );
    }

    public function useJS_callback()
    {
        printf(
            '<input type="checkbox" id="useJS" name="' . $this->pluginSlug . '_settings[useJS]" %s />Enables auto loading.',
            checked ( isset($this->options['useJS']), true, false )
        );
    }

    public function loadjQuery_callback()
    {
        printf(
            '<input type="checkbox" id="loadjQuery" name="' . $this->pluginSlug . '_settings[loadjQuery]" %s />Enable if your site does not already use jQuery.',
            checked ( isset($this->options['loadjQuery']), true, false )
        );
    }

    public function channelID_callback()
    {
        printf(
            '<input type="text" id="channelID" name="' . $this->pluginSlug . '_settings[channelID]" value="%s" size="60" maxlength="24" required />',
            isset( $this->options['channelID'] ) ? esc_attr( $this->options['channelID']) : ''
        );
    }

    public function apiKey_callback()
    {
        printf(
            '<input type="text" id="apiKey" name="' . $this->pluginSlug .'_settings[apiKey]" value="%s" size="60" maxlength="39" required />',
            isset( $this->options['apiKey'] ) ? esc_attr( $this->options['apiKey']) : ''
        );
    }

    public function testingToggle_callback()
    {
        printf(
            '<input type="checkbox" id="testing-toggle" name="' . $this->pluginSlug . '_settings[testing-toggle]" %s />Enable to use your testing account.',
            checked ( isset($this->options['testing-toggle']), true, false )
        );
    }

    public function debuggingToggle_callback()
    {
        printf(
            '<input type="checkbox" id="debugging-toggle" name="' . $this->pluginSlug . '_settings[debugging-toggle]" %s />Enables debugging where shortcode is used.',
            checked ( isset($this->options['debugging-toggle']), true, false )
        );
    }

    public function channelID_testing_callback()
    {
        printf(
            '<input type="text" id="channelID-testing" name="' . $this->pluginSlug . '_settings[channelID-testing]" value="%s" size="60" maxlength="24" />',
            isset( $this->options['channelID-testing'] ) ? esc_attr( $this->options['channelID-testing']) : ''
        );
    }

    public function apiKey_testing_callback()
    {
        printf(
            '<input type="text" id="apiKey-testing" name="' . $this->pluginSlug .'_settings[apiKey-testing]" value="%s" size="60" maxlength="39" />',
            isset( $this->options['apiKey-testing'] ) ? esc_attr( $this->options['apiKey-testing']) : ''
        );
    }


    /**************************************************
     ************** FRONT END *************************
     *************************************************/

    public function shortcode()
    {
        if ( $this->isLive() || $this->useJS() ) {
            printf('<div class="wunrav-youtube-embed-wrapper"><iframe id="wunrav-youtube-embed-iframe" width="%s" height="%s" %s src="%s" allowfullscreen></iframe></div>',
                $this->embed_width, // iframe width
                $this->embed_height, // iframe height
                ($this->useJS() ? ' style="display:none;"' : ''), // hide it since JS will reveal when live
                ($this->useJS() ? '' : '//youtube.com/embed/' . $this->live_video_id . '?autoplay=1&color=white')
            );
        }

        if ( ! $this->isLive() ) {
            // off air message... one day this will be in WP admin
            echo '<div id="wunrav-youtube-embed-offair">';
            echo '<h3>We aren\'t live quite yet.</h3>';
            if ( $this->useJS() ) {
                echo '<h4>As soon as we start, our live stream will appear.</h4>';
            } else {
                echo '<h4>If you\'re expecting us to start soon, <strong><a href="javascript:window.location.reload()">refresh the page</a></strong> in a moment.</h4>';
            }
            echo '</div>';
        }

        echo $this->debugging();
    }

    public function isTesting()
    {
        if ( isset($this->options['testing-toggle']) && isset($this->options['debugging-toggle']) ) {
            return 2;
        } elseif ( isset($this->options['testing-toggle']) ) {
            return 1;
        } else {
            return 0;
        }
    }

    public function useJS()
    {
        if ( isset($this->options['useJS']) ) {
            return true;
        } else {
            return false;
        }
    }

    public function loadjQuery()
    {
        if ( isset($this->options['loadjQuery']) ) {
            return true;
        } else {
            return false;
        }
    }

    public function getChannel()
    {
        $this->options = get_option( $this->pluginSlug . '_settings' );

        if ( $this->isTesting() ) {
            $out['channelID'] = $this->options['channelID-testing'];
            $out['apiKey'] = $this->options['apiKey-testing'];
        } else {
            $out['channelID'] = $this->options['channelID'];
            $out['apiKey'] = $this->options['apiKey'];
        }

        return $out;
    }

    public function debugging()
    {
        if ( $this->isTesting() == 2 ) {
            $out = '<strong><br />';
            $out .= '##################################################<br />';
            $out .= ' DEBUGGING<br />';
            $out .= '##################################################<br /></strong>';
            $out .= '<pre>' . print_r($this->options, true) . '</pre>';
            $out .= '<br /><strong>YouTube Retured JSON Below</strong><br />';
            $out .= ( $this->jsonResponse ? '<pre>' . $this->jsonResponse . '</pre>' : '' );
            $out .= '<strong>';
            $out .= '##################################################<br />';
            $out .= ' END DEBUGGING<br />';
            $out .= '##################################################<br /></strong>';
            
            return $out;
        } else {
            return;
        }
    }

    public function queryIt()
    {

        // check that we have required fields before trying to query the API
        if ( ! isset($this->options['alertTitle']) ||
             ! isset($this->options['alertBtn']) ||
             ! isset($this->options['alertBtnURL']) ||
             ! isset($this->options['channelID']) ||
             ! isset($this->options['apiKey']) ){

                 $this->errorNotice('Please fill in the required fields for the YouTube Live Auto Embed plugin.');

        }

        $this->queryData = array(
            "part" => $this->part,
            "channelId" => $this->getChannel()['channelID'],
            "key" => $this->getChannel()['apiKey'],
            "eventType" => $this->eventType,
            "type" => $this->type,
            "maxResults" => 1,
        );
        $this->getQuery = http_build_query($this->queryData); // transform array of data in url query
        $this->queryString = $this->getAddress . $this->getQuery;

        // querying the YouTube API with either PHP (on page) load or JS (using WP cron)
        if ( ! $this->useJS() ) {
            $this->jsonResponse = file_get_contents($this->queryString); // pure server response
            $this->objectResponse = json_decode($this->jsonResponse); // decode as object
            $this->live_video_id = ( isset($this->objectResponse->items[0]->id->videoId) ? $this->objectResponse->items[0]->id->videoId : '' );
        } else {
            if ( ! wp_next_scheduled( 'wunrav-youtube-hook' ) ) {
                wp_schedule_event( time(), 'wunrav-30seconds', 'wunrav-youtube-hook' );
            }

            add_action( 'wunrav-youtube-hook', array($this, 'doWPCron') );
        }
    }

    public function isLive()
    {
        if( isset($this->objectResponse->items) ) {
            return true;
        } else {
            return false;
        }
    }

    public function addWPCronSchedule()
    {
        $schedules['wunrav-30seconds'] = array(
            'interval' => 30,
            'display' => __('Every 30 seconds'),
        );

        return $schedules;
    }

    public function doWPCron()
    {
        $this->jsonResponse = file_get_contents($this->queryString); // pure server response

        // Write JSON from the YouTube API to a file for our JS to access
        file_put_contents(dirname(__FILE__, 2) . '/channel.json', $this->jsonResponse);
    }

    public function alert()
    {
        global $post;
        $postContent = ( isset($post->post_content) ? $post->content : false );

        if ( ! has_shortcode($postContent, 'youtube-live') ) {
            $out  = '<input type="checkbox" id="slideout-button" name="slideout-button">';
            $out .= '<div class="live-feed-slideout" id="wunrav-youtube-embed-slideout" onload="lptv_slidout_onload()"' . ( $this->isLive() ? '' : ' style="display:none;"' ) . '>';
            $out .= '<div class="slideout-content-wrap">';
            $out .= '<div class="slideout-content">';
            $out .= '<h2>' . $this->options['alertTitle'] . '</h2>';
            $out .= '<p>' . $this->options['alertMsg'] . '</p>';
            $out .= '<a href="' . $this->options['alertBtnURL'] . '"><h4 class="lptv-blue-button-big">' . $this->options['alertBtn'] . '</h4></a>';
            $out .= '</div>';
            $out .= '</div>';
            $out .= '<label for="slideout-button" id="slideout-trigger" class="slideout-trigger onAir"><img src="'. plugins_url('images/arrow-triangle.png', __FILE__) .'" /><br />' . implode( "<br />", str_split("LIVE") ) . '</label>';
            $out .= '</div>';

            echo $out;
        } else {
            return;
        }
    }

    public function loadScripts()
    {
        wp_enqueue_style('wunrav-youtube-live-embed-style', plugins_url('wunrav-youtube-live-embed/includes/stylesheets/css/style.css'), __FILE__);
        wp_enqueue_script('wunrav-youtube-live-embed-cookie', plugins_url('wunrav-youtube-live-embed/includes/live-feed-cookie.js'), __FILE__);

        if ( $this->useJS() ) {
            //
            // Make this script work with the plugin
            wp_enqueue_script('wunrav-youtube-live-embed-clientside', plugins_url('wunrav-youtube-live-embed/includes/clientside.js'), __FILE__);

            if ($this->loadjQuery() ) {
                wp_enqueue_script('wunrav-youtube-live-embed-jquery', plugins_url('wunrav-youtube-live-embed/includes/jquery-3.3.1.min.js'), __FILE__);
            }
        }
    }

    public function errorNotice( $message = null ) {

        if ( null == message) {
            $err = '<div class="error notice">Function errorNotice() is expecting a message to return.</div>';
        } else {
            $err = '<div class="error notice">' . $message . '</div>';
        }

        return $err;
    }

    public function deactivate()
    {
        // remove the wp-cron entry
        wp_clear_scheduled_hook('wunrav-youtube-hook');
    }

}
