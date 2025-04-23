# Deploying Your Telegram Bot to Vercel

This guide will help you deploy your Telegram bot to Vercel's serverless platform.

## Prerequisites

1. A Vercel account (sign up at [vercel.com](https://vercel.com))
2. A Telegram bot token (from [BotFather](https://t.me/botfather))
3. Your Smart Correction API endpoint

## Deployment Steps

### 1. Push Your Code to a Git Repository

Push your bot code to GitHub, GitLab, or Bitbucket.

### 2. Import Your Project in Vercel

1. Go to [vercel.com/new](https://vercel.com/new)
2. Import your repository
3. Configure the project:
   - Framework Preset: `Other`
   - Root Directory: `./` (or where your package.json is located)
   - Build Command: `npm install`
   - Output Directory: Leave empty
   - Install Command: Leave default

### 3. Set Environment Variables

In the Vercel project settings, add these environment variables:

- `TELEGRAM_BOT_TOKEN`: Your Telegram bot token
- `API_ENDPOINT`: Your Smart Correction API endpoint (e.g., https://your-api-name.onrender.com/smart-correct/)
- `WEBHOOK_DOMAIN`: Your Vercel deployment URL (e.g., https://your-bot.vercel.app)
- `VERCEL`: Set to `1` (this helps the code detect it's running on Vercel)

### 4. Deploy Your Project

Click "Deploy" and wait for the deployment to complete.

### 5. Set Up the Webhook

After deployment, visit:
\`\`\`
https://your-bot.vercel.app/set-webhook
\`\`\`

This will configure your Telegram bot to send updates to your Vercel deployment.

## Vercel Limitations to Be Aware Of

1. **Cold Starts**: Serverless functions may experience "cold starts" if they haven't been used recently.

2. **Execution Time**: Vercel has a maximum execution time of 10 seconds for serverless functions.

3. **Statelessness**: Serverless functions are stateless, so any in-memory session data will be lost between invocations. The bot uses Telegraf's session middleware which stores session data in memory, so user statistics may reset occasionally.

## Troubleshooting

If your bot isn't responding:

1. Check if the webhook is set correctly by visiting `/set-webhook`
2. Verify all environment variables are set correctly
3. Check Vercel logs for any errors
4. Make sure your API endpoint is accessible and working

## Keeping Your Bot "Warm"

To minimize cold starts, you can set up a simple cron job (using a service like UptimeRobot) to ping your bot's URL every few minutes.
