# BotBuddy

**Status: ⚠️ In Development** - This plugin is still under active development and not yet complete. Features are being actively worked on.

## Overview

BotBuddy is a powerful AI-powered chatbot plugin for WordPress websites, specifically designed for mobile service websites. It delivers instant, accurate answers using your own custom data, enabling businesses to improve customer support, automate responses, and provide 24/7 assistance directly from their WordPress site.

## Key Features

- **AI-Powered Responses**: Uses Hugging Face models for intelligent text embedding and response generation
- **Vector Database Integration**: Integrates with Pinecone for semantic search and vector storage
- **Custom Knowledge Base**: Train the chatbot with your own data and documents
- **Shortcode Support**: Easy integration via `[botbuddy]` shortcode
- **REST API**: Complete REST API for chat interactions and admin operations
- **Admin Settings Panel**: Full configuration panel in WordPress admin under Tools > BotBuddy
- **24/7 Availability**: Automated chat support without manual intervention
- **Customizable Bot**: Configure bot name, avatar, system prompt, and response templates
- **Activity Logging**: Built-in logging system for debugging and monitoring

## Plugin Architecture

### Core Class: `Bot_buddy`

The main plugin class implements the singleton pattern and serves as the central hub for all plugin functionality.

**Key Responsibilities:**
- Plugin initialization and setup
- Configuration management (settings storage and retrieval)
- Service initialization and dependency injection
- Hook registration (WordPress actions and filters)
- External API request handling
- Activity logging

**Key Methods:**
- `get_instance()` - Singleton instance retrieval
- `init()` - Initialize plugin and load all services
- `get_settings()` - Retrieve all plugin settings
- `save_settings_ajax()` - AJAX handler for settings updates
- `send_request()` - Generic HTTP request handler for external APIs
- `get_vector()` - Get vector embeddings from Hugging Face API
- `add_log()` / `get_logs()` / `clear_logs()` - Logging functionality
- `register_scripts()` - Register frontend assets
- `enqueue_admin_assets()` - Load admin panel assets

### Service Classes

#### 1. **Bot_buddy_Shortcode** (`includes/class-bot-buddy-shortcode.php`)
Handles frontend shortcode rendering and asset loading.
- Registers `[botbuddy]` shortcode
- Loads frontend CSS and JavaScript
- Provides bot configuration data to frontend via `wp_localize_script()`

#### 2. **Bot_buddy_Admin_REST** (`admin/rest-api-admin.php`)
Manages admin-only REST endpoints for backend operations.
- **Endpoint**: `POST /botbuddy/v1/admin/chunking`
- Currently a placeholder for future document chunking and vectorization
- Requires admin capabilities

#### 3. **Bot_buddy_Public_API** (`includes/class-bot-buddy-public-api.php`)
Handles public-facing REST API endpoints for chatbot interactions.
- **Endpoint**: `POST /botbuddy/v1/public/message`
- Validates requests using WordPress nonces
- Processes user messages and returns AI-generated responses

## Configuration & Settings

BotBuddy stores all settings as WordPress options. The following settings are configurable:

| Setting | Option Name | Default | Description |
|---------|------------|---------|-------------|
| Bot Name | `bot_name` | BotBuddy | Display name for the chatbot |
| Bot Avatar | `bot_avatar` | Default image | Avatar image URL for the bot |
| Document ID | `bot_doc_id` | (empty) | ID for knowledge base documents |
| Hugging Face API Key | `bot_hugging_face_api_key` | (empty) | API key for HuggingFace embeddings |
| Pinecone API Key | `bot_pinecone_api_key` | (empty) | API key for Pinecone vector database |
| Pinecone Host | `bot_pinecone_host` | (empty) | Pinecone instance host URL |
| System Prompt | `bot_system_prompt` | "You are a helpful assistant..." | System prompt for AI model |
| Prompt Template | `bot_prompt_template` | Multi-line template | Template for formatting user queries with context |

### Admin Panel

Located at **Tools > BotBuddy**, the admin panel provides:
- Settings form for all configuration options
- Activity logs display
- Clear logs functionality
- Real-time settings updates via AJAX

## REST API Endpoints

### Public Endpoint
```
POST /wp-json/botbuddy/v1/public/message
```
- **Authentication**: WordPress nonce verification
- **Request**: User message and conversation context
- **Response**: AI-generated response with metadata

### Admin Endpoint
```
POST /wp-json/botbuddy/v1/admin/chunking
```
- **Authentication**: Requires `manage_options` capability
- **Purpose**: Document processing and vectorization (in development)

## File Structure

