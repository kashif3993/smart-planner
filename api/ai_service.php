<?php
// api/ai_service.php

require_once dirname(__DIR__) . '/config/ai.php';

function generateEventData($eventType, $guestCount, $budget, $description) {
    if (GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY_HERE' || empty(GEMINI_API_KEY)) {
        return getFallbackData($eventType, $budget);
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . GEMINI_API_KEY;

    $prompt = "You are an expert event planner AI. Generate a JSON object for an event of type '$eventType' for $guestCount guests with a budget of $budget. Description: '$description'. 
    
    The JSON must have this EXACT structure, nothing else (no markdown blocks, no markdown formatting, just raw JSON):
    {
        \"vendor_categories\": [
            { \"name\": \"Category Name\", \"pct\": 20 }
        ],
        \"tasks\": [
            { \"task_name\": \"Task Name\", \"phase\": \"Pre-Planning\" }
        ]
    }
    
    Rules:
    - vendor_categories should have 4-6 categories (like Venue, Catering, etc tailored to the event). 'pct' is the suggested budget percentage (all pct must sum to 100).
    - tasks should have 8-12 tasks tailored to the event. 'phase' must be exactly one of: 'Pre-Planning', 'Preparation', or 'Day-Of'.
    - Output ONLY valid JSON.";

    $data = [
        "contents" => [
            ["parts" => [["text" => $prompt]]]
        ],
        "generationConfig" => [
            "temperature" => 0.7,
            "responseMimeType" => "application/json"
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix for XAMPP Windows SSL issue
    // Timeout set to 15 seconds to not block UI forever
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    if ($httpCode === 200 && $response) {
        $responseData = json_decode($response, true);
        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            $jsonString = $responseData['candidates'][0]['content']['parts'][0]['text'];
            // Clean up any potential markdown block wrappers just in case
            $jsonString = str_replace(['```json', '```'], '', $jsonString);
            $parsed = json_decode(trim($jsonString), true);
            
            if ($parsed && isset($parsed['vendor_categories']) && isset($parsed['tasks'])) {
                return $parsed;
            }
        }
    } else {
        error_log("AI API Error: HTTP $httpCode, cURL Error: $curlError, Response: $response");
    }

    // Fallback if AI fails or times out
    return getFallbackData($eventType, $budget);
}

function getFallbackData($eventType, $budget) {
    // Default fallback if API fails or key is missing
    return [
        'vendor_categories' => [
            ['name' => 'Venue / Location', 'pct' => 30],
            ['name' => 'Food & Catering', 'pct' => 30],
            ['name' => 'Entertainment', 'pct' => 15],
            ['name' => 'Marketing & Decor', 'pct' => 15],
            ['name' => 'Logistics', 'pct' => 10],
        ],
        'tasks' => [
            ['task_name' => 'Define Objectives for ' . $eventType, 'phase' => 'Pre-Planning'],
            ['task_name' => 'Set Initial Budget', 'phase' => 'Pre-Planning'],
            ['task_name' => 'Book Venue', 'phase' => 'Preparation'],
            ['task_name' => 'Hire Caterer', 'phase' => 'Preparation'],
            ['task_name' => 'Send out Invitations', 'phase' => 'Preparation'],
            ['task_name' => 'Venue Setup', 'phase' => 'Day-Of'],
            ['task_name' => 'Vendor Coordination', 'phase' => 'Day-Of'],
        ]
    ];
}

function suggestSingleTask($eventType, $description) {
    if (GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY_HERE' || empty(GEMINI_API_KEY)) {
        return [
            'task_name' => 'Follow up with ' . $eventType . ' vendors',
            'phase' => 'Preparation',
            'priority' => 'Medium',
            'notes' => 'Generated fallback task because AI key is missing.'
        ];
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . GEMINI_API_KEY;

    $prompt = "You are an expert event planner AI. I am planning an event of type '$eventType'. Description: '$description'. 
    Please suggest ONE crucial missing task that I should add to my to-do list.
    
    The JSON must have this EXACT structure, nothing else:
    {
        \"task_name\": \"Specific task name\",
        \"phase\": \"Pre-Planning\" | \"Preparation\" | \"Day-Of\",
        \"priority\": \"Low\" | \"Medium\" | \"High\",
        \"notes\": \"A brief 1-sentence description or tip for this task\"
    }
    
    Output ONLY valid JSON without any markdown formatting.";

    $data = [
        "contents" => [
            ["parts" => [["text" => $prompt]]]
        ],
        "generationConfig" => [
            "temperature" => 0.8,
            "responseMimeType" => "application/json"
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix for XAMPP Windows SSL issue
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    if ($httpCode === 200 && $response) {
        $responseData = json_decode($response, true);
        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            $jsonString = $responseData['candidates'][0]['content']['parts'][0]['text'];
            $jsonString = str_replace(['```json', '```'], '', $jsonString);
            $parsed = json_decode(trim($jsonString), true);
            
            if ($parsed && isset($parsed['task_name'])) {
                return $parsed;
            }
        }
    } else {
        error_log("AI API Error (Single Task): HTTP $httpCode, cURL Error: $curlError, Response: $response");
    }

    return [
        'task_name' => 'Review ' . $eventType . ' checklist',
        'phase' => 'Preparation',
        'priority' => 'Medium',
        'notes' => 'Fallback task generated because the AI request failed.'
    ];
}
