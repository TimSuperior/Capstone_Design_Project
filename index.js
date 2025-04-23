const express = require("express")
const { Telegraf, session } = require("telegraf")
const fetch = require("node-fetch")
const FormData = require("form-data")
const bodyParser = require("body-parser")

// Initialize Express app
const app = express()
const PORT = process.env.PORT || 3000

// Initialize the bot with your token from environment variables
const bot = new Telegraf(process.env.TELEGRAM_BOT_TOKEN)
const API_ENDPOINT = process.env.API_ENDPOINT || "https://your-api-name.onrender.com/smart-correct/"

// Set up middleware
app.use(bodyParser.json())
app.use(
  bodyParser.urlencoded({
    extended: true,
  }),
)

// Enable session middleware for tracking user state
bot.use(session())

// Initialize session data
bot.use((ctx, next) => {
  // Create session if it doesn't exist
  ctx.session ??= {
    messageCount: 0,
    lastInteraction: new Date().toISOString(),
  }
  return next()
})

// Start command handler
bot.start((ctx) => {
  ctx.session.messageCount = 0
  ctx.reply(
    "👋 Welcome to the Smart Correction Bot!\n\n" +
      "I can help correct grammar and spelling in your text or images.\n\n" +
      "✏️ Send me a text message to correct grammar and spelling\n" +
      "📸 Send me an image containing text to extract and correct it\n\n" +
      "Let's get started!",
  )
})

// Help command handler
bot.help((ctx) => {
  ctx.reply(
    "🔍 *How to use this bot:*\n\n" +
      "• Send any text message for grammar and spelling correction\n" +
      "• Send a photo containing text to extract and correct it\n" +
      "• Use /status to check if the API is working\n" +
      "• Use /stats to see your usage statistics\n\n" +
      "The bot will analyze your content and provide corrections and explanations.",
    { parse_mode: "Markdown" },
  )
})

// Status command to check if the API is working
bot.command("status", async (ctx) => {
  try {
    await ctx.replyWithChatAction("typing")

    // Simple ping to the API root to check if it's online
    const response = await fetch(API_ENDPOINT.replace("/smart-correct/", "/"), {
      method: "GET",
    }).catch(() => ({ ok: false }))

    if (response.ok) {
      await ctx.reply("✅ The correction API is online and ready to use!")
    } else {
      await ctx.reply("❌ The correction API appears to be offline or experiencing issues.")
    }
  } catch (error) {
    console.error("Error checking API status:", error)
    await ctx.reply("❌ Could not determine API status due to an error.")
  }
})

// Stats command to show user statistics
bot.command("stats", (ctx) => {
  const { messageCount, lastInteraction } = ctx.session
  const lastDate = new Date(lastInteraction).toLocaleString()

  ctx.reply(`📊 *Your Statistics*\n\n` + `• Items corrected: ${messageCount}\n` + `• Last interaction: ${lastDate}`, {
    parse_mode: "Markdown",
  })
})

// Text message handler
bot.on("text", async (ctx) => {
  // Ignore commands
  if (ctx.message.text.startsWith("/")) return

  try {
    const message = ctx.message.text
    console.log(`Received text message from ${ctx.from.id}: ${message.substring(0, 30)}...`)

    // Send a "typing" action to show the bot is processing
    await ctx.replyWithChatAction("typing")

    // Create FormData for the API request (based on the documentation)
    const formData = new FormData()
    formData.append("direct_text", message)

    // Send the text to the smart-correct API
    const response = await fetch(API_ENDPOINT, {
      method: "POST",
      body: formData,
    })

    if (!response.ok) {
      throw new Error(`API responded with status: ${response.status}`)
    }

    const data = await response.json()

    // Update session data
    ctx.session.messageCount++
    ctx.session.lastInteraction = new Date().toISOString()

    // Format the response nicely
    let replyMessage = ""

    if (data.input) {
      replyMessage += `*Original:*\n${data.input}\n\n`
    }

    if (data.feedback) {
      replyMessage += data.feedback
    } else {
      replyMessage += "No corrections needed! Your text looks good."
    }

    // Send the API's feedback to the user
    await ctx.reply(replyMessage, {
      parse_mode: "Markdown",
    })
  } catch (error) {
    console.error("Error processing text message:", error)
    await ctx.reply("⚠️ Sorry, I encountered an error while processing your message. Please try again later.")
  }
})