```
bot-buddy.php                          # Main plugin file
├── admin/
│   ├── admin-page.php                # Admin settings page UI
│   ├── rest-api-admin.php            # Admin REST endpoints
│   └── assets/
│       ├── css/admin-page.css        # Admin panel styles
│       └── js/admin-page.js          # Admin panel functionality
├── includes/
│   ├── class-bot-buddy-shortcode.php # Shortcode class
│   ├── class-bot-buddy-public-api.php # Public API class
│   └── shortcode-body.php            # Shortcode template
├── assets/
│   ├── css/
│   │   ├── botbuddy.css              # Frontend styles (default)
│   │   └── botbuddy-frontend.css     # Frontend styles (extended)
│   ├── js/
│   │   ├── botbuddy.js               # Frontend script (default)
│   │   └── botbuddy-frontend.js      # Frontend script (extended)
│   └── images/
│       └── bot-avatar.avif           # Default bot avatar image
└── README.md                          # This file
```

## External Integrations

### Hugging Face
- **Model**: BAAI/bge-large-en-v1.5
- **Endpoint**: `https://router.huggingface.co/hf-inference/models/BAAI/bge-large-en-v1.5/pipeline/feature-extraction`
- **Purpose**: Text vectorization and embeddings
- **Authentication**: Bearer token (API key required)

### Pinecone
- **Purpose**: Vector database for semantic search and knowledge storage
- **Configuration**: Host URL and API key required in settings

## Logging System

BotBuddy includes a comprehensive logging system using WordPress transients:

- **Transient Key**: `botbuddy_logs`
- **Duration**: 12 hours
- **Features**:
  - Timestamped log entries
  - Supports scalar values and JSON serialized objects
  - WP-CLI support for command line logging
  - Viewable in admin panel
  - Clearable with nonce verification

## Security Features

- **Nonce Verification**: All AJAX and REST requests verified with WordPress nonces
- **Capability Checks**: Admin operations require `manage_options` capability
- **Input Sanitization**: All user inputs properly sanitized and validated
- **URL Escaping**: External URLs properly escaped
- **Data Validation**: Textarea and text field content properly validated

## Development Status

### ✅ Completed
- Core plugin architecture and initialization
- Settings management and storage
- Admin panel UI and functionality
- REST API route registration
- Shortcode implementation
- Frontend asset loading
- Logging system
- External API request handling
- Hugging Face vector generation (partial)

### 🚧 In Progress / Planned
- Document vectorization and chunking
- Pinecone integration for vector storage
- Message history and context management
- Response caching
- Conversation context persistence
- Advanced prompt engineering
- Analytics and metrics
- Multi-language support
- Extended customization options

## Installation

1. Place the plugin folder in `/wp-content/plugins/`
2. Activate the plugin from WordPress admin panel
3. Navigate to Tools > BotBuddy
4. Configure API keys:
   - Hugging Face API key
   - Pinecone API key and host
5. Customize bot settings (name, avatar, prompts)
6. Add `[botbuddy]` shortcode to any page/post

## Configuration Example

1. Get API credentials:
   - Hugging Face: https://huggingface.co/settings/tokens
   - Pinecone: https://www.pinecone.io/

2. In WordPress admin > Tools > BotBuddy:
   - Enter your API keys
   - Set bot name and avatar
   - Customize system prompt if needed
   - Configure response template

3. On frontend page/post:
   ```
   [botbuddy]
   ```

## Constants Defined

```php
BOT_BUDDY_VERSION       // Plugin version (cache buster)
BOT_BUDDY_PLUGIN_DIR    // Absolute path to plugin directory
BOT_BUDDY_PLUGIN_URL    // URL to plugin directory
```

## Hooks & Filters

### Actions
- `wp_enqueue_scripts` - Register frontend scripts
- `admin_menu` - Add admin menu
- `admin_enqueue_scripts` - Load admin assets
- `wp_ajax_botbuddy_save_settings` - AJAX settings save handler
- `admin_post_botbuddy_clear_logs` - Clear logs handler
- `rest_api_init` - Register REST routes

### Filters
- `script_loader_tag` - Modify script tags for modules

## Technologies Used

- **WordPress**: Core plugin framework
- **REST API**: REST endpoints
- **Hugging Face**: Text embeddings
- **Pinecone**: Vector database
- **JavaScript**: Frontend interactivity
- **CSS**: Styling

## Author & License

**Author**: ForaziTech  
**Author URI**: https://forazitech.com  
**License**: GPL2  
**License URI**: https://www.gnu.org/licenses/gpl-2.0.html  
**Text Domain**: botbuddy

---

**Last Updated**: May 2026  
**Plugin Version**: 1.0.0 (Alpha)  
**Status**: Under Active Development