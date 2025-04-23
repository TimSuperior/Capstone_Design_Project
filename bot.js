import { Telegraf } from 'telegraf';
import fetch from 'node-fetch';
import FormData from 'form-data';
import { Buffer } from 'buffer';

// Initialize the bot with your token (this would come from environment variables in production)
const bot = new Telegraf(process.env.TELEGRAM_BOT_TOKEN || 'YOUR_BOT_TOKEN');

// Your custom API endpoint
const API_ENDPOINT = process.env.API_ENDPOINT || 'https://your-custom-api.com/analyze';

// Start command handler
bot.start((ctx) => {
  ctx.reply('Welcome! Send me a text message or a photo, and I\'ll analyze it for you.');
});

// Help command handler
bot.help((ctx) => {
  ctx.reply('Send me a text message or a photo, and I\'ll analyze it for you.');
});

// Text message handler
bot.on('text', async (ctx) => {
  try {
    const message = ctx.message.text;
    console.log(`Received text message: ${message}`);
    
    // Send a "typing" action to show the bot is processing
    await ctx.replyWithChatAction('typing');
    
    // Forward the text to your API
    const response = await fetch(API_ENDPOINT, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        type: 'text',
        content: message,
        userId: ctx.from.id.toString(),
      }),
    });
    
    if (!response.ok) {
      throw new Error(`API responded with status: ${response.status}`);
    }
    
    const data = await response.json();
    
    // Send the API's feedback to the user
    await ctx.reply(data.feedback || 'Analysis complete, but no specific feedback was provided.');
    
  } catch (error) {
    console.error('Error processing text message:', error);
    await ctx.reply('Sorry, I encountered an error while processing your message. Please try again later.');
  }
});

// Photo handler
bot.on('photo', async (ctx) => {
  try {
    console.log('Received photo message');
    
    // Send a "processing photo" action
    await ctx.replyWithChatAction('upload_photo');
    
    // Get the photo file ID (Telegram stores multiple sizes, we'll use the largest)
    const photoId = ctx.message.photo[ctx.message.photo.length - 1].file_id;
    
    // Get the file link from Telegram
    const fileLink = await ctx.telegram.getFileLink(photoId);
    
    // Download the photo
    const photoResponse = await fetch(fileLink.href);
    const photoBuffer = await photoResponse.buffer();
    
    // Create a FormData instance for multipart/form-data request
    const formData = new FormData();
    formData.append('type', 'photo');
    formData.append('userId', ctx.from.id.toString());
    formData.append('photo', photoBuffer, { filename: 'photo.jpg' });
    
    // Add caption if it exists
    if (ctx.message.caption) {
      formData.append('caption', ctx.message.caption);
    }
    
    // Forward the photo to your API
    const response = await fetch(API_ENDPOINT, {
      method: 'POST',
      body: formData,
    });
    
    if (!response.ok) {
      throw new Error(`API responded with status: ${response.status}`);
    }
    
    const data = await response.json();
    
    // Send the API's feedback to the user
    await ctx.reply(data.feedback || 'Photo analysis complete, but no specific feedback was provided.');
    
  } catch (error) {
    console.error('Error processing photo:', error);
    await ctx.reply('Sorry, I encountered an error while processing your photo. Please try again later.');
  }
});

// Error handler
bot.catch((err, ctx) => {
  console.error('Bot error:', err);
  ctx.reply('An error occurred while processing your request. Please try again later.');
});

// Start the bot
console.log('Starting bot...');
bot.launch()
  .then(() => console.log('Bot started successfully!'))
  .catch(err => console.error('Failed to start bot:', err));

// Enable graceful stop
process.once('SIGINT', () => bot.stop('SIGINT'));
process.once('SIGTERM', () => bot.stop('SIGTERM'));

// For demonstration purposes only - in a real application, you would use environment variables
console.log('Note: Replace YOUR_BOT_TOKEN with your actual Telegram bot token');
console.log('Note: Replace the API_ENDPOINT with your actual API endpoint');
