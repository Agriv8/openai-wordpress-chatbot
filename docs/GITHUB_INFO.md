# GitHub Repository Information

## Repository Details

- **Repository URL**: https://github.com/Agriv8/openai-wordpress-chatbot
- **Default Branch**: main
- **Created**: May 16, 2025
- **Description**: WordPress plugin for AI-powered chatbot using OpenAI GPT-4

## Git Configuration

- **Username**: Agriv8 
- **Email**: peteg@corsolutions.co.uk
- **Credentials**: Stored in macOS Keychain

## Common Git Commands

```bash
# Check status
git status

# Stage all changes
git add .

# Commit with message
git commit -m "Your commit message"

# Push to GitHub
git push origin main

# Pull latest changes
git pull origin main

# View commit history
git log --oneline

# Create new branch
git checkout -b feature/branch-name

# Switch branches
git checkout branch-name

# Merge branch
git merge feature/branch-name
```

## GitHub API Access

The repository was created using the GitHub API with credentials from macOS Keychain:

```bash
# List repositories
curl -s -H "Authorization: token $(security find-internet-password -s github.com -a Agriv8 -w)" https://api.github.com/user/repos

# Create repository via API
curl -X POST -H "Authorization: token $(security find-internet-password -s github.com -a Agriv8 -w)" https://api.github.com/user/repos -d '{...}'
```

## Repository Structure

```
openai-wordpress-chatbot/
├── admin/          # Admin interface files
├── css/            # Stylesheets
├── docs/           # Documentation
├── includes/       # PHP classes
├── js/             # JavaScript files
├── .gitignore      # Git ignore rules
├── chatbot-data.json
├── class-openai-chatbot.php
└── readme.txt      # WordPress plugin info
```

## Development Workflow

1. Always pull before starting work: `git pull origin main`
2. Create feature branch for new work: `git checkout -b feature/description`
3. Make changes and test thoroughly
4. Commit with descriptive messages
5. Push to GitHub: `git push origin feature/description`
6. Create pull request for review

## Repository Settings

- Public repository
- Issues enabled
- Projects enabled  
- Wiki enabled
- Main branch protection recommended for production