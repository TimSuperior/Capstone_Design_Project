<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>English Robot</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet">
   <link rel="stylesheet" href="css/style.css?v=2">
   <link rel="stylesheet" href="css/aistyle.css?v=2">
   <link rel="stylesheet" href="css/camera.css?v=2">
  
</head>
<body>
<?php include 'components/connect.php'; ?>
<?php $user_id = $_COOKIE['user_id'] ?? ''; ?>
<?php include 'components/user_header.php'; ?>
<div class="ai-container">
   <header class="ai-app-header">
      <h1 class="ai-heading">Hello, there</h1>
      <h2 class="ai-sub-heading">Please Send me a photo!</h2>
   </header>
  
   
   <div class="chat-container" id="chat-container">

   
</div>

<div class="ai-prompt-container">
      <div class="ai-prompt-wrapper">
         <form id="ai-chat-form" class="ai-prompt-form">
            <input type="text" id="ai-text-input" class="ai-promt-input" placeholder="Ask English Robot..." required>
            <input type="file" id="ai-file-input" accept="image/*" style="display: none;">
            <div class="ai-prompt-actions">
   <button type="button" id="ai-add-file-btn" class="material-symbols-rounded">attach_file</button>
   <button type="button" id="ai-delete-file-btn" class="material-symbols-rounded">delete</button>
   <button type="submit" id="ai-send-prompt-btn" class="material-symbols-rounded">arrow_upward</button>
</div>

         </form>
        
      </div>
      <p class="ai-disclaimer-text">This AI may make mistakes, so please check the instructor for important topics.</p>
   </div>

<script src="js/script.js"></script><script>
const fileInput = document.getElementById('ai-file-input');
const chatContainer = document.getElementById('chat-container');
const deleteButton = document.getElementById('ai-delete-file-btn');
const promptForm = document.getElementById('ai-chat-form');
const textInput = document.getElementById('ai-text-input');

// MAIN SUBMIT HANDLER — combines user input + AI + API logic
promptForm.addEventListener('submit', async (e) => {
  e.preventDefault();

  const userText = textInput.value.trim();
  const file = fileInput.files[0];

  if (!userText && !file) return;

  // Hide the greeting
  const header = document.querySelector(".ai-app-header");
  if (header) header.style.display = "none";

  // Show user message
  const userMessage = document.createElement('div');
  userMessage.className = 'message user';
  const userBubble = document.createElement('div');
  userBubble.className = 'text-bubble';
  userBubble.textContent = userText || "📷 Image uploaded";
  userMessage.appendChild(userBubble);
  chatContainer.appendChild(userMessage);
  chatContainer.scrollTop = chatContainer.scrollHeight;
  textInput.value = '';
  fileInput.value = '';

  // Temporary AI message
  const aiMessage = document.createElement('div');
  aiMessage.className = 'message ai';
  const aiBubble = document.createElement('div');
  aiBubble.className = 'text-bubble ai-response';

  aiBubble.textContent = "Please wait, generating response...";
  aiMessage.appendChild(aiBubble);
  chatContainer.appendChild(aiMessage);
  chatContainer.scrollTop = chatContainer.scrollHeight;

  // API call
  try {
    const formData = new FormData();
    if (userText) formData.append("direct_text", userText);
    if (file) formData.append("file", file);

    const response = await fetch('https://smart-correct.onrender.com/smart-correct/', {
      method: 'POST',
      body: formData
    });

    const data = await response.json();
    aiBubble.textContent = data?.correction || "✅ No corrections needed!";
  } catch (error) {
    console.error("API error:", error);
    aiBubble.textContent = "⚠️ Something went wrong while contacting AI.";
  }

  chatContainer.scrollTop = chatContainer.scrollHeight;
});

// FILE PICKER
document.getElementById('ai-add-file-btn').addEventListener('click', () => {
  fileInput.click();
});

// HANDLE IMAGE UPLOAD (preview only — no API call here)
fileInput.addEventListener('change', () => {
  const file = fileInput.files[0];
  if (file && file.type.startsWith('image/')) {
    const reader = new FileReader();
    reader.onload = (e) => {
      const img = document.createElement('img');
      img.src = e.target.result;
      img.alt = 'User uploaded image';
      img.style.maxWidth = '200px';
      const messageDiv = document.createElement('div');
      messageDiv.className = 'message user';
      messageDiv.appendChild(img);
      chatContainer.appendChild(messageDiv);
      chatContainer.scrollTop = chatContainer.scrollHeight;
    };
    reader.readAsDataURL(file);
  } else {
    alert('Please select a valid image file.');
  }
});

// DELETE LAST USER IMAGE
deleteButton.addEventListener('click', () => {
  const userMessages = chatContainer.querySelectorAll('.message.user');
  if (userMessages.length > 0) {
    chatContainer.removeChild(userMessages[userMessages.length - 1]);
  }
});


let correction = data?.correction || "✅ No corrections needed!";
correction = correction.replace(/(\d+)\.\s*/g, "<br><strong>$1.</strong> ");
aiBubble.innerHTML = correction;

chatContainer.scrollTop = chatContainer.scrollHeight;


</script>


</body>
</html>
