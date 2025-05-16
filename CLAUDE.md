# OpenAI Chatbot Plugin Documentation

## Overview
This WordPress plugin implements an AI-powered chatbot using OpenAI's API for Web Smart Co's website. The chatbot provides customer service, guides users to book consultations, and handles contact form submissions.

## Project Structure

### Core Files
- [/class-openai-chatbot.php](/Users/agriv8r/Documents/python/Chatbot Ai working may25/class-openai-chatbot.php) - Main plugin class
- [/chatbot-data.json](/Users/agriv8r/Documents/python/Chatbot Ai working may25/chatbot-data.json) - Service data configuration
- [/js/openai-chatbot.js](/Users/agriv8r/Documents/python/Chatbot Ai working may25/js/openai-chatbot.js) - Frontend JavaScript
- [/css/openai-chatbot.css](/Users/agriv8r/Documents/python/Chatbot Ai working may25/css/openai-chatbot.css) - Chatbot styling

### Admin Interface
- [/admin/class-openai-chatbot-admin.php](/Users/agriv8r/Documents/python/Chatbot Ai working may25/admin/class-openai-chatbot-admin.php) - Admin functionality
- [/admin/js/admin.js](/Users/agriv8r/Documents/python/Chatbot Ai working may25/admin/js/admin.js) - Admin JavaScript
- [/admin/css/admin.css](/Users/agriv8r/Documents/python/Chatbot Ai working may25/admin/css/admin.css) - Admin styling

### Documentation
#### Root Directory Files
- [docs/PROJECT_README.md](docs/PROJECT_README.md) - Project overview
- [/readme.txt](/Users/agriv8r/Documents/python/Chatbot Ai working may25/readme.txt) - WordPress.org format

#### Documentation Folder (/docs)
- [CLAUDE.md](CLAUDE.md) - This file - Quick reference
- [docs/DOCUMENTATION.md](docs/DOCUMENTATION.md) - Complete technical reference
- [docs/INSTALLATION_GUIDE.md](docs/INSTALLATION_GUIDE.md) - Step-by-step setup
- [docs/USER_GUIDE.md](docs/USER_GUIDE.md) - End user guide
- [docs/DEVELOPER_GUIDE.md](docs/DEVELOPER_GUIDE.md) - Developer reference
- [docs/SECURITY_GUIDE.md](docs/SECURITY_GUIDE.md) - Security implementation
- [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md) - Problem resolution
- [docs/RECOMMENDED_UPDATES.md](docs/RECOMMENDED_UPDATES.md) - Authorized improvements
- [docs/IMPLEMENTATION_TRACKER.md](docs/IMPLEMENTATION_TRACKER.md) - Progress tracking
- [docs/IMPROVEMENT_IDEAS.md](docs/IMPROVEMENT_IDEAS.md) - Future development ideas
- [docs/platform-and-wp-idea.md](docs/platform-and-wp-idea.md) - SaaS migration plans
- [docs/GITHUB_INFO.md](docs/GITHUB_INFO.md) - GitHub repository details

## Admin Page

### Location
- Main WordPress admin menu (not in Settings)
- Menu title: "Smart Chatbot"
- Capability required: `manage_options`

### Features
- Company configuration
- API key instructions and testing
- AI-powered content generator for service data
- JSON data editor with preview
- Live API status checker

### Configuration Options
- Company name input
- Core services editor
- Language selection (default: UK English)
- AI content expansion toggle
- JSON preview and apply system

## Commands & Scripts

### Development
```bash
# No specific commands configured yet
# Plugin activation/deactivation handled by WordPress

# Recommended development setup:
# - WordPress local environment
# - PHP 7.4+
# - OpenAI API key configured
```

### Testing
```bash
# No automated tests configured
# Manual testing via WordPress admin
# API key validation available in admin page
```

## Key Features

### 1. Popup Menu System
- 3-second delayed popup on page load
- Responsive design (mobile/desktop)
- Minimizable interface
- Links to booking system

### 2. Chat Interface
- Contact form collection
- Service selection menu
- Real-time conversation with OpenAI
- Markdown formatting support
- Typing indicator animation
- Cross-page session persistence
- Automatic session restoration

### 3. OpenAI Integration
- GPT-4-turbo model
- Context-aware responses
- UK English optimization
- Sales-focused conversation guidance

### 4. Contact Form Handler
- Email notification system
- Conversation history tracking
- AJAX form submission

## Configuration

### Required Settings
1. **OpenAI API Key**: Set in `wp-config.php`:
   ```php
   define('OPENAI_API_KEY', 'sk-your-api-key-here');
   ```
2. **Email Settings**: Configure in admin page or update in code
3. **Company Settings**: Set via admin page:
   - Company name
   - Core services
   - Language preference
   - Service data (JSON)

### Environment Variables
```php
// wp-config.php
define('OPENAI_API_KEY', 'your-api-key-here');
```

## API Endpoints

### AJAX Handlers
- `openai_chatbot` - Main chat message handler
- `openai_chatbot_contact` - Contact form submission

## Styling & Customization

### CSS Variables
- Primary color: `rgb(88, 80, 236)`
- User message color: `rgb(91 66 126)`
- Responsive breakpoint: 768px

### Customizable Elements
- Popup positioning
- Chat window dimensions
- Button colors and styles
- Message formatting

## Data Structure

### chatbot-data.json
Contains:
- Company information
- Service listings with pricing
- Package details
- Contact information
- Custom plugin descriptions

**Note**: This file is managed through the admin interface with AI assistance. Direct editing is not recommended.

### Conversation History
```javascript
conversationHistory: [
  { role: 'user', content: 'message' },
  { role: 'assistant', content: 'response' }
]
```

### Session Persistence
- Stored in localStorage
- 30-minute timeout
- Survives page navigation
- Automatic cleanup

## Error Handling
- API timeout: 200 seconds
- Error logging to WordPress debug
- User-friendly error messages
- Fallback responses

## Security Considerations
- Input sanitization
- AJAX nonce verification (to be implemented)
- Secure API key storage
- Email validation

## Performance Optimization
- Lazy loading of chat interface
- AJAX-based communication
- Minimal DOM manipulation
- CSS animations for smooth UX

## Browser Compatibility
- Modern browsers (Chrome, Firefox, Safari, Edge)
- IE11+ partial support
- Mobile responsive design

## Dependencies
- WordPress 5.0+
- jQuery (WordPress bundled)
- PHP 7.4+
- OpenAI API access

## Deployment Checklist
1. Set OpenAI API key in wp-config.php
2. Verify email recipient address
3. Test popup delay timing
4. Check responsive design
5. Validate form submissions
6. Test error handling
7. Review conversation flow

## Maintenance Notes
- Monitor API usage and costs
- Regular security updates
- Performance monitoring
- User feedback collection

## Support
- Company: Web Smart Co
- Phone: 01462 544738
- Email: enquiries@web-smart.co
- Meeting Scheduler: https://appt.link/meet-with-pete-gypps/chat-with-pete-R9wR8KNd

## Version History
- v4.1 - Current version with enhanced UI and conversation flow

---

© Built by [Web-Smart.Co](https://web-smart.co)