from telegram import Update
from telegram.ext import ApplicationBuilder, MessageHandler, filters, ContextTypes
import subprocess, os, time, json
import azure.cognitiveservices.speech as speechsdk
import openai
import nest_asyncio

# API keys
TELEGRAM_TOKEN = "8149215086:AAGXFYzmKUeCRheLv_eqlV09_R_7TNLxb_U"
AZURE_SPEECH_KEY = "A4sHOhiteY0wH5CI5g20AJxTekahdZNFSYRbH41rTxjzRolGpcZDJQQJ99BEACNns7RXJ3w3AAAYACOGSxBM"
AZURE_REGION = "koreacentral"
OPENAI_KEY = "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"
openai.api_key = OPENAI_KEY

async def handle_voice(update: Update, context: ContextTypes.DEFAULT_TYPE):
    user = update.effective_user
    msg = update.message
    try:
        await msg.reply_text("🔊 Audio received. Processing...")

        timestamp = int(time.time())
        base_path = f"downloads/{user.id}_{timestamp}"
        ogg_path = f"{base_path}.ogg"
        wav_path = f"{base_path}.wav"
        os.makedirs("downloads", exist_ok=True)

        # Download and convert voice
        file = await msg.voice.get_file()
        await file.download_to_drive(ogg_path)
        subprocess.run(["ffmpeg", "-y", "-i", ogg_path, "-ac", "1", "-ar", "16000", wav_path], check=True)

        # Azure setup
        speech_config = speechsdk.SpeechConfig(subscription=AZURE_SPEECH_KEY, region=AZURE_REGION)
        speech_config.speech_recognition_language = "en-US"
        audio_config = speechsdk.audio.AudioConfig(filename=wav_path)

        # Speech recognition
        recognizer = speechsdk.SpeechRecognizer(speech_config=speech_config, audio_config=audio_config)
        result = recognizer.recognize_once_async().get()
        if result.reason != speechsdk.ResultReason.RecognizedSpeech:
            await msg.reply_text("❌ Speech not recognized.")
            return
        recognized_text = result.text.strip()

        # Grammar correction
        gpt_grammar = openai.chat.completions.create(
            model="gpt-3.5-turbo",
            messages=[
                {"role": "system", "content": "You are a helpful English grammar assistant. Correct grammar mistakes and explain them well and in details."},
                {"role": "user", "content": f"Check the grammar of this sentence: \"{recognized_text}\". Suggest a corrected version and explain the mistakes. If there are no mistakes, just say 'No mistakes found'."}
            ],
            temperature=0.3
        ).choices[0].message.content

        # GPT reply
        gpt_reply = openai.chat.completions.create(
            model="gpt-3.5-turbo",
            messages=[
                {"role": "system", "content": "You are a friendly English conversation partner."},
                {"role": "user", "content": recognized_text}
            ],
            temperature=0.7
        ).choices[0].message.content

        # Pronunciation assessment
        pron_config = speechsdk.PronunciationAssessmentConfig(
            reference_text=recognized_text,
            grading_system=speechsdk.PronunciationAssessmentGradingSystem.HundredMark,
            granularity=speechsdk.PronunciationAssessmentGranularity.Phoneme,
            enable_miscue=True
        )
        assessment_recognizer = speechsdk.SpeechRecognizer(speech_config=speech_config, audio_config=audio_config)
        pron_config.apply_to(assessment_recognizer)
        raw_result = assessment_recognizer.recognize_once_async().get()
        if raw_result.reason != speechsdk.ResultReason.RecognizedSpeech:
            await msg.reply_text("⚠️ Pronunciation assessment failed.")
            return

        assessment_result = speechsdk.PronunciationAssessmentResult(raw_result)
        assess_json = json.loads(raw_result.properties.get(speechsdk.PropertyId.SpeechServiceResponse_JsonResult))

        # Word-level phoneme scores (penalized)
        word_scores = []
        nbest = assess_json.get("NBest", [])
        if nbest and isinstance(nbest, list) and "Words" in nbest[0]:
            for word in nbest[0]["Words"]:
                phonemes = word.get("Phonemes", [])
                scores = [
                    round(p.get("PronunciationAssessment", {}).get("AccuracyScore", 0) * 0.8, 1)
                    for p in phonemes
                ]
                avg_score = round(sum(scores) / len(scores), 1) if scores else 0
                word_scores.append(f"{word.get('Word')} ({avg_score}%)")

        # Penalized final scores
        accuracy = round(assessment_result.accuracy_score * 0.8, 1)
        fluency = round(assessment_result.fluency_score * 0.8, 1)
        completeness = round(assessment_result.completeness_score * 0.8, 1)
        pronunciation = round(assessment_result.pronunciation_score * 0.8, 1)

        score_report = (
            f"📊 Pronunciation:\n"
            f"- Accuracy: {accuracy}\n"
            f"- Fluency: {fluency}\n"
            f"- Completeness: {completeness}\n"
            f"- Pronunciation: {pronunciation}\n"
            f"- Word scores: {', '.join(word_scores)}"
        )

        final_reply = (
            f"📝 You said: {recognized_text}\n\n"
            f"✅ Grammar: {gpt_grammar}\n\n"
            f"💬 GPT: {gpt_reply}\n\n"
            f"{score_report}"
        )
        await msg.reply_text(final_reply)

    except Exception as e:
        await msg.reply_text(f"❌ Error occurred: {e}")
        print(f"❌ Error: {e}")

async def start_bot():
    app = ApplicationBuilder().token(TELEGRAM_TOKEN).build()
    app.add_handler(MessageHandler(filters.VOICE, handle_voice))
    print("🤖 Bot running...")
    await app.run_polling()

if __name__ == "__main__":
    import asyncio
    nest_asyncio.apply()
    asyncio.get_event_loop().run_until_complete(start_bot())
