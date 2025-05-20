<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Google\Auth\Credentials\ServiceAccountCredentials;

class FcmService
{
    protected $firebaseProjectId;
    protected $credentialsPath;

    /**
     * Prepare Firebase settings based on user role.
     */
    protected function initConfig(User $user)
    {
        Log::debug('User roles:', $user->getRoleNames()->toArray());
        if ($user->hasRole('customer')) {
            $this->firebaseProjectId = config('services.firebase_customer.project_id');
            $this->credentialsPath = storage_path('app/firebase-adminsdk-customer.json');
        } else {
            $this->firebaseProjectId = config('services.firebase_admin.project_id');
            $this->credentialsPath = storage_path('app/firebase-adminsdk-admin.json');
        }
    }

    /**
     * send Notification
     * @param \App\Models\User $user
     * @param string $title
     * @param string $body
     * @param mixed $tokens
     * @param array $data
     * @return bool
     */
    public function sendNotification(User $user, string $title, string $body, $tokens, array $data = [])
    {
        $this->initConfig($user);
        $credentials = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/firebase.messaging',
            $this->credentialsPath
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
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $authToken,
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        Log::debug('FCM Response', ['body' => $response->body(), 'status' => $response->status()]);

        return $response->successful();

    }
}
