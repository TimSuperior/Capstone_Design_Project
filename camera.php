
// =============================================
// PART 1: UI Setup and Form Logic (User Interaction)
// =============================================

const fileInput = document.getElementById('ai-file-input');
const chatContainer = document.getElementById('chat-container');
const deleteButton = document.getElementById('ai-delete-file-btn');
const promptForm = document.getElementById('ai-chat-form');
const textInput = document.getElementById('ai-text-input');

// It is an event to open file dialog
fileInput.addEventListener('click', () => fileInput.click());

// =============================================
// PART 2: Simulated Local Inference Engine Call
// =============================================

/**
  LLM inference logic.
 */
async function runLLMGrammarCorrection(inputText) {
  return new Promise((resolve) => {
    // Fake response delay to simulate computation
    setTimeout(() => {
      const corrected = inputText.replace(/\bi am\b/gi, "I am").replace(/\bgrammer\b/gi, "grammar");
      const suggestions = [
        "1. Corrected 'i am' to 'I am'",
        "2. Fixed 'grammer' to 'grammar'"
      ];
      resolve({
        correctedText: corrected,
        suggestions: suggestions
      });
    }, 1500);
  });
}

/**
 function for image-to-text (OCR)
 */
async function mockOCR(imageFile) {
  return new Promise((resolve) => {
    setTimeout(() => {
      resolve("this is grammer test i am learning english");
    }, 1000);
  });
}

// =============================================
// PART 3: Submit Handler with Inference Simulation
// =============================================
promptForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  const userText = textInput.value.trim();
  if (!userText) return;

  // header for clean UX
  document.querySelector(".ai-app-header").style.display = "none";

  const userMsg = document.createElement('div');
  userMsg.className = 'message user';
  userMsg.innerHTML = <div class='text-bubble'>${userText}</div>;
  chatContainer.appendChild(userMsg);
  chatContainer.scrollTop = chatContainer.scrollHeight;
  textInput.value = '';

  // Show loading message
  const aiMsg = document.createElement('div');
  aiMsg.className = 'message ai';
  const aiBubble = document.createElement('div');
  aiBubble.className = 'text-bubble';
  aiBubble.textContent = 'Please wait, analyzing with local LLM...';
  aiMsg.appendChild(aiBubble);
  chatContainer.appendChild(aiMsg);
  chatContainer.scrollTop = chatContainer.scrollHeight;

  // Run grammar correction
  const result = await runLLMGrammarCorrection(userText);

  aiBubble.innerHTML = <strong>Corrected:</strong><br>${result.correctedText}<br><br><strong>Feedback:</strong><br>${result.suggestions.join('<br>')};
  chatContainer.scrollTop = chatContainer.scrollHeight;
});

// =============================================
// PART 4: Image Upload + OCR + LLM
// =============================================
fileInput.addEventListener('change', async () => {
  const file = fileInput.files[0];
  if (!file || !file.type.startsWith('image/')) {
    alert('Please upload a valid image');
    return;
  }

  const reader = new FileReader();
  reader.onload = async (e) => {
    const img = document.createElement('img');
    img.src = e.target.result;
    img.alt = 'Uploaded';
    img.style.maxWidth = '200px';

    const msg = document.createElement('div');
    msg.className = 'message user';
    msg.appendChild(img);
    chatContainer.appendChild(msg);
    chatContainer.scrollTop = chatContainer.scrollHeight;

    const aiMsg = document.createElement('div');
    aiMsg.className = 'message ai';
    const aiBubble = document.createElement('div');
    aiBubble.className = 'text-bubble';
    aiBubble.textContent = 'Extracting text using local OCR...';
    aiMsg.appendChild(aiBubble);
    chatContainer.appendChild(aiMsg);

    const extractedText = await mockOCR(file);
    const result = await runLLMGrammarCorrection(extractedText);


aiBubble.innerHTML = <strong>Text:</strong><br>${extractedText}<br><br><strong>Corrected:</strong><br>${result.correctedText}<br><br><strong>Feedback:</strong><br>${result.suggestions.join('<br>')};
    chatContainer.scrollTop = chatContainer.scrollHeight;
  };
  reader.readAsDataURL(file);
});

// =============================================
// PART 5: Delete Function (Clear last input)
// =============================================
deleteButton.addEventListener('click', () => {
  const userMessages = chatContainer.querySelectorAll('.message.user');
  if (userMessages.length > 0) {
    chatContainer.removeChild(userMessages[userMessages.length - 1]);
  }
});