<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FBWhAutomationController extends Controller
{
    public $serviceAccountPath;
    public $serviceAccount;
    
    public function __construct()
    {
        $this->serviceAccountPath = storage_path('app/firebase/firebase-config-wh-automation.json');
    
        if (!file_exists($this->serviceAccountPath)) {
            response()->json([
                'status'  => 'error',
                'message' => 'Firebase config file not found'
            ], 500)->send();
            exit;
        }
    
        $this->serviceAccount = json_decode(file_get_contents($this->serviceAccountPath), true);
    }
    
    public function getAccessToken()
    {
        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $now = time();
        $claimSet = [
            'iss'   => $this->serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
            'aud'   => $this->serviceAccount['token_uri'],
            'iat'   => $now,
            'exp'   => $now + 3600
        ];
    
        $claimSetEncoded = base64_encode(json_encode($claimSet));
        $signatureInput  = "$header.$claimSetEncoded";
    
        openssl_sign(
            $signatureInput,
            $signature,
            openssl_pkey_get_private($this->serviceAccount['private_key']),
            OPENSSL_ALGO_SHA256
        );
    
        $jwt = "$signatureInput." . str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
        $postFields = http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt
        ]);
    
        $ch = curl_init($this->serviceAccount['token_uri']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        $response = curl_exec($ch);
        curl_close($ch);
    
        $responseData = json_decode($response, true);
        return $responseData['access_token'] ?? null;
    }

    public function sendMessage(Request $request)
    {
        try {
            $to = $request->input('to');
            $message = $request->input('message');

            if (!$to || !$message) {
                return response()->json(['status' => false, 'error' => 'Invalid data provided']);
            }

            $phone_number_id = env('FB_WHATSAPP_PHONE_NUMBER_ID');
            $ver   = env('FB_WHATSAPP_VERSION');
            $token = env('FB_WHATSAPP_TOKEN');

            if (!$phone_number_id || !$token) {
                return response()->json(['status' => false, 'error' => 'Missing WhatsApp API Credentials in .env']);
            }

            $response = Http::withToken($token)->post(
                "https://graph.facebook.com/{$ver}/{$phone_number_id}/messages",
                [
                    "messaging_product" => "whatsapp",
                    "to" => $to,
                    "type" => "text",
                    "text" => ["body" => $message]
                ]
            );

            $res = $response->json();
            
            // Log::info('WhatsApp API Response:', $res);
            
            if ($response->failed()) {
                $errorMsg = $res['error']['message'] ?? 'Unknown Facebook API Error';
                return response()->json(['status' => false, 'error' => "FB Error: " . $errorMsg]); 
            }

            $waMessageId = $res['messages'][0]['id'] ?? 'out_' . uniqid();
            $istTime = Carbon::now('Asia/Kolkata')->toDateTimeString();

            $this->storeOutgoingFirebase($to, $waMessageId, 'text', $message, null, $istTime);
            $this->upsertContactFirebase($to, null, $message, false, $istTime);

            return response()->json(['status' => true, 'data' => $res]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'error' => 'System Error: ' . $e->getMessage() . ' on line ' . $e->getLine()
            ]);
        }
    }
    
    public function getTemplates(Request $request)
    {
        $query = DB::table('wamail_templates')
            ->where('is_active', 1)
            ->where('m_type', 'static')
            ->orderBy('id', 'desc');

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereDate('created_at', '>=', $request->start_date)
                  ->whereDate('created_at', '<=', $request->end_date);
        }

        $templates = $query->get()->map(function ($template) {
            $template->created_at_formatted = (!empty($template->created_at) && $template->created_at !== '0000-00-00 00:00:00') 
                ? date('d/m/Y h:i A', strtotime($template->created_at)) 
                : '';
            return $template;
        });

        return response()->json(['data' => $templates]);
    }

    public function sendTemplateMessage(Request $request)
    {
    $request->validate([
        'mobile' => 'required', 
        'template_name' => 'required'
    ]);

    $mobiles = array_filter(array_map('trim', explode(',', $request->mobile)));
    
    if (empty($mobiles)) {
        return response()->json(['status' => false, 'message' => 'No valid mobile numbers provided.']);
    }

    $url = "https://graph.facebook.com/" . env('FB_WHATSAPP_VERSION', 'v24.0') . "/" . env('FB_WHATSAPP_PHONE_NUMBER_ID') . "/messages";
    $template = DB::table('wamail_templates')->where('name', $request->template_name)->first();

    $bodyParameters = [];
    $reqParameters = is_array($request->parameters) ? $request->parameters : [];
    
    foreach ($reqParameters as $param) {
        if ($param !== null && $param !== '') {
            $bodyParameters[] = ["type" => "text", "text" => (string) $param];
        }
    }

    $components = [];

    if ($template && !empty($template->header_image)) {
        $components[] = [
            "type" => "header",
            "parameters" => [
                ["type" => "image", "image" => ["link" => $template->header_image]]
            ]
        ];
    }

    if (!empty($bodyParameters)) {
        $components[] = ["type" => "body", "parameters" => $bodyParameters];
    }

    if ($template && !empty($template->variables_json)) {
        $buttonsConfig = json_decode($template->variables_json, true);
        if (!empty($buttonsConfig['buttons'])) {
            foreach ($buttonsConfig['buttons'] as $index => $btn) {
                if ($btn['type'] === 'COPY_CODE') {
                    $components[] = [
                        "type" => "button",
                        "sub_type" => "url",
                        "index" => (string)$index,
                        "parameters" => [["type" => "text", "text" => (string)($reqParameters[0] ?? '123456')]]
                    ];
                }
                if ($btn['type'] === 'URL' && strpos($btn['url'] ?? '', '{{1}}') !== false) {
                    $components[] = [
                        "type" => "button",
                        "sub_type" => "url",
                        "index" => (string)$index,
                        "parameters" => [["type" => "text", "text" => (string)($reqParameters[0] ?? '')]]
                    ];
                }
            }
        }
    }

    $safeTemplateName = strtolower(trim($request->template_name));

    $templatePayloadBase = [
        "name" => $safeTemplateName
    ];

    if (!empty($components)) {
        $templatePayloadBase["components"] = $components;
    }

    $languagesToTry = array_values(array_unique(array_filter([
        $request->template_language ?? null,
        'en_US', 'en_GB', 'en', 'en_IN', 'hi', 'hi_IN', 'ta', 'ta_IN', 'te', 'te_IN',
        'mr', 'mr_IN', 'bn', 'bn_IN', 'gu', 'gu_IN', 'kn', 'kn_IN', 'ml', 'ml_IN',
        'pa', 'pa_IN', 'ur', 'ur_IN', 'or', 'or_IN', 'as', 'as_IN', 'mai', 'mai_IN',
        'sat', 'sat_IN', 'ks', 'ks_IN', 'kok', 'kok_IN', 'sd', 'sd_IN', 'doi', 'doi_IN',
        'mni', 'mni_IN', 'ne', 'ne_IN', 'sa', 'sa_IN', 'brx', 'brx_IN', 'bho', 'bho_IN',
        'raj', 'raj_IN', 'awa', 'awa_IN', 'mag', 'mag_IN', 'bgc', 'bgc_IN', 'hne', 'hne_IN',
        'dcc', 'dcc_IN', 'kha', 'kha_IN', 'lus', 'lus_IN', 'lep', 'lep_IN', 'bhb', 'bhb_IN',
        'en_AU', 'en_CA', 'en_NZ', 'en_ZA', 'en_IE', 'en_SG', 'en_JM', 'en_BZ', 'en_TT', 
        'en_ZW', 'en_PH', 'en_MT', 'en_HK', 'en_MY', 'en_AE', 'en_NG', 'en_KE', 'en_UG'
    ])));

    $successCount = 0;
    $failCount = 0;
    $errors = [];

    foreach ($mobiles as $mob) {
        $cleanMobile = preg_replace('/[^0-9]/', '', $mob);
        if (empty($cleanMobile)) continue;

        $reqTime = Carbon::now('Asia/Kolkata')->toDateTimeString();
        $isSuccess = false;
        $body = null;
        $response = null;
        $finalPayload = [];

        foreach ($languagesToTry as $langCode) {
            $finalPayload = [
                "messaging_product" => "whatsapp",
                "to" => $cleanMobile,
                "type" => "template",
                "template" => array_merge($templatePayloadBase, [
                    "language" => ["code" => $langCode]
                ])
            ];

            $response = Http::withToken(env('FB_WHATSAPP_TOKEN'))
                ->acceptJson()
                ->post($url, $finalPayload);

            $body = $response->json();
            $isSuccess = $response->successful();

            if ($isSuccess) {
                break;
            }

            $errorCode = $body['error']['code'] ?? null;
            if ($errorCode != 132001) {
                break;
            }
        }

        $resTime = Carbon::now('Asia/Kolkata')->toDateTimeString();
        $messageId = $body['messages'][0]['id'] ?? 'out_' . uniqid();

        if ($isSuccess) {
            $successCount++;
            $this->storeOutgoingFirebase($cleanMobile, $messageId, 'template', null, $request->template_name, $resTime);
            $this->upsertContactFirebase($cleanMobile, null, "[$request->template_name template]", false, $resTime);
        } else {
            $failCount++;
            $errorMsg = $body['error']['message'] ?? 'Unknown Error';
            $errorUserMsg = $body['error']['error_user_msg'] ?? '';
            $errors[] = $cleanMobile . ': ' . $errorMsg . ' - ' . $errorUserMsg;
        }

        DB::table('smslog')->insert([
            'gateway' => 'fbWhatsapp',
            'subject' => substr($request->message_body ?? "Template: " . $request->template_name, 0, 200),
            'details' => $request->message_body ?? "Template: " . $request->template_name,
            'mobile' => $cleanMobile,
            'ip' => $request->ip() ?? '',
            'datetime' => $reqTime,
            'token_response' => json_encode($body),
            'status' => $isSuccess ? 'sent' : 'failed',
            'reference_id' => $messageId,
            'site' => 'CUSTOMER',
            'REQ_Time' => $reqTime,
            'RES_Time' => $resTime,
            'smsdetails' => json_encode($finalPayload),
            'smsstatus' => $isSuccess ? 'Sent' : 'Failed',
            'smssendstatus' => $isSuccess ? '1' : '0',
            'response' => $response ? $response->body() : '',
            'isResend' => 'NO'
        ]);
    }

    return response()->json([
        'status' => $successCount > 0 || $failCount === 0,
        'message' => "Sent successfully to $successCount numbers." . ($failCount > 0 ? " Failed for $failCount numbers." : ""),
        'errors' => $errors
    ]);
}
    
    public function showLoginForm()
    {
        if (session()->has('chat_admin_logged_in')) {
            return redirect()->route('gorideChat');
        }
        return view('chat-login');
    }

    public function processLogin(Request $request)
    {
    $request->validate([
        'username' => 'required',
        'password' => 'required'
    ]);

    $admin = \Illuminate\Support\Facades\DB::table('chat_admins')
        ->where('username', $request->username)
        // Hash the incoming password with MD5 before checking the DB
        ->where('password', md5($request->password)) 
        ->first();

    if ($admin) {
        session([
            'chat_admin_logged_in' => true,
            'chat_admin_username' => $admin->username
        ]);
        return redirect()->route('gorideChat');
    }

    return back()->with('error', 'Invalid username or password.');
}

    public function chatPage()
    {
        if (!session()->has('chat_admin_logged_in')) {
            return redirect()->route('chat.login')->with('error', 'Please login to access the chat.');
        }
        return view('gorideChat');
    }

    public function logout()
    {
        session()->forget(['chat_admin_logged_in', 'chat_admin_username']);
        return redirect()->route('chat.login');
    }
    
    public function whNoreplyWebhook(Request $request)
    {
        try {
            $verifyToken = 'goride-wh-noreply-hook0987)(*&';
            
            \Log::info('WA_WEBHOOK_DEBUG', ['has_message' => isset($payload['entry'][0]['changes'][0]['value']['messages']), 'raw_payload' => $request]);
    
            if ($request->isMethod('get')) {
                $token      = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
                $challenge  = $request->query('hub_challenge') ?? $request->query('hub.challenge');
    
                if ($token == $verifyToken) {
                    return response($challenge, 200)->header('Content-Type', 'text/plain');
                }
                return response('Verification token mismatch', 403);
            }
    
            if ($request->isMethod('post')) {
                $payload = $request->all();
                $entry = $payload['entry'][0]['changes'][0]['value'] ?? null;
    
                if (!$entry) return response()->json(['status' => 'no entry'], 200);
    
                $this->storeIncomingFirebase($entry);
                $this->handleStatusFirebase($entry);
                $this->storeIncomingMessage($entry);
                $this->handleStatus($entry);
                $this->handleIncomingMessage($entry);
    
                return response()->json(['status' => 'processed'], 200);
            }
        } catch (\Throwable $e) {
            Log::error('Webhook Error: ' . $e->getMessage());
            return response()->json(['error' => 'server error'], 500);
        }
    }

    private function createMessageFirebase($waId, $msgId, $data)
    {
        try {
            $projectId = $this->serviceAccount['project_id'];
            $accessToken = $this->getAccessToken();
            $safeMsgId = urlencode($msgId);
            $docPath = "contacts/{$waId}/messages/{$safeMsgId}";

            $fields = [];
            foreach ($data as $key => $value) {
                if ($value !== null) {
                    $fields[$key] = ['stringValue' => (string)$value];
                }
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/{$docPath}";
            $payload = json_encode(['fields' => $fields]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Throwable $e) {
            Log::error('Firebase Create Msg Error: ' . $e->getMessage());
        }
    }
    
    private function upsertContactFirebase($waId, $name = null, $lastMessage = null, $isIncoming = false, $customTime = null)
    {
        try {
            $projectId = $this->serviceAccount['project_id'];
            $accessToken = $this->getAccessToken();
            $docPath = "contacts/{$waId}";
            
            $currentUnread = 0;
            $existingName = null; 
            $docExists = false;
            
            $urlGet = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/{$docPath}";
            $chGet = curl_init($urlGet);
            curl_setopt($chGet, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chGet, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
            $resGet = curl_exec($chGet);
            curl_close($chGet);
            
            if ($resGet) {
                $dataGet = json_decode($resGet, true);
                if (!isset($dataGet['error'])) {
                    $docExists = true;
                    if (isset($dataGet['fields']['unread_count']['integerValue'])) {
                        $currentUnread = (int)$dataGet['fields']['unread_count']['integerValue'];
                    }
                    if (isset($dataGet['fields']['name']['stringValue'])) {
                        $existingName = $dataGet['fields']['name']['stringValue'];
                    }
                }
            }

            $timestamp = $customTime ?? Carbon::now('Asia/Kolkata')->toDateTimeString();

            $fields = [
                'phone' => ['stringValue' => (string)$waId],
                'last_message_at' => ['stringValue' => $timestamp],
                'updated_at' => ['stringValue' => $timestamp]
            ];
            $updatePaths = ['phone', 'last_message_at', 'updated_at'];

            if (!empty($name) && empty($existingName)) {
                $fields['name'] = ['stringValue' => (string)$name];
                $updatePaths[] = 'name';
            }
            
            if (!empty($lastMessage)) {
                $fields['last_message'] = ['stringValue' => (string)$lastMessage];
                $updatePaths[] = 'last_message';
            }
            
            $newUnreadCount = $isIncoming ? ($currentUnread + 1) : 0;
            $fields['unread_count'] = ['integerValue' => (string)$newUnreadCount];
            $updatePaths[] = 'unread_count';

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/{$docPath}";
            
            if ($docExists) {
                $maskStr = implode('&', array_map(fn($p) => "updateMask.fieldPaths={$p}", $updatePaths));
                $url .= '?' . $maskStr;
            }
            
            $payload = json_encode(['fields' => $fields]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);
            curl_exec($ch);
            curl_close($ch);

        } catch (\Throwable $e) {
            Log::error('Firebase Contact Error: ' . $e->getMessage());
        }
    }
    
    private function handleIncomingMessage($entry)
    {
        try {
            if (!isset($entry['messages'][0])) return;
            $message = $entry['messages'][0];
            $from = $message['from'] ?? null;
            $type = $message['type'] ?? null;
    
            if (!$from) return;
    
            if ($this->isFirstMessageToday($from)) {
                $this->sendTemplate($from, 'welcome_flow', 'en');
                return;
            }
            
            if ($type == 'button') {
                $payload = strtolower(trim($message['button']['payload'] ?? ''));
                if (str_contains($payload, 'collaboration')) {
                    $this->sendTemplate($from, 'collaboration_template', 'en');
                    return;
                }
                if (str_contains($payload, 'fast')) {
                    $this->sendTemplate($from, 'fast_book_template', 'en_US');
                    return;
                }
            }
        } catch (\Throwable $e) {
            Log::error('Logic Error: ' . $e->getMessage());
        }
    }
    
    private function storeIncomingMessage($entry)
    {
        try {
            $message = $entry['messages'][0] ?? null;
            if (!$message) return;
    
            $waId = $message['from'];
            $msgId = $message['id'];
            $conversation = DB::table('wa_conversations')->where('wa_id', $waId)->first();
            
            $metaTimestamp = $message['timestamp'] ?? time();
            $istTime = Carbon::createFromTimestamp($metaTimestamp)->timezone('Asia/Kolkata')->toDateTimeString();
    
            if (!$conversation) {
                $conversationId = DB::table('wa_conversations')->insertGetId([
                    'wa_id' => $waId,
                    'created_at' => $istTime,
                    'updated_at' => $istTime,
                ]);
            } else {
                $conversationId = $conversation->id;
                DB::table('wa_conversations')->where('id', $conversationId)->update([
                    'last_message_at' => $istTime,
                    'updated_at' => $istTime,
                ]);
            }
    
            $type = $message['type'] ?? 'unknown';
            $text = $message['text']['body'] ?? null;
            $buttonPayload = $message['button']['payload'] ?? null;
            $buttonText = $message['button']['text'] ?? null;
    
            DB::table('wa_messages')->insert([
                'conversation_id' => $conversationId,
                'wa_message_id' => $msgId,
                'direction' => 'in',
                'type' => $type,
                'message_text' => $text,
                'button_payload' => $buttonPayload,
                'button_text' => $buttonText,
                'raw_payload' => json_encode($message),
                'created_at' => $istTime,
                'updated_at' => $istTime,
            ]);
        } catch (\Throwable $e) {
            Log::error('Store Incoming Error: ' . $e->getMessage());
        }
    }
    
    private function storeIncomingFirebase($entry)
    {
        try {
            $message = $entry['messages'][0] ?? null;
            if (!$message) return;
    
            $waId = $message['from'];
            $type = $message['type'] ?? 'unknown';
            $waMessageId = $message['id'] ?? uniqid();
            
            $metaTimestamp = $message['timestamp'] ?? time();
            $istTime = Carbon::createFromTimestamp($metaTimestamp)->timezone('Asia/Kolkata')->toDateTimeString();
    
            $text = $message['text']['body'] ?? $message['button']['text'] ?? $message['interactive']['button_reply']['title'] ?? "[$type message]";
            $payload = $message['button']['payload'] ?? null;
            $buttonText = $message['button']['text'] ?? null;
            $name = $entry['contacts'][0]['profile']['name'] ?? null;
    
            $this->upsertContactFirebase($waId, $name, $text, true, $istTime);

            $this->createMessageFirebase($waId, $waMessageId, [
                'direction' => 'in',
                'type' => $type,
                'text' => $text,
                'payload' => $payload,
                'button_text' => $buttonText,
                'wa_message_id' => $waMessageId,
                'status' => 'received',
                'timestamp' => $istTime,
            ]);
        } catch (\Throwable $e) {
            Log::error('Firebase Incoming Error: ' . $e->getMessage());
        }
    }
    
    private function storeOutgoingMessage($waId, $msgId, $type, $text = null, $template = null)
    {
        try {
            $conversation = DB::table('wa_conversations')->where('wa_id', $waId)->first();
            if (!$conversation) return;
            
            $istTime = Carbon::now('Asia/Kolkata')->toDateTimeString();
    
            DB::table('wa_messages')->insert([
                'conversation_id' => $conversation->id,
                'wa_message_id' => $msgId,
                'direction' => 'out',
                'type' => $type,
                'message_text' => $text,
                'template_name' => $template,
                'created_at' => $istTime,
                'updated_at' => $istTime,
            ]);
        } catch (\Throwable $e) {
            Log::error('Store Outgoing Error: ' . $e->getMessage());
        }
    }
    
    private function storeOutgoingFirebase($waId, $waMessageId, $type, $text = null, $template = null, $customTime = null)
    {
        try {
            $fallbackText = $text ?? ($template ? "[$template template]" : "Outgoing message");
            $timestamp = $customTime ?? Carbon::now('Asia/Kolkata')->toDateTimeString();

            $this->createMessageFirebase($waId, $waMessageId, [
                'direction' => 'out',
                'type' => $type,
                'text' => $fallbackText,
                'template' => $template,
                'wa_message_id' => $waMessageId,
                'status' => 'sent',
                'timestamp' => $timestamp,
            ]);
        } catch (\Throwable $e) {
            Log::error('Firebase Outgoing Error: ' . $e->getMessage());
        }
    }
    
    private function sendTemplate($to, $templateName, $lang = 'en')
    {
        try {
            $phone_number_id = env('FB_WHATSAPP_PHONE_NUMBER_ID');
            $ver   = env('FB_WHATSAPP_VERSION');
            $token = env('FB_WHATSAPP_TOKEN');
    
            $response = Http::withToken($token)->post(
                "https://graph.facebook.com/{$ver}/{$phone_number_id}/messages",
                [
                    "messaging_product" => "whatsapp",
                    "to" => $to,
                    "type" => "template",
                    "template" => [
                        "name" => $templateName,
                        "language" => ["code" => $lang]
                    ]
                ]
            );
            
            $res = $response->json();
            $waMessageId = $res['messages'][0]['id'] ?? 'out_' . uniqid();
            
            $istTime = Carbon::now('Asia/Kolkata')->toDateTimeString();

            $this->storeOutgoingFirebase($to, $waMessageId, 'template', null, $templateName, $istTime);
            $this->upsertContactFirebase($to, null, "[$templateName template]", false, $istTime);

        } catch (\Throwable $e) {
            Log::error('Send Template Error: ' . $e->getMessage());
        }
    }
    
    private function handleStatus($entry)
    {
        try {
            $status = $entry['statuses'][0] ?? null;
            if (!$status) return;
            
            $istTime = Carbon::now('Asia/Kolkata')->toDateTimeString();
    
            DB::table('wa_messages')
                ->where('wa_message_id', $status['id'])
                ->update([
                    'status' => $status['status'],
                    'updated_at' => $istTime
                ]);
        } catch (\Throwable $e) {
            Log::error('Status Error: ' . $e->getMessage());
        }
    }
    
    private function handleStatusFirebase($entry)
    {
        try {
            $status = $entry['statuses'][0] ?? null;
            if (!$status) return;
    
            $waId = $status['recipient_id'] ?? null;
            $msgId = $status['id'] ?? null;
            $statusStr = $status['status'] ?? null;
            
            if (!$waId || !$msgId) return;

            $projectId = $this->serviceAccount['project_id'];
            $accessToken = $this->getAccessToken();
            $safeMsgId = urlencode($msgId);
            $docPath = "contacts/{$waId}/messages/{$safeMsgId}";
            
            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/{$docPath}?updateMask.fieldPaths=status";
            
            $payload = json_encode([
                'fields' => [
                    'status' => ['stringValue' => $statusStr]
                ]
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);
            curl_exec($ch);
            curl_close($ch);

        } catch (\Throwable $e) {
            Log::error('Firebase Status Error: ' . $e->getMessage());
        }
    }
    
    private function isFirstMessageToday($waId)
    {
        try {
            $today = Carbon::now('Asia/Kolkata')->toDateString();
            $istTime = Carbon::now('Asia/Kolkata')->toDateTimeString();
            
            $record = DB::table('wa_user_sessions')->where('wa_id', $waId)->first();
    
            if (!$record) {
                DB::table('wa_user_sessions')->insert([
                    'wa_id' => $waId,
                    'last_message_date' => $today,
                    'created_at' => $istTime,
                    'updated_at' => $istTime,
                ]);
                return true;
            }
    
            if ($record->last_message_date != $today) {
                DB::table('wa_user_sessions')->where('wa_id', $waId)->update([
                    'last_message_date' => $today,
                    'updated_at' => $istTime,
                ]);
                return true;
            }
            return false;
        } catch (\Throwable $e) {
            Log::error('Daily Check Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getChatAdmins()
    {
        try {
            $admins = \Illuminate\Support\Facades\DB::table('chat_admins')
                ->select('id', 'username')
                ->get();
            return response()->json(['status' => true, 'data' => $admins]);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'error' => $e->getMessage()]);
        }
    }
    
    public function fetchMissingTemplates(Request $request)
    {
        $wabaId = env('FB_WHATSAPP_BUSINESS_ACCOUNT_ID');
        $version = env('FB_WHATSAPP_VERSION', 'v24.0');
        $token = env('FB_WHATSAPP_TOKEN');

        
        $url = "https://graph.facebook.com/{$version}/{$wabaId}/message_templates?limit=1000";

        try {
            $response = Http::withToken($token)->get($url);
            if (!$response->successful()) {
                return response()->json(['status' => false, 'message' => 'Failed to fetch from Meta: ' . $response->body()]);
            }

            $fbTemplates = $response->json()['data'] ?? [];

            // Get all local template names (lowercase for safe comparison)
            $localTemplates = DB::table('wamail_templates')->pluck('name')->map(function ($name) {
                return strtolower(trim($name));
            })->toArray();

            $missingTemplates = [];

            foreach ($fbTemplates as $tpl) {
                $fbName = strtolower(trim($tpl['name']));
                // If the template from Facebook does not exist in our local DB, add it to the missing list
                if (!in_array($fbName, $localTemplates)) {
                    $missingTemplates[] = $tpl;
                }
            }

            return response()->json([
                'status' => true,
                'templates' => $missingTemplates
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

    public function syncSelectedTemplates(Request $request)
    {
        $templates = $request->input('templates');

        if (empty($templates)) {
            return response()->json(['status' => false, 'message' => 'No templates provided for syncing.']);
        }

        $insertedCount = 0;

        try {
            foreach ($templates as $tpl) {
                $name = $tpl['name'];
                $channel = $tpl['category'] ?? 'UTILITY';
                $status = strtolower($tpl['status'] ?? 'approved');
                $fbId = $tpl['id'];

                $bodyText = '';
                $buttonsJson = '';
                $headerImage = '';

                // Parse Meta's component array back into our DB format
                if (isset($tpl['components'])) {
                    foreach ($tpl['components'] as $comp) {
                        if ($comp['type'] === 'BODY') {
                            $bodyText = $comp['text'] ?? '';
                        }

                        if ($comp['type'] === 'HEADER' && isset($comp['format']) && $comp['format'] === 'IMAGE') {
                            if (isset($comp['example']) && is_array($comp['example'])) {
                                $headerImage = $comp['example']['header_url'][0] ?? ($comp['example']['header_handle'][0] ?? '');
                            }
                        }

                        // FIX: Added robust catch for COPY_CODE and unknown buttons
                        if ($comp['type'] === 'BUTTONS' && isset($comp['buttons'])) {
                            $btnType = 'none';
                            $btns = [];
                            foreach ($comp['buttons'] as $b) {
                                if ($b['type'] === 'QUICK_REPLY') {
                                    $btnType = 'QUICK_REPLY';
                                    $btns[] = ['type' => 'QUICK_REPLY', 'text' => $b['text']];
                                } elseif ($b['type'] === 'URL') {
                                    $btnType = 'CALL_TO_ACTION';
                                    $btns[] = ['type' => 'URL', 'text' => $b['text'] ?? '', 'url' => $b['url'] ?? ''];
                                } elseif ($b['type'] === 'PHONE_NUMBER') {
                                    $btnType = 'CALL_TO_ACTION';
                                    $btns[] = ['type' => 'PHONE_NUMBER', 'text' => $b['text'] ?? '', 'phone_number' => $b['phone_number'] ?? ''];
                                } elseif ($b['type'] === 'COPY_CODE') {
                                    $btnType = 'COPY_CODE';
                                    $btns[] = ['type' => 'COPY_CODE', 'text' => $b['text'] ?? 'Copy code', 'example' => $b['example'] ?? ''];
                                } else {
                                    $btnType = $b['type'];
                                    $btns[] = ['type' => $b['type'], 'text' => $b['text'] ?? 'Button'];
                                }
                            }
                            if (count($btns) > 0) {
                                $buttonsJson = json_encode(['type' => $btnType, 'buttons' => $btns], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            }
                        }
                    }
                }

                if (empty($buttonsJson)) {
                    $buttonsJson = json_encode(['type' => 'none', 'buttons' => []]);
                }

                $formattedBody = '';
                if (!empty($bodyText)) {
                    $formattedBody = '<p>' . nl2br(htmlspecialchars($bodyText)) . '</p>';
                }

                preg_match_all('/\{\{\d+\}\}/', $bodyText, $matches);
                $varCount = count($matches[0]);
                $mType = $varCount > 0 ? 'dynamic' : 'static';

                $exists = DB::table('wamail_templates')->where('name', $name)->exists();

                if (!$exists) {
                    DB::table('wamail_templates')->insert([
                        'name' => $name,
                        'channel' => $channel,
                        'body' => $formattedBody,
                        'variables_json' => $buttonsJson,
                        'header_image' => $headerImage,
                        'media_url' => '',
                        'whatsapp_template_id' => $fbId,
                        'approval_status' => $status,
                        'is_active' => 1,
                        'created_by' => 0,
                        'm_type' => $mType,
                        'var_count' => $varCount,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $insertedCount++;
                }
            }

            return response()->json(['status' => true, 'message' => "Successfully synced $insertedCount templates."]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'DB Error: ' . $e->getMessage()], 500);
        }
    }
}