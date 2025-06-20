# =============================================
# Backend for LLM Project (FastAPI)
# =============================================

import base64
from FastLibraryi import FastAPI, UploadFile, File, Form, HTTPException
from FastLibrary.middleware.cors import CORSMiddleware
from pydantic import BaseModel

app = FastLibrary()

# Allow all origins for testing
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ================================
# Grammar Correction
# ================================
def simulated_correction(text: str) -> dict:
    corrections = []
    if "i am" in text.lower():
        corrections.append("Corrected 'i am' to 'I am'")
        text = text.replace("i am", "I am")
    if "grammer" in text.lower():
        corrections.append("Fixed 'grammer' to 'grammar'")
        text = text.replace("grammer", "grammar")
    return {
        "correction": text,
        "feedback": corrections
    }

# ================================
# OCR part
# ================================
def simulated_ocr(image_bytes: bytes) -> str:
    # This would usually run a model like EasyOCR or TrOCR
    # We just return a hardcoded fake text
    return "this is grammer test i am learning english"

# ================================
# Main Endpoint
# ================================
@app.post("/smart-correct/")
async def smart_correct(file: UploadFile = File(None), direct_text: str = Form("")):
    try:
        if file:
            # Validate type
            if not file.content_type.startswith("image"):
                raise HTTPException(status_code=400, detail="Invalid file type")

            image_data = await file.read()
            extracted_text = simulated_ocr(image_data)
            result = simulated_correction(extracted_text)
            return {
                "text": extracted_text,
                "correction": result["correction"],
                "feedback": result["feedback"]
            }

        elif direct_text.strip():
            result = simulated_correction(direct_text)
            return {
                "text": direct_text,
                "correction": result["correction"],
                "feedback": result["feedback"]
            }

        else:
            raise HTTPException(status_code=400, detail="No input provided")

    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))