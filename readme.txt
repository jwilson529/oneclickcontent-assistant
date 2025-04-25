=== AI Chat Assistant ===
Contributors: jwilson529
Tags: ai, chat, openai, assistant, chatbot
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.0
Stable tag: 1.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A lightweight WordPress plugin that adds an AI-powered chat assistant to your site using OpenAI's streaming API.

== Description ==

AI Chat Assistant lets you embed a conversational AI chatbot on your WordPress site using OpenAI's streaming technology. Add the `[ai_assistant]` shortcode to any page or post to display an interactive chat interface where users can ask questions and receive real-time responses. The plugin is easy to set up, with an admin settings page to configure your OpenAI API key and Assistant ID.

**Key Features:**
- **Real-Time Chat**: Stream responses from OpenAI for a smooth, conversational experience.
- **Shortcode Integration**: Use `[ai_assistant]` to add the chat interface anywhere on your site.
- **Customizable Assistant**: Specify an Assistant ID to tailor the AI's behavior.
- **Download Chat**: Allow users to save their conversations as text files.
- **Secure and Lightweight**: Includes nonce validation and minimal dependencies (jQuery only).

Ideal for adding AI-driven support, Q&A, or interactive content to your WordPress site.

== Installation ==

1. Upload the `ai-chat-assistant` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Assistant Settings** in the WordPress admin dashboard.
4. Enter your OpenAI API key and Assistant ID (obtain these from your OpenAI account).
5. Add the `[ai_assistant]` shortcode to any page or post to display the chat interface.

== Frequently Asked Questions ==

= Where do I get an OpenAI API key and Assistant ID? =
Sign up for an OpenAI account at https://platform.openai.com. Create an API key in the API section and set up an Assistant in the Assistants dashboard.

= Can I customize the chat interface? =
Yes, the chat UI uses CSS classes (e.g., `.occ-assistant-message`, `.occ-assistant-user-message`). You can override the default styles in your theme's CSS.

= Does the plugin support multiple assistants? =
You can specify a custom Assistant ID using the shortcode, e.g., `[ai_assistant assistant_id="your-assistant-id"]`. The default Assistant ID is set in the plugin settings.

= Is the plugin secure? =
The plugin validates API keys, uses nonces for AJAX requests, and sanitizes all inputs to ensure security.

== Screenshots ==

1. The chat interface in action, showing user and AI messages.
2. Admin settings page for configuring OpenAI API key and Assistant ID.

== Changelog ==

= 1.6 =
* Added support for streaming OpenAI responses via AJAX.
* Improved chat UI with downloadable conversation feature.
* Enhanced security with nonce validation and input sanitization.

= 1.0 =
* Initial release with basic chat functionality and shortcode support.

== Upgrade Notice ==

= 1.6 =
This update introduces streaming responses and a downloadable chat feature. Back up your settings before upgrading.

== License ==

This plugin is licensed under the GPLv2 or later. See the included LICENSE file for details.