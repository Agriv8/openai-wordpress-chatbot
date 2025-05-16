# ZIP Creation Instructions

## Process for Creating Project ZIP Files

When asked to create a ZIP file of the project:

1. **Check for Existing ZIP Files**
   - Look in the main project directory for any existing ZIP files
   - Delete any found ZIP files before creating new one

2. **Create New ZIP File**
   - Use today's date in the filename format: `openai-chatbot-YYYY-MM-DD.zip`
   - Include all necessary project files
   - Exclude development files, node_modules, etc.

3. **Example Commands**
   ```bash
   # Check for and remove existing ZIPs
   ls *.zip
   rm -f *.zip
   
   # Create new ZIP with today's date
   zip -r openai-chatbot-2025-05-16.zip . -x "*.git*" -x "node_modules/*" -x "*.DS_Store"
   ```

## Note for Claude/AI Assistants

Always follow this process when user requests a ZIP file:
1. First check for and delete existing ZIPs in main directory
2. Create new ZIP with current date
3. Confirm successful creation