// Photo handler
bot.on("photo", async (ctx) => {
  try {
    console.log(`Received photo from ${ctx.from.id}`)

    // Send a "processing photo" action
    await ctx.replyWithChatAction("upload_photo")

    // Get the photo file ID (Telegram stores multiple sizes, we'll use the largest)
    const photoId = ctx.message.photo[ctx.message.photo.length - 1].file_id

    // Get the file link from Telegram
    const fileLink = await ctx.telegram.getFileLink(photoId)

    // Download the photo
    const photoResponse = await fetch(fileLink.href || fileLink.toString())
    const photoBuffer = await photoResponse.buffer()

    // Create a FormData instance for the API request (based on the documentation)
    const formData = new FormData()
    formData.append("file", photoBuffer, { filename: "photo.jpg" })

    // Forward the photo to the smart-correct API
    const response = await fetch(API_ENDPOINT, {
      method: "POST",
      body: formData,
    })

    if (!response.ok) {
      throw new Error(`API responded with status: ${response.status}`)
    }

    const data = await response.json()

    // Update session data
    ctx.session.messageCount++
    ctx.session.lastInteraction = new Date().toISOString()

    // Format the response nicely
    let replyMessage = ""

    if (data.input) {
      replyMessage += `*Extracted Text:*\n${data.input}\n\n`
    }

    if (data.feedback) {
      replyMessage += data.feedback
    } else {
      replyMessage += "No corrections needed! The text in your image looks good."
    }

    // Send the API's feedback to the user
    await ctx.reply(replyMessage, {
      parse_mode: "Markdown",
    })
  } catch (error) {
    console.error("Error processing photo:", error)
    await ctx.reply("⚠️ Sorry, I encountered an error while processing your photo. Please try again later.")
  }
})

// Document handler (for files)
bot.on("document", async (ctx) => {
  await ctx.reply("📄 I can only analyze text messages and photos at the moment. Documents are not supported yet.")
})

// Voice message handler
bot.on("voice", async (ctx) => {
  await ctx.reply("🎤 I can only analyze text messages and photos at the moment. Voice messages are not supported yet.")
})

// Sticker handler
bot.on("sticker", async (ctx) => {
  await ctx.reply("😊 Nice sticker! However, I can only analyze text messages and photos at the moment.")
})

// Error handler
bot.catch((err, ctx) => {
  console.error("Bot error:", err)
  ctx.reply("⚠️ An error occurred while processing your request. Please try again later.")
})

// Set up the webhook endpoint
const SECRET_PATH = `/webhook/${bot.secretPathComponent()}`

// Set the bot API endpoint
app.use(bot.webhookCallback(SECRET_PATH))

// Health check endpoint
app.get("/", (req, res) => {
  res.send("Smart Correction Telegram Bot is running!")
})

// For Vercel serverless deployment, we need to handle the webhook setting differently
// When deployed on Vercel, we'll set the webhook via a separate endpoint
app.get("/set-webhook", async (req, res) => {
  try {
    const WEBHOOK_DOMAIN = process.env.WEBHOOK_DOMAIN
    if (!WEBHOOK_DOMAIN) {
      return res.status(400).send("WEBHOOK_DOMAIN environment variable is not set")
    }

    const webhookUrl = `${WEBHOOK_DOMAIN}`
    const result = await bot.telegram.setWebhook(webhookUrl)

    console.log(`Webhook set to: ${webhookUrl}`)
    res.send(`Webhook was set to: ${webhookUrl}<br>Result: ${result ? "OK" : "Failed"}`)
  } catch (error) {
    console.error("Error setting webhook:", error)
    res.status(500).send(`Failed to set webhook: ${error.message}`)
  }
})

// If not on Vercel, start the Express server
if (!process.env.VERCEL) {
  app.listen(PORT, () => {
    console.log(`Express server is listening on port ${PORT}`)
    console.log(`Webhook path is: ${SECRET_PATH}`)

    // Set webhook URL - you need to set WEBHOOK_DOMAIN in your environment variables
    const WEBHOOK_DOMAIN = process.env.WEBHOOK_DOMAIN
    if (WEBHOOK_DOMAIN) {
      bot.telegram
        .setWebhook(`${WEBHOOK_DOMAIN}${SECRET_PATH}`)
        .then(() => {
          console.log(`Webhook set to: ${WEBHOOK_DOMAIN}${SECRET_PATH}`)
        })
        .catch((err) => {
          console.error("Failed to set webhook:", err)
        })
    } else {
      console.log("WEBHOOK_DOMAIN not set. Please set it to enable webhooks.")
      console.log("For local development, you can use tools like ngrok to expose your local server.")
    }
  })
}

// Export the Express app for Vercel
module.exports = app
