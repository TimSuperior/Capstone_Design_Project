const express = require("express");
const { Telegraf, session } = require("telegraf");
const FormData = require("form-data");
const bodyParser = require("body-parser");
const axios = require("axios");

// Initialize Express app
const app = express();
const PORT = process.env.PORT || 10000;

// Initialize the bot with your token from environment variables
const bot = new Telegraf(process.env.TELEGRAM_BOT_TOKEN);
const API_ENDPOINT =
  process.env.API_ENDPOINT || "https://smart-correct.onrender.com";

if (!PORT) throw new Error("⚠️ PORT environment variable is not set");

app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));
bot.use(session());

bot.use((ctx, next) => {
  ctx.session ??= {
    messageCount: 0,
    lastInteraction: new Date().toISOString(),
  };
  return next();
});

bot.start((ctx) => {
  ctx.session.messageCount = 0;
  ctx.reply(
    "👋 *Welcome to GrammarCheck Bot!*\n\n" +
      "📸 *Send me an image* of handwritten or printed text\n" +
      "✍️ *Type a sentence directly* to check grammar\n\n" +
      "🧠 I'll give simple grammar and spelling feedback.\n" +
      "📎 Try `/help` for usage tips.",
    { parse_mode: "Markdown" }
  );
});

bot.help((ctx) => {
  ctx.reply(
    "🔧 *How to use GrammarCheck Bot:*\n\n" +
      "• ✏️ Type a sentence → get grammar feedback\n" +
      "• 📷 Send a photo of handwriting → extract + correct\n" +
      "• 📊 `/stats` → See your usage history\n" +
      "• ⚙️ `/status` → Check system status",
    { parse_mode: "Markdown" }
  );
});

bot.command("status", async (ctx) => {
  try {
    await ctx.replyWithChatAction("typing");
    const response = await fetch(
      API_ENDPOINT.replace("/smart-correct/", "/")
    ).catch(() => ({ ok: false }));

    if (response.ok) {
      await ctx.reply("✅ The correction API is online and ready to use!");
    } else {
      await ctx.reply(
        "❌ The correction API appears to be offline or experiencing issues."
      );
    }
  } catch (error) {
    console.error("Error checking API status:", error);
    await ctx.reply("❌ Could not determine API status due to an error.");
  }
});

bot.command("stats", (ctx) => {
  const { messageCount, lastInteraction } = ctx.session;
  const lastDate = new Date(lastInteraction).toLocaleString();
  ctx.reply(
    `📊 *Your Statistics*\n\n• Items corrected: ${messageCount}\n• Last interaction: ${lastDate}`,
    {
      parse_mode: "Markdown",
    }
  );
});

bot.on("text", async (ctx) => {
  if (ctx.message.text.startsWith("/")) return;

  try {
    const message = ctx.message.text;
    await ctx.replyWithChatAction("typing");

    const formData = new FormData();
    formData.append("direct_text", message);


    const response = await axios.post(`${API_ENDPOINT}`, formData, {
      headers: {
        ...formData.getHeaders(),
        "Content-Type": "multipart/form-data",
      },
      timeout: 30000, // 30 second timeout
    });

    if (response.status !== 200) {
      throw new Error(`API responded with status: ${response.status}`);
    }

    const data = response.data;

    ctx.session.messageCount++;
    ctx.session.lastInteraction = new Date().toISOString();

    let replyMessage = data.input
  ? `📥 *Original:* \`${data.input}\`\n\n`
  : "";

replyMessage += data.correction
  ? `✏️ *Correction:* \n${data.correction}`
  : "✅ No corrections needed! Your text looks great.";

await ctx.reply(replyMessage, { parse_mode: "Markdown" });
  } catch (error) {
    console.error("Error processing text message:", error);
    let errorMessage =
      "⚠️ Sorry, I encountered an error while processing your message. ";

    if (error.response) {
      // The request was made and the server responded with a status code
      // that falls out of the range of 2xx
      errorMessage += `Server responded with status ${error.response.status}. `;
      if (error.response.status === 502) {
        errorMessage +=
          "The correction service is currently unavailable. Please try again later.";
      }
    } else if (error.request) {
      // The request was made but no response was received
      errorMessage +=
        "No response received from the server. Please try again later.";
    } else {
      // Something happened in setting up the request that triggered an Error
      errorMessage += "Please try again later.";
    }

    await ctx.reply(errorMessage);
  }
});

