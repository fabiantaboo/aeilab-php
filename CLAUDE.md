# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

AEI Lab Internal Tool - A PHP web application for generating datasets for Artificial Emotional Intelligence (AEI) research. The system creates dialogs between AEI characters and User characters using the Anthropic API to generate training data.

## Architecture

### Core Classes
- **Database** (`classes/Database.php`): PDO-based database abstraction with auto-setup
- **Character** (`classes/Character.php`): Manages AEI and User character profiles with pairing system
- **Dialog** (`classes/Dialog.php`): Handles dialog creation and conversation management  
- **DialogJob** (`classes/DialogJob.php`): Background job system for automated dialog generation
- **AnthropicAPI** (`classes/AnthropicAPI.php`): Anthropic Claude API integration
- **User** (`classes/User.php`): User authentication and role management
- **Setup** (`classes/Setup.php`): Automated database initialization

### Key Features
- **Character Pairing**: AEI characters can be paired with User characters for dialogs
- **Background Processing**: Automated dialog turn generation via cron jobs using `background/dialog_processor.php`
- **Character Awareness**: Characters maintain awareness of their chat partners
- **API Usage Tracking**: Logs and tracks Anthropic API usage and costs
- **JSON Export**: Dialog data can be downloaded as JSON for ML training

### Database Schema
The system auto-creates these main tables:
- `users`: Authentication and user management
- `characters`: AEI and User character profiles
- `character_pairings`: Relationships between AEI and User characters
- `dialogs`: Dialog sessions with metadata
- `dialog_messages`: Individual conversation turns with Anthropic request data
- `dialog_jobs`: Background job queue for automated processing

## Development Setup

### Initial Configuration
1. Copy `config/config.example.php` to `config/config.php`
2. Configure database connection and Anthropic API key
3. Set up web server pointing to project root
4. Access any page - database tables are auto-created

### Cron Job Setup
For automated dialog generation, set up cron job:
```bash
# Every 30 seconds
* * * * * /usr/bin/php /path/to/project/background/dialog_processor.php
* * * * * sleep 30; /usr/bin/php /path/to/project/background/dialog_processor.php
```

### Common Commands
- **Manual Setup**: Access `setup.php` for manual database initialization
- **Debug Jobs**: Access `debug_jobs.php` to monitor background job status
- **Test API**: Run `test_anthropic.php` to verify Anthropic API configuration
- **Fix Database**: Run `fix_database.php` if database schema needs updates

## File Structure

### Main Pages
- `index.php`: Landing page and overview
- `dashboard.php`: Main dashboard with system statistics
- `characters.php`: Character management interface
- `dialogs.php`: Dialog listing and management
- `character-create.php`, `character-edit.php`, `character-view.php`: Character CRUD
- `dialog-create.php`, `dialog-view.php`: Dialog CRUD
- `admin.php`: User administration (admin only)
- `jobs.php`: Background job monitoring

### Authentication
- `login.php`: User login
- `logout.php`: Session cleanup
- Default admin credentials: `admin` / `admin123`

### Bootstrap & Includes
- `includes/bootstrap.php`: Core initialization, loads all classes and handles authentication
- Auto-loads classes from `classes/` directory
- Sets up global instances: `$db`, `$user`, `$character`, `$dialog`, `$dialogJob`

## Important Notes

### Security
- All forms use CSRF protection
- Session-based authentication with configurable timeout
- Admin role required for user management
- SQL injection protection via PDO prepared statements

### API Integration
- Uses Anthropic Claude API for dialog generation
- Configurable model and token limits in config
- Full request/response logging for training data analysis
- Rate limit handling with automatic retry

### Background Processing
- Dialog turns generated automatically via cron job
- Job system prevents duplicate processing
- Failed jobs are automatically retried
- Old jobs are cleaned up periodically

### Database
- Auto-setup on first access
- Migration-friendly with column existence checks
- Soft deletes for characters and dialogs
- Full audit trail for all operations