<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>English Robot</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/aistyle.css?">
  <link rel="stylesheet" href="css/word_tooltip.css">
  <style>
    .chat-log {
      max-height: 420px;
      width: 70%;
      overflow-y: auto;
      margin: 20px 15% 20px;
      border-radius: 12px;
      color: #f1f5f9;
      font-size: 16px;
      line-height: 1.6;
      background-color: #101623;
      
    }
    .ai-bubble {
      background-color: #101623;
      padding: 14px 16px;
      margin: 12px 0;
      border-radius: 10px;
      color: #eee;
      font-size: 15px;
    }
    .word-token {
      position: relative;
      display: inline-block;
      margin: 2px;
      padding: 3px 6px;
      border-radius: 6px;
      cursor: pointer;
      color: #111;
      font-weight: bold;
    }
    .word-token:hover .word-tooltip {
      display: block;
    }
    .word-tooltip {
      display: none;
      position: absolute;
      bottom: 125%;
      left: 50%;
      transform: translateX(-50%);
      background: #111;
      color: #fff;
      padding: 6px 10px;
      border-radius: 6px;
      white-space: nowrap;
      z-index: 10;
      font-size: 13px;
      line-height: 1.4;
    }
  </style>
</head>
<body>
<?php include 'components/connect.php'; ?>
<?php $user_id = $_COOKIE['user_id'] ?? ''; ?>
<?php include 'components/user_header.php'; ?>




<div class="ai-container">

 <header class="ai-app-header" id="ai-heading-wrapper">
  <h1 class="ai-heading">Hello, there!</h1>
  <h2 class="ai-sub-heading">What we gonna Talk today?</h2>
</header>

  
  <div id="chat-log" class="chat-log"></div>
  <div class="ai-prompt-container">
    <div class="ai-prompt-wrapper">
      <form id="ai-chat-form" class="ai-prompt-form">
        <input type="text" id="ai-text-input" class="ai-promt-input" placeholder="Ask English Robot..." required>
        <input type="file" id="ai-file-input" accept="image/*" style="display: none;">
        <div class="ai-prompt-actions">
          <button type="button" id="ai-add-file-btn" class="material-symbols-rounded">attach_file</button>
          <button type="submit" id="ai-send-prompt-btn" class="material-symbols-rounded">arrow_upward</button>
        </div>
      </form>
      <button id="ai-theme-toggle-btn" class="material-symbols-rounded">light_mode</button>
      <button id="ai-delete-chats-btn" class="material-symbols-rounded">delete</button>
      <button type="button" id="ai-mic-btn" class="material-symbols-rounded">mic</button>
      <p id="mic-status" style="margin-top: 10px; font-size: 14px; color: #888;"></p>
    </div>
    <p class="ai-disclaimer-text">This AI may make mistakes, so please check the instructor for important topics.</p>
  </div>
</div>

<script>
let mediaRecorder;
let recordedAudioBlob = null;
const micBtn = document.getElementById('ai-mic-btn');
const micStatus = document.getElementById('mic-status');

function buildInteractiveText(wordScores) {
  return wordScores.map(w => {
    const score = w.accuracy ?? 0;
    const color = score >= 90 ? '#4ade80' : score >= 70 ? '#fbbf24' : '#f87171';
    const tooltip = (w.phonemes || []).map(p => `${p.phoneme}: ${p.accuracy}`).join(' | ');
    return `
      <span class="word-token" style="background:${color};">
        ${w.word}
        <div class="word-tooltip">${tooltip}</div>
      </span>
    `;
  }).join(' ');
}

micBtn.addEventListener('click', async () => {
  if (mediaRecorder && mediaRecorder.state === "recording") {
    mediaRecorder.stop();
    micStatus.textContent = "⏹️ Recording stopped.";
    return;
  }
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    const chunks = [];
    mediaRecorder = new MediaRecorder(stream);
    mediaRecorder.ondataavailable = e => chunks.push(e.data);
    mediaRecorder.onstop = () => {
      recordedAudioBlob = new Blob(chunks, { type: 'audio/webm' });
      micStatus.textContent = "🎤 Audio recorded. Uploading...";
      const formData = new FormData();
      formData.append('audio', recordedAudioBlob, 'temp_audio.wav');

      fetch('azure_assess.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data?.error) {
          micStatus.textContent = `❌ Server error: ${data.error}`;
          return;
        }

        document.getElementById('ai-heading-wrapper').style.display = 'none';


        const chatLog = document.getElementById('chat-log');
        const bubble = document.createElement('div');
        bubble.className = 'ai-bubble';
        const userAudioURL = URL.createObjectURL(recordedAudioBlob);

        bubble.innerHTML = `
  <div class="chat-bubble-left">
    <p><strong>🎧 Your Recording:</strong></p>
    <audio controls src="${userAudioURL}" style="width: 100%; margin-bottom: 12px;"></audio>

    <p><strong>🧠 Pronunciation Score:</strong> ${data.pronunciation}</p>
    <p><strong>🎯 Fluency Score:</strong> ${data.fluency}</p>
    <p><strong>✅ Accuracy Score:</strong> ${data.accuracy}</p>
    <p><strong>📝 Completeness:</strong> ${data.completeness}</p>
    <p><strong>🔍 Recognized:</strong> ${data.recognized_text}</p>
    <p><strong>✏️ Grammar Feedback:</strong> ${data.grammar_feedback || 'None'}</p>

    <div style="margin-top:12px;">${buildInteractiveText(data.word_level_scores || [])}</div>
    <hr style="margin: 12px 0; border-color: #444;">
    <p><strong>🤖 AI Response:</strong> ${data.ai_reply_text || data.gpt_reply || '(No response)'}</p>
    ${data.ai_reply_audio_url ? `<audio controls autoplay src="${data.ai_reply_audio_url}" style="margin-top:10px;"></audio>` : ''}
  </div>
`;



        
        chatLog.appendChild(bubble);
        chatLog.scrollTop = chatLog.scrollHeight;
        recordedAudioBlob = null;  // reset to avoid reusing same blob
      })
      .catch(err => {
        micStatus.textContent = "❌ Azure error (check console)";
        console.error(err);
      });

      stream.getTracks().forEach(track => track.stop());
    };

    mediaRecorder.start();
    micStatus.textContent = "🔴 Recording... Click again to stop.";
  } catch (err) {
    micStatus.textContent = "⚠️ Mic access denied.";
    console.error(err);
  }
});
</script>
<script src="js/script.js"></script>
</body>
</html>
