s is a WordPress plugin that uses the YouTube API to detect if you're channel is live streaming and then embeds the YouTube live video on a page. A  modified version of [this repo](https://github.com/iacchus/youtube-live-embed) has been implemented here.

## Usage

Install the plugin in your plugins folder, enable it and then start using the `[live-youtube]` shortcode wherever you want it to appear. For now, we're hard coding a generic off-air message. More customizability may be added later. 

#### 1. First parameter: CHANNEL ID

How to find your channel ID:

1. Open https://youtube.com
2. At top left, below the logo, click "My Channel"
3. It will open an address like https://www.youtube.com/channel/[LOTS-OF-NONSENSE]
4. Copy the [LOTS-OF-NONSENSE] part of the URL and paste it in the **Channel ID** field of your WordPress settings.


#### 2. Second parameter: API KEY

To create your API key:

1. Create a project in the google developer console. https://console.developers.google.com
2. Enable **YouTube API** for that project.
3. Create a credential of type "Public API Access", a "Server key", with the IPs from the server you will query the API from.
4. This will create an API Key for you. Copy it and paste it into the **API Key** field in the WordPress settings.

## Todo List

* Add ability to customize the off-air message.
* Add option to show a slate of the youtube video size. Make it a link to refresh the page or even better, refresh the page automatically.
