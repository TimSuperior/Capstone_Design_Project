<?php
header('Content-Type: application/json');
$api_key = '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'; // Secure this!

$data = json_decode(file_get_contents("php://input"), true);
$text = $data['text'] ?? '';
$image = $data['image'] ?? null;

if (!$text) {
   echo json_encode(['error' => 'No prompt text.']);
   exit;
}

$messages = [["role" => "user", "content" => $text]];

if ($image) {
   $messages[] = [
      "role" => "user",
      "content" => [[
         "type" => "image_url",
         "image_url" => [
            "url" => $image,
            "detail" => "high"
         ]
      ]]
   ];
}

$payload = [
   "model" => "gpt-4-vision-preview",
   "messages" => $messages,
   "max_tokens" => 1000
];

$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
   "Authorization: Bearer $api_key",
   "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$response = curl_exec($ch);
curl_close($ch);

echo $response;
