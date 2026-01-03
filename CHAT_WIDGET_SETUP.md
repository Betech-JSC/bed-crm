# AI Chat Widget - Setup & Usage Guide

## Overview

The AI Chat Widget is a production-ready, embeddable chat solution that integrates seamlessly with your CRM. It uses OpenAI's GPT models to provide intelligent customer support and automatically creates leads and contacts from conversations.

## Features

- ✅ **Multi-tenant Support**: Fully isolated per account
- ✅ **AI-Powered**: Uses OpenAI GPT models (GPT-4o, GPT-4o-mini, GPT-4 Turbo, GPT-3.5 Turbo)
- ✅ **Easy Embedding**: Single script tag to embed on any website
- ✅ **CRM Integration**: Auto-creates leads and contacts
- ✅ **Secure**: Domain whitelisting, rate limiting, CORS protection
- ✅ **Scalable**: Built for production with proper indexing and caching
- ✅ **Customizable**: Brand colors, positions, welcome messages, AI prompts

## Installation

### 1. Environment Setup

Add your OpenAI API key to `.env`:

```env
OPENAI_API_KEY=your_openai_api_key_here
```

The OpenAI Laravel package is already installed. If you need to configure it further:

```bash
php artisan vendor:publish --tag=openai-config
```

### 2. Run Migrations

```bash
php artisan migrate
```

This will create the following tables:
- `chat_widgets` - Widget configurations
- `chat_conversations` - Conversation records
- `chat_messages` - Individual messages

## Usage

### Creating a Chat Widget

1. Navigate to **Chat Widgets** in the CRM sidebar
2. Click **Create Widget**
3. Configure:
   - **Widget Name**: Internal name for your reference
   - **Welcome Message**: First message visitors see
   - **AI System Prompt**: Defines how the AI behaves
   - **Primary Color**: Brand color for the widget
   - **Position**: Bottom-right or bottom-left
   - **AI Model**: Choose GPT model (GPT-4o-mini recommended for cost-effectiveness)
   - **Temperature**: 0-2 (higher = more creative)
   - **Max Tokens**: Response length limit
   - **Rate Limit**: Messages per hour per visitor
   - **Allowed Domains**: Restrict where widget can be embedded (optional)
   - **Settings**: 
     - Auto-create leads from conversations
     - Collect visitor email/phone

4. Click **Create Widget**

### Embedding the Widget

After creating a widget, you'll get an embed code like:

```html
<script src="https://your-domain.com/chat/widget/YOUR_WIDGET_KEY.js"></script>
```

Simply paste this code before the closing `</body>` tag on any website where you want the chat widget to appear.

**Example:**

```html
<!DOCTYPE html>
<html>
<head>
    <title>My Website</title>
</head>
<body>
    <h1>Welcome to my site</h1>
    
    <!-- Chat Widget -->
    <script src="https://your-domain.com/chat/widget/YOUR_WIDGET_KEY.js"></script>
</body>
</html>
```

### Viewing Conversations

1. Navigate to **Chat Conversations** in the CRM sidebar
2. View all conversations with filters:
   - Search by name, email, or phone
   - Filter by widget
   - Filter by status (active, closed, archived)
3. Click on any conversation to view full details and message history

### CRM Integration

The widget automatically:
- **Creates Leads**: When enabled, creates a new lead from conversations with email/phone
- **Links Contacts**: If a contact exists with the same email, links the conversation
- **Creates Contacts**: Automatically creates contacts when leads are created

Leads are created with:
- Source: "Website"
- Status: "New"
- Notes: Includes page URL where conversation started

## API Endpoints

The widget uses these public API endpoints (no authentication required):

- `POST /api/chat/{widgetKey}/init` - Initialize conversation
- `POST /api/chat/{widgetKey}/message` - Send message
- `GET /api/chat/{widgetKey}/history` - Get conversation history
- `POST /api/chat/{widgetKey}/close` - Close conversation

All endpoints include:
- Rate limiting (configurable per widget)
- CORS protection
- Domain validation (if configured)

## Security Features

1. **Domain Whitelisting**: Restrict widget to specific domains
2. **Rate Limiting**: Prevent abuse with per-visitor rate limits
3. **CORS Protection**: Only allows requests from configured origins
4. **Visitor Tracking**: Unique visitor IDs stored in localStorage
5. **IP Logging**: Visitor IP addresses logged for security

## Customization

### AI System Prompt

The system prompt defines how your AI assistant behaves. Example:

```
You are a helpful customer service representative for Acme Corporation. 
Be friendly, professional, and concise. If you don't know something, 
offer to connect the visitor with a human agent.
```

### Widget Styling

The widget automatically uses your configured:
- Primary color (for buttons and assistant messages)
- Position (bottom-right or bottom-left)

The widget is fully self-contained and won't conflict with your website's CSS.

## Cost Management

Each message tracks:
- **Tokens Used**: Total tokens consumed
- **Cost**: Calculated based on model pricing
- **Response Time**: Performance metrics

Monitor costs in the conversation details view.

### Model Pricing (approximate, as of 2024)

- **GPT-4o Mini**: $0.00015/1K input, $0.0006/1K output
- **GPT-4o**: $0.005/1K input, $0.015/1K output
- **GPT-4 Turbo**: $0.01/1K input, $0.03/1K output
- **GPT-3.5 Turbo**: $0.0005/1K input, $0.0015/1K output

## Troubleshooting

### Widget Not Appearing

1. Check widget is **Active** in CRM
2. Verify embed code is correct
3. Check browser console for errors
4. Verify domain is allowed (if domain restrictions are set)

### Messages Not Sending

1. Check OpenAI API key is configured
2. Verify rate limits aren't exceeded
3. Check browser console for API errors
4. Verify widget is active

### Leads Not Creating

1. Check "Auto-create leads" is enabled
2. Verify visitor provided email or phone
3. Check lead was created in CRM (may already exist)

## Architecture

### Database Schema

- **chat_widgets**: Widget configurations
- **chat_conversations**: Conversation records with visitor info
- **chat_messages**: Individual messages with AI metadata

### Key Models

- `ChatWidget`: Widget configuration
- `ChatConversation`: Conversation with visitor
- `ChatMessage`: Individual message

### Services

- `ChatService`: Handles AI processing, lead creation, visitor updates

### Controllers

- `ChatWidgetsController`: Admin panel for managing widgets
- `ChatConversationsController`: View conversations in CRM
- `Api\ChatController`: Public API for widget communication

## Production Considerations

1. **Caching**: Consider caching widget configurations
2. **Queue Jobs**: For high volume, consider queuing AI requests
3. **Monitoring**: Set up alerts for API failures
4. **Cost Tracking**: Monitor OpenAI usage regularly
5. **Rate Limits**: Adjust based on your traffic patterns
6. **Database Indexing**: Already optimized with proper indexes

## Support

For issues or questions, check:
- Laravel logs: `storage/logs/laravel.log`
- Browser console for frontend errors
- OpenAI API status

## Next Steps

- Add custom AI agents for different use cases
- Integrate with webhooks for real-time notifications
- Add conversation analytics and reporting
- Implement conversation handoff to human agents
- Add file upload support in chat

