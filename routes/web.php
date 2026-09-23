<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FBWhAutomationController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/chat-login', [FBWhAutomationController::class, 'showLoginForm'])->name('chat.login');
Route::post('/chat-login', [FBWhAutomationController::class, 'processLogin'])->name('chat.login.submit');
Route::get('/chat-logout', [FBWhAutomationController::class, 'logout'])->name('chat.logout');
Route::get('/webview-chat', [FBWhAutomationController::class, 'chatPage'])->name('gorideChat');
Route::post('/send-message', [FBWhAutomationController::class, 'sendMessage']);
Route::post('/get-templates', [FBWhAutomationController::class, 'getTemplates']);
Route::post('/send-template-message', [FBWhAutomationController::class, 'sendTemplateMessage']);
Route::match(['get', 'post'], '/whatsapp-webhook', [FBWhAutomationController::class, 'whNoreplyWebhook']);
Route::get('/chat-admins', [FBWhAutomationController::class, 'getChatAdmins'])->name('chat.admins');
Route::get('/whatsapp/fetch-missing-templates', [FBWhAutomationController::class, 'fetchMissingTemplates']);
Route::post('/whatsapp/sync-selected-templates', [FBWhAutomationController::class, 'syncSelectedTemplates']);

// WhatsApp Media Routes
Route::get('/api/whatsapp/media/{waMessageId}/view', [FBWhAutomationController::class, 'viewMedia'])->where('waMessageId', '.*');
Route::post('/api/whatsapp/media/{waMessageId}/store', [FBWhAutomationController::class, 'storeMedia'])->where('waMessageId', '.*');