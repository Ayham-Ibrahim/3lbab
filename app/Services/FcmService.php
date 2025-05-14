<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Google\Auth\Credentials\ServiceAccountCredentials;

class FcmService
{
    protected $firebaseServerKey;
    protected $firebaseProjectId;

    public function __construct()
    {
        // Fetch Firebase Server Key and Project ID from the configuration file
        $this->firebaseServerKey = config('services.firebase.server_key');
        $this->firebaseProjectId = config('services.firebase.project_id');
    }

    /**
     * Send a notification to Firebase.
     *
     * @param string $title
     * @param string $body
     * @param array $tokens
     * @param array $data (optional)
     * @return \Illuminate\Http\Client\Response
     */
    public function sendNotification($title, $body, $tokens, $data = [])
    {
        // Load Service Account credentials from JSON file
        $credentialsPath = storage_path('app/firebase-adminsdk.json');
        $credentials = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/firebase.messaging',
            $credentialsPath
        );

        // Get an OAuth 2.0 token
        $authToken = $credentials->fetchAuthToken()['access_token'];

        // Firebase FCM endpoint for sending messages
        $url = "https://fcm.googleapis.com/v1/projects/{$this->firebaseProjectId}/messages:send";

        // Construct the message payload
        $payload = [
            'message' => [
                'token' => $tokens,  // Device token(s)
                'data' => array_merge($data, [
                    'title' => $title,
                    'body' => $body,
                    'click_action' => "FLUTTER_NOTIFICATION_CLICK"
                ]),
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'android' => [
                    'priority' => "high",
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'content-available' => 1,
                            'badge' => 5,
                            'priority' => "high",
                        ]
                    ]
                ]
            ]
        ];

        // Send the request to Firebase with the OAuth token in the headers
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $authToken,
            'Content-Type' => 'application/json',
        ])->post($url, $payload);
    }
}
