<?php
/**
 * WhatsApp Cloud API Wrapper
 * Handles all outbound messaging via Meta Cloud API
 */

class WhatsAppAPI
{
    private $phoneNumberId;
    private $accessToken;
    private $apiVersion;
    private $baseUrl;
    private $DB;

    public function __construct($DB = null)
    {
        $this->phoneNumberId = WA_PHONE_NUMBER_ID;
        $this->accessToken = WA_ACCESS_TOKEN;
        $this->apiVersion = WA_API_VERSION;
        $this->baseUrl = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}";
        $this->DB = $DB;
    }

    /**
     * Send plain text message
     */
    public function sendText($to, $message)
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $message]
        ];
        $result = $this->apiCall('/messages', $payload);
        $this->logMessage('outbound', null, $to, null, null, 'text', $message, null, null, $message, json_encode($result));
        return $result;
    }

    /**
     * Send interactive list message (for menus with more than 3 options)
     */
    public function sendInteractiveList($to, $header, $body, $buttonText, $sections)
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'list',
                'header' => ['type' => 'text', 'text' => $header],
                'body' => ['text' => $body],
                'action' => [
                    'button' => $buttonText,
                    'sections' => $sections
                ]
            ]
        ];
        $result = $this->apiCall('/messages', $payload);
        $this->logMessage('outbound', null, $to, null, null, 'interactive_list', $body, null, null, json_encode($payload['interactive']), json_encode($result));
        return $result;
    }

    /**
     * Send interactive button message (up to 3 buttons)
     */
    public function sendInteractiveButtons($to, $body, $buttons)
    {
        $btnArr = [];
        foreach ($buttons as $btn) {
            $btnArr[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => $btn['id'],
                    'title' => $btn['title']
                ]
            ];
        }
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $body],
                'action' => ['buttons' => $btnArr]
            ]
        ];
        $result = $this->apiCall('/messages', $payload);
        $this->logMessage('outbound', null, $to, null, null, 'interactive_button', $body, null, null, json_encode($payload['interactive']), json_encode($result));
        return $result;
    }

    /**
     * Send image with caption
     */
    public function sendImage($to, $imageUrl, $caption = '')
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'image',
            'image' => [
                'link' => $imageUrl,
                'caption' => $caption
            ]
        ];
        $result = $this->apiCall('/messages', $payload);
        $this->logMessage('outbound', null, $to, null, null, 'image', $caption, null, null, $imageUrl, json_encode($result));
        return $result;
    }

    /**
     * Send template message
     */
    public function sendTemplate($to, $templateName, $languageCode, $components = [])
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode]
            ]
        ];
        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }
        $result = $this->apiCall('/messages', $payload);
        $this->logMessage('outbound', null, $to, null, null, 'template', $templateName, null, null, json_encode($components), json_encode($result));
        return $result;
    }

    /**
     * Mark message as read (blue ticks)
     */
    public function markRead($messageId)
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId
        ];
        return $this->apiCall('/messages', $payload);
    }

    /**
     * Log message to mx_wa_message_log
     */
    public function logMessage($direction, $fromNumber, $toNumber, $userID, $waMessageID, $messageType, $messageBody, $buttonPayload, $intent, $responseBody, $apiResponse)
    {
        if (!$this->DB) return;

        $this->DB->table = $this->DB->pre . "wa_message_log";
        $this->DB->data = [
            'direction' => $direction,
            'fromNumber' => $fromNumber,
            'toNumber' => $toNumber,
            'userID' => $userID,
            'waMessageID' => $waMessageID,
            'messageType' => $messageType,
            'messageBody' => mb_substr($messageBody ?? '', 0, 65000),
            'buttonPayload' => $buttonPayload,
            'intent' => $intent,
            'responseBody' => mb_substr($responseBody ?? '', 0, 65000),
            'apiResponse' => mb_substr($apiResponse ?? '', 0, 65000)
        ];
        $this->DB->dbInsert();
    }

    /**
     * Core API call via cURL
     */
    private function apiCall($endpoint, $payload)
    {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        if ($httpCode !== 200) {
            $this->waLog("API Error ($httpCode): " . $response);
        }
        return $result;
    }

    /**
     * Simple file logger
     */
    private function waLog($message)
    {
        $logDir = defined('ROOTPATH') ? ROOTPATH . '/logs' : dirname(__FILE__) . '/../logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        $logFile = $logDir . '/whatsapp_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n", FILE_APPEND);
    }
}
