## API KEYS IN REPO... DO NOT MAKE PUBLIC UNLESS REMOVED 

# YouTube Live Embed PHP Class
This is a WordPress plugin that uses the detects if your YouTube channel is live streaming and then embeds the live YouTube video on a page using a shortcode. It also adds a slideout notification to the page directing people to the live stream. This plugin was created using a barebones class that I have shameless stolen from [this repo](https://github.com/iacchus/youtube-live-embed).

## Installation / Usage

Place this repo in the WordPress plugins folder, enable the plugin and then start using the `[live-youtube]` shortcode wherever you want it to appear.

**It takes some seconds (sometimes even a minute) after the session is live for the server to tell that it is live.** This is just lag from YouTube's API.

## Todo List

* Create admin options for title, message, and button text of slideout.
* Add ability to customize the off-air message.
