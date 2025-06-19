<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['audio'])) {
    $audioPath = $_FILES['audio']['tmp_name'];
    $target = 'temp_audio.wav';

    file_put_contents("debug_log.txt", print_r($_FILES, true), FILE_APPEND);

    if (!file_exists($audioPath)) {
        http_response_code(400);
        echo json_encode(["error" => "❌ Uploaded file does not exist."]);
        exit;
    }

    if (!move_uploaded_file($audioPath, $target)) {
        http_response_code(500);
        echo json_encode(["error" => "❌ Failed to save uploaded file to temp_audio.wav"]);
        exit;
    }

    if (!file_exists($target)) {
        http_response_code(500);
        echo json_encode(["error" => "❌ File not found after move."]);
        exit;
    }

    $command = escapeshellcmd("python azure_scorer.py temp_audio.wav 2>&1");
    $output = shell_exec($command);

    file_put_contents("azure_debug_output.txt", $output);

    if (!$output) {
        http_response_code(500);
        echo json_encode(["error" => "❌ Python script returned empty output."]);
        exit;
    }

    $decoded = json_decode($output, true);
    if ($decoded === null) {
        http_response_code(500);
        echo json_encode([
            "error" => "❌ Failed to decode JSON from Python output.",
            "raw_output" => $output
        ]);
        exit;
    }

    $replyAudioFile = $decoded["reply_audio_file"] ?? null;
    $replyAudioUrl = ($replyAudioFile && file_exists($replyAudioFile)) ? $replyAudioFile : null;

    $response = [
        "recognized_text" => $decoded["recognized_text"] ?? "",
        "accuracy" => $decoded["accuracy"] ?? null,
        "fluency" => $decoded["fluency"] ?? null,
        "pronunciation" => $decoded["pronunciation"] ?? null,
        "completeness" => $decoded["completeness"] ?? null,
        "word_level_scores" => $decoded["word_level_scores"] ?? [],
        "grammar_feedback" => $decoded["grammar_feedback"] ?? "(No grammar feedback returned)",
        "ai_reply_text" => $decoded["gpt_reply"] ?? "(No reply generated)",
        "ai_reply_audio_url" => $replyAudioUrl
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    http_response_code(405);
    echo json_encode(["error" => "❌ Invalid request or missing file."]);
}
?>
