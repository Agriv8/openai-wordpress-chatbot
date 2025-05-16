# Smart Chatbot Admin Interface

This directory contains the WordPress admin interface for the Smart Chatbot plugin.

## Files

- `class-openai-chatbot-admin.php` - Main admin class
- `css/admin.css` - Admin page styling
- `js/admin.js` - Admin page JavaScript functionality

## Features

1. **API Configuration**
   - Instructions for adding API key to wp-config.php
   - Live API connection testing

2. **Company Settings**
   - Company name configuration
   - Language selection
   - Email recipient settings

3. **Service Data Management**
   - JSON editor for chatbot knowledge base
   - AI-powered content expansion
   - Preview and apply system

## Usage

1. Navigate to "Smart Chatbot" in the WordPress admin menu
2. Configure your API key in wp-config.php as shown
3. Test the API connection
4. Set your company information
5. Edit or expand service data with AI assistance
6. Save your settings

## Security

- All AJAX requests use nonce verification
- Capability checks ensure only admins can access
- Input sanitization on all user data