AI Chat Assistant

A lightweight WordPress plugin that adds an AI-powered chat assistant to your site using OpenAI's streaming API.
Description
AI Chat Assistant enables you to embed an interactive AI chatbot on your WordPress site. By adding the [ai_assistant] shortcode to any page or post, you can provide users with a real-time conversational experience powered by OpenAI's streaming technology. The plugin includes an admin settings page to configure your OpenAI API key and Assistant ID, making setup straightforward.
Key Features

Real-Time Chat: Stream responses from OpenAI for a seamless conversational experience.
Shortcode Integration: Use [ai_assistant] to add the chatbot anywhere on your site.
Customizable Assistant: Specify an Assistant ID to tailor the AI's behavior.
Download Chat: Allow users to save their conversations as text files.
Secure and Lightweight: Includes nonce validation and minimal dependencies (jQuery only).

Ideal for adding AI-driven support, Q&A, or interactive content to your WordPress site.
Installation

Download the plugin from this repository.
Upload the ai-chat-assistant folder to the /wp-content/plugins/ directory.
Activate the plugin through the 'Plugins' menu in WordPress.
Navigate to Settings > Assistant Settings in the WordPress admin dashboard.
Enter your OpenAI API key and Assistant ID (obtain these from your OpenAI account).
Add the [ai_assistant] shortcode to any page or post to display the chat interface.

Frequently Asked Questions
Where do I get an OpenAI API key and Assistant ID?
Sign up for an OpenAI account at platform.openai.com. Create an API key in the API section and set up an Assistant in the Assistants dashboard.
Can I customize the chat interface?
Yes, the chat UI uses CSS classes (e.g., .occ-assistant-message, .occ-assistant-user-message). Override the default styles in your theme's CSS.
Does the plugin support multiple assistants?
You can specify a custom Assistant ID using the shortcode, e.g., [ai_assistant assistant_id="your-assistant-id"]. The default Assistant ID is set in the plugin settings.
Is the plugin secure?
The plugin validates API keys, uses nonces for AJAX requests, and sanitizes all inputs to ensure security.
Screenshots

Chat Interface: The chat UI in action, showing user and AI messages.
Admin Settings: The settings page for configuring OpenAI API key and Assistant ID.

Changelog
1.6

Added support for streaming OpenAI responses via AJAX.
Improved chat UI with downloadable conversation feature.
Enhanced security with nonce validation and input sanitization.

1.0

Initial release with basic chat functionality and shortcode support.

Upgrade Notice
1.6
This update introduces streaming responses and a downloadable chat feature. Back up your settings before upgrading.
License
This plugin is licensed under the GPLv2 or later. See the LICENSE file for details.
Contributing
Contributions are welcome! Please submit pull requests or open issues on the GitHub repository.
