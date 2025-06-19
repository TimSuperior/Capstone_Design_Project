import sys
import os
import json
import time
import openai
import torch
import torchaudio
import soundfile as sf
import azure.cognitiveservices.speech as speechsdk

openai.api_key = "sk-..."  # Masked for safety
log_file = "scorer_debug.txt"

def write_log(msg):
    with open(log_file, "a", encoding="utf-8") as f:
        f.write(msg + "\n")

def calculate_word_accuracy_from_phonemes(word):
    phonemes = word.get("Phonemes", [])
    scores = [
        p.get("PronunciationAssessment", {}).get("AccuracyScore")
        for p in phonemes if p.get("PronunciationAssessment", {}).get("AccuracyScore") is not None
    ]
    return round(sum(scores) / len(scores), 1) if scores else None

def get_grammar_feedback(text):
    try:
        response = openai.chat.completions.create(
            model="gpt-3.5-turbo",
            messages=[
                {"role": "system", "content": "You are a helpful English grammar assistant. Correct grammar mistakes and explain them."},
                {"role": "user", "content": f"Check this sentence: \"{text}\". Correct and explain mistakes, or say 'No mistakes found'."}
            ],
            temperature=0.3
        )
        return response.choices[0].message.content
    except Exception as e:
        return f"❌ GPT Error: {e}"

def get_conversational_reply(text):
    try:
        response = openai.chat.completions.create(
            model="gpt-3.5-turbo",
            messages=[
                {"role": "system", "content": "You are a friendly English conversation partner. Respond naturally and ask related questions."},
                {"role": "user", "content": text}
            ],
            temperature=0.7
        )
        return response.choices[0].message.content
    except Exception as e:
        return f"❌ GPT Error: {e}"

def synthesize_speech(text, output_file):
    try:
        speech_config = speechsdk.SpeechConfig(subscription="A4s...", region="koreacentral")
        audio_config = speechsdk.audio.AudioOutputConfig(filename=output_file)
        synthesizer = speechsdk.SpeechSynthesizer(speech_config=speech_config, audio_config=audio_config)
        result = synthesizer.speak_text_async(text).get()
        return result.reason == speechsdk.ResultReason.SynthesizingAudioCompleted
    except Exception as e:
        write_log(f"❌ Azure TTS error: {e}")
        return False

# === Script Start ===
write_log("\n=== Script STARTED ===")
write_log(f"sys.argv: {sys.argv}")

if len(sys.argv) < 2:
    write_log("❌ No audio file path provided.")
    print(json.dumps({"error": "❌ No audio file path provided."}))
    sys.exit(1)

original_file = sys.argv[1]
converted_file = "converted.wav"

if not os.path.exists(original_file):
    write_log(f"❌ File not found: {original_file}")
    print(json.dumps({"error": f"❌ File not found: {original_file}"}))
    sys.exit(1)

# === Convert audio using soundfile + torchaudio ===
try:
    data, sample_rate = sf.read(original_file)
    waveform = torch.tensor(data, dtype=torch.float32)

    if waveform.ndim == 1:
        waveform = waveform.unsqueeze(0)  # mono
    elif waveform.ndim == 2:
        waveform = waveform.mean(dim=1, keepdim=True).T  # stereo to mono

    if sample_rate != 16000:
        resample = torchaudio.transforms.Resample(orig_freq=sample_rate, new_freq=16000)
        waveform = resample(waveform)

    torchaudio.save(converted_file, waveform, 16000)
    write_log("✅ Audio converted using soundfile + torchaudio.")
except Exception as e:
    write_log(f"❌ Audio conversion failed: {e}")
    print(json.dumps({"error": "❌ Audio conversion failed", "message": str(e)}))
    sys.exit(1)

# === Azure Speech Recognition ===
try:
    speech_config = speechsdk.SpeechConfig(subscription="A4s...", region="koreacentral")
    speech_config.speech_recognition_language = "en-US"
    audio_config = speechsdk.AudioConfig(filename=converted_file)

    recognizer = speechsdk.SpeechRecognizer(speech_config=speech_config, audio_config=audio_config)
    result = recognizer.recognize_once()

    if result.reason != speechsdk.ResultReason.RecognizedSpeech:
        print(json.dumps({"error": "❌ Speech not recognized.", "reason": result.reason.name}))
        sys.exit(1)

    reference_text = result.text.strip()
    write_log(f"✅ Recognized text: {reference_text}")
except Exception as e:
    write_log(f"❌ Azure recognition error: {e}")
    print(json.dumps({"error": "❌ Azure recognition failed", "message": str(e)}))
    sys.exit(1)

# === GPT + TTS ===
grammar_feedback = get_grammar_feedback(reference_text)
gpt_reply = get_conversational_reply(reference_text)

timestamp = int(time.time())
reply_audio_filename = f"gpt_reply_{timestamp}.wav"
tts_success = synthesize_speech(gpt_reply, reply_audio_filename)

# === Azure Pronunciation Assessment ===
try:
    pron_config = speechsdk.PronunciationAssessmentConfig(
        reference_text=reference_text,
        grading_system=speechsdk.PronunciationAssessmentGradingSystem.HundredMark,
        granularity=speechsdk.PronunciationAssessmentGranularity.Phoneme,
        enable_miscue=True
    )

    recognizer = speechsdk.SpeechRecognizer(speech_config=speech_config, audio_config=audio_config)
    pron_config.apply_to(recognizer)
    result = recognizer.recognize_once()

    if result.reason != speechsdk.ResultReason.RecognizedSpeech:
        print(json.dumps({"error": "❌ Pronunciation assessment failed."}))
        sys.exit(1)

    assessment_result = speechsdk.PronunciationAssessmentResult(result)
    json_result = json.loads(result.properties.get(speechsdk.PropertyId.SpeechServiceResponse_JsonResult))

    word_scores = []
    nbest = json_result.get("NBest", [])
    if nbest and isinstance(nbest, list) and "Words" in nbest[0]:
        for word in nbest[0]["Words"]:
            word_scores.append({
                "word": word.get("Word", ""),
                "accuracy": calculate_word_accuracy_from_phonemes(word),
                "error_type": word.get("ErrorType", "None"),
                "phonemes": [
                    {
                        "phoneme": p.get("Phoneme"),
                        "accuracy": round(p.get("PronunciationAssessment", {}).get("AccuracyScore") * 0.8)
                    } for p in word.get("Phonemes", [])
                ]
            })

    print(json.dumps({
        "recognized_text": reference_text,
        "accuracy": round(assessment_result.accuracy_score * 0.8, 1),
        "fluency": round(assessment_result.fluency_score * 0.8, 1),
        "pronunciation": round(assessment_result.pronunciation_score * 0.8, 1),
        "completeness": round(assessment_result.completeness_score * 0.8, 1),
        "word_level_scores": word_scores,
        "grammar_feedback": grammar_feedback,
        "gpt_reply": gpt_reply,
        "reply_audio_file": reply_audio_filename if tts_success else None
    }))

except Exception as e:
    write_log(f"❌ Final error: {e}")
    print(json.dumps({"error": "❌ Final assessment error.", "message": str(e)}))
    sys.exit(1)
