# OpenAI Chatbot WordPress Plugin

A WordPress plugin that implements an AI-powered chatbot using OpenAI's GPT-4 API for Web Smart Co's website.

## Features

- **Smart Popup System**: Delayed popup with minimize/close functionality
- **AI-Powered Conversations**: Uses GPT-4-turbo for intelligent responses
- **Contact Form Integration**: Captures user information before chat
- **Service Selection**: Guided conversation flow based on user interests
- **Email Notifications**: Sends chat transcripts to designated email
- **Responsive Design**: Optimized for both desktop and mobile devices
- **Multi-language Support**: Configurable language settings (UK/US English, Spanish, French, German)
- **Admin Dashboard**: Comprehensive settings page in WordPress admin
- **AI Content Assistant**: Expand and enhance service data with AI
- **Live API Testing**: Built-in connection tester
- **Cross-Page Persistence**: Chat sessions persist across page navigation
- **Session Management**: 30-minute session timeout for security

## Installation

1. Upload the plugin files to `/wp-content/plugins/openai-chatbot/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Set your OpenAI API key in `wp-config.php`:
   ```php
   define('OPENAI_API_KEY', 'your-api-key-here');
   ```

## Configuration

### Admin Dashboard

1. Navigate to "Smart Chatbot" in WordPress admin menu
2. Configure all settings through the user-friendly interface:
   - API key instructions
   - Company information
   - Language preferences
   - Email settings
   - Service data with AI assistance

### Manual Configuration

1. **API Key**: Add to `wp-config.php` as shown above
2. **Email Recipient**: Set via admin page or update in code
3. **Service Data**: Edit through admin interface with AI expansion

### Optional Customization

- Adjust popup delay (currently 3 seconds)
- Modify color scheme in CSS
- Update conversation prompts in JavaScript

## File Structure

```
openai-chatbot/
├── class-openai-chatbot.php    # Main plugin file
├── chatbot-data.json          # Business/service data
├── admin/                     # Admin interface
│   ├── class-openai-chatbot-admin.php
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
├── css/
│   └── openai-chatbot.css     # Styling
├── js/
│   └── openai-chatbot.js      # Frontend logic
├── includes/                  # Additional classes
│   ├── class-chatbot-analytics.php
│   ├── class-chatbot-installer.php
│   └── class-media-handler.php
├── docs/                      # All documentation
│   ├── PROJECT_README.md      # This file
│   ├── ADMIN_README.md        # Admin interface guide
│   ├── CLAUDE.md              # Developer reference
│   ├── DOCUMENTATION.md       # Complete documentation
│   ├── USER_GUIDE.md          # User documentation
│   └── ... (other guides)
└── readme.txt                 # WordPress plugin info
```

## Usage

The chatbot automatically appears 3 seconds after page load. Users can:

1. Click "Ask the AI" to start a chat
2. Fill out the contact form
3. Select their service of interest
4. Have a conversation with the AI assistant
5. End chat to submit their information

## Development

### Requirements

- WordPress 5.0+
- PHP 7.4+
- jQuery (bundled with WordPress)
- OpenAI API access

### Key Functions

- `OpenAIChatbot::getResponse()` - Handles AI responses
- `OpenAIChatbot::ajax_handler()` - Processes chat messages
- `OpenAIChatbot::contact_form_handler()` - Manages form submissions

## Security

- Input sanitization on all user data
- API key stored securely in wp-config
- Email validation on contact forms

## Support

For issues or questions:
- Email: enquiries@web-smart.co
- Phone: 01462 544738
- Documentation: See [docs/](./) folder for all documentation
- Quick Reference: [CLAUDE.md](CLAUDE.md) for development
- User Guide: [USER_GUIDE.md](USER_GUIDE.md)
- Installation: [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md)
- Admin Guide: [ADMIN_README.md](ADMIN_README.md)

## License

Proprietary - Web Smart Co

## Version

Current version: 4.1

---

© Built by [Web-Smart.Co](https://web-smart.co)