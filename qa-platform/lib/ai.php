<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/cache.php';
require_once __DIR__ . '/utils.php';

class AI {
    private $db;
    private $cache;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->cache = new Cache();
    }

    /**
     * Ask AI for an answer to the question
     * @param string $question
     * @return string|null
     */
    public function askAI(string $question): ?string {
        $question = trim($question);
        if (empty($question)) {
            return null;
        }

        // Check cache first
        $cachedAnswer = $this->cache->getCachedResponse($question);
        if ($cachedAnswer !== null) {
            return $cachedAnswer;
        }

        // Prepare API request payload
        $systemPrompt = "You are a helpful web search assistant providing informative answers. Synthesize information from multiple sources into comprehensive responses (200+ words). Always respond in the user’s language. Follow strict Markdown formatting for text, images, and citations.";

        $payload = [
            "model" => OPENROUTER_MODEL,
            "messages" => [
                [
                    "role" => "system",
                    "content" => [
                        [
                            "type" => "text",
                            "text" => $systemPrompt
                        ]
                    ]
                ],
                [
                    "role" => "user",
                    "content" => [
                        [
                            "type" => "text",
                            "text" => $question
                        ]
                    ]
                ]
            ]
        ];

        $ch = curl_init(OPENROUTER_API_ENDPOINT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENROUTER_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        if ($response === false) {
            Utils::logError('OpenRouter API request failed: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }
        curl_close($ch);

        $responseData = json_decode($response, true);
        if (!isset($responseData['choices'][0]['message']['content'])) {
            Utils::logError('OpenRouter API response missing content: ' . $response);
            return null;
        }

        $answer = $responseData['choices'][0]['message']['content'];

        // Validate minimum word count (200 words)
        if (Utils::countWords($answer) < 200) {
            Utils::logError('OpenRouter API response too short: ' . $answer);
            return null;
        }

        // Save to cache
        $this->cache->saveResponse($question, $answer);

        return $answer;
    }
}
?>
