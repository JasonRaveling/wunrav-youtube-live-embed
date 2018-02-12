This is a WordPress plugin that uses the YouTube API to detect if a channel is live streaming and then embeds the live YouTube video on a page using a shortcode.

## Usage

Place this plugin in your plugins folder, enable it and then start using the `[live-youtube]` shortcode wherever you want live video to appear. A slidout will appear on every page of your site once the channel is live. The slideout message can be customized from the WordPress admin.

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
