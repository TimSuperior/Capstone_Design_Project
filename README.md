# Smart Correction Telegram Bot

A Telegram bot built with Express.js that forwards text messages and photos to the Smart Correction API for grammar and spelling correction.

## Features

- Express.js server for handling webhook requests
- Handles text messages for grammar correction
- Processes photos with text for extraction and correction
- Provides user statistics
- Checks API status
- Comprehensive error handling

## How It Works

1. **Text Correction**:
   - User sends a text message to the bot
   - Bot forwards the text to the Smart Correction API
   - API analyzes the text and returns corrections
   - Bot formats and sends the corrections back to the user

2. **Image Text Correction**:
   - User sends an image containing text
   - Bot forwards the image to the Smart Correction API
   - API extracts text from the image, analyzes it, and returns corrections
   - Bot formats and sends the corrections back to the user

## Setup

1. Create a Telegram bot using [BotFather](https://t.me/botfather)
2. Set up environment variables:
   - `TELEGRAM_BOT_TOKEN`: Your Telegram bot token
   - `API_ENDPOINT`: Your Smart Correction API endpoint (e.g., https://your-api-name.onrender.com/smart-correct/)
   - `WEBHOOK_DOMAIN`: Your public domain (e.g., https://your-app.vercel.app)
   - `PORT`: (Optional) Port for the Express server (defaults to 3000)

3. Install dependencies:
   \`\`\`bash
   npm install
   \`\`\`

4. Run the bot:
   \`\`\`bash
   npm start
   \`\`\`

## Local Development

For local development, you can use tools like ngrok to expose your local server:

1. Install ngrok: https://ngrok.com/download
2. Run your Express server: `npm start`
3. In another terminal, run: `ngrok http 3000`
4. Set the `WEBHOOK_DOMAIN` environment variable to the ngrok URL

## Commands

- `/start` - Initialize the bot
- `/help` - Display help information
- `/status` - Check if the API is online
- `/stats` - View your usage statistics

## Deployment

This bot is configured for deployment on Vercel. Simply push to your connected repository, and Vercel will deploy the bot automatically.

### Important for Vercel Deployment

When deploying to Vercel, make sure to:

1. Set all required environment variables in the Vercel dashboard
2. Set `WEBHOOK_DOMAIN` to your Vercel deployment URL (e.g., https://your-app.vercel.app)

## API Requirements

The Smart Correction API should handle:

1. Text correction:
   - Form data with field `direct_text` containing the text to correct

2. Image text extraction and correction:
   - Form data with field `file` containing the image file

The API should return a JSON response with:
- `input`: The original text or extracted text from the image
- `feedback`: The corrections and explanations

Example response:
\`\`\`json
{
  "input": "She go to the park every morning.",
  "feedback": "Corrected: She goes to the park every morning.\nExplanation: Verb agreement with singular subject."
}