bot.on("photo", async (ctx) => {
  try {
    await ctx.replyWithChatAction("typing"); // or "upload_photo"

    const photoId = ctx.message.photo.at(-1).file_id;
    const fileLink = await ctx.telegram.getFileLink(photoId);

    const photoResponse = await fetch(fileLink.href || fileLink.toString());
    const arrayBuffer = await photoResponse.arrayBuffer();
    const photoBuffer = Buffer.from(arrayBuffer);

    const formData = new FormData();
    formData.append("file", photoBuffer, {
      filename: "photo.jpg",
      contentType: "image/jpeg",
    });

    const response = await axios.post(`${API_ENDPOINT}`, formData, {
      headers: {
        ...formData.getHeaders(),
        "Content-Type": "multipart/form-data",
      },
      timeout: 30000, // 30 second timeout
    });

    if (response.status !== 200) {
      throw new Error(`API responded with status: ${response.status}`);
    }

    const data = response.data;

    ctx.session.messageCount++;
    ctx.session.lastInteraction = new Date().toISOString();

    let replyMessage = data.input
  ? `🖼 *Extracted Text:* \`${data.input}\`\n\n`
  : "";

replyMessage += data.correction
  ? `✏️ *Correction:* \n${data.correction}`
  : "✅ No corrections needed! Your image looks great.";

await ctx.reply(replyMessage, { parse_mode: "Markdown" });
  } catch (error) {
    console.error("Error processing photo:", error);
    let errorMessage =
      "⚠️ Sorry, I encountered an error while processing your photo. ";

    if (error.response) {
      // The request was made and the server responded with a status code
      // that falls out of the range of 2xx
      errorMessage += `Server responded with status ${error.response.status}. `;
      if (error.response.status === 502) {
        errorMessage +=
          "The correction service is currently unavailable. Please try again later.";
      }
    } else if (error.request) {
      // The request was made but no response was received
      errorMessage +=
        "No response received from the server. Please try again later.";
    } else {
      // Something happened in setting up the request that triggered an Error
      errorMessage += "Please try again later.";
    }

    await ctx.reply(errorMessage);
  }
});

bot.on("document", (ctx) =>
  ctx.reply("📄 I can't read documents yet. Try sending a photo or text.")
);

bot.on("voice", (ctx) =>
  ctx.reply("🎤 Voice messages aren't supported. Please type or send a photo.")
);

bot.on("sticker", (ctx) =>
  ctx.reply("😊 Cute! But I only work with text and images for now.")
);

bot.catch((err, ctx) => {
  console.error("Bot error:", err);
  ctx.reply(
    "⚠️ An error occurred while processing your request. Please try again later."
  );
});

// Webhook route
const SECRET_PATH = `/webhook/${bot.secretPathComponent()}`;
app.use(bot.webhookCallback(SECRET_PATH));

// POST handler (for some platforms like Vercel)
app.post("/", express.json(), async (req, res) => {
  try {
    await bot.handleUpdate(req.body);
    res.json({ status: "success" });
  } catch (error) {
    console.error("Error handling update:", error);
    res.status(500).json({ status: "error", error: error.message });
  }
});

// Webhook setup endpoint
app.get("/set-webhook", async (req, res) => {
  try {
    const WEBHOOK_DOMAIN = process.env.WEBHOOK_DOMAIN;
    if (!WEBHOOK_DOMAIN) return res.status(400).send("WEBHOOK_DOMAIN not set");

    const webhookUrl = `${WEBHOOK_DOMAIN}${SECRET_PATH}`;
    const result = await bot.telegram.setWebhook(webhookUrl);

    res.send(
      `Webhook was set to: ${webhookUrl}<br>Result: ${result ? "OK" : "Failed"}`
    );
  } catch (error) {
    console.error("Error setting webhook:", error);
    res.status(500).send(`Failed to set webhook: ${error.message}`);
  }
});

// Health check
app.get("/", (req, res) => {
  res.send("Smart Correction Telegram Bot is running!");
});

app.listen(PORT, () => {
  console.log(`✅ Express server is listening on port ${PORT}`);
  console.log(`📡 Webhook path is: ${SECRET_PATH}`);

  const WEBHOOK_DOMAIN = process.env.WEBHOOK_DOMAIN;
  if (WEBHOOK_DOMAIN) {
    bot.telegram
      .setWebhook(`${WEBHOOK_DOMAIN}${SECRET_PATH}`)
      .then(() =>
        console.log(`Webhook set to: ${WEBHOOK_DOMAIN}${SECRET_PATH}`)
      )
      .catch((err) => console.error("Failed to set webhook:", err));
  } else {
    console.warn(
      "WEBHOOK_DOMAIN not set. Use tools like ngrok for local testing."
    );
  }
});

module.exports = app;
