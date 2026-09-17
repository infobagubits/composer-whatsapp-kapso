<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Integrations\Kapso\Kapso;

/*
|--------------------------------------------------------------------------
| Messaggi - invio
|--------------------------------------------------------------------------
*/

Route::post('kapso/send/message', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'text' => ['required', 'string'],
        'preview_url' => ['nullable', 'boolean'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendText($data['to'], $data['text'], (bool) ($data['preview_url'] ?? false), $data['options'] ?? []),
    );
});

Route::post('kapso/send/image', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'media' => ['required', 'string'],
        'caption' => ['nullable', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendImage($data['to'], $data['media'], $data['caption'] ?? '', $data['options'] ?? []),
    );
});

Route::post('kapso/send/video', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'media' => ['required', 'string'],
        'caption' => ['nullable', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendVideo($data['to'], $data['media'], $data['caption'] ?? '', $data['options'] ?? []),
    );
});

Route::post('kapso/send/audio', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'media' => ['required', 'string'],
        'voice' => ['nullable', 'boolean'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendAudio($data['to'], $data['media'], (bool) ($data['voice'] ?? false), $data['options'] ?? []),
    );
});

Route::post('kapso/send/voice', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'media' => ['required', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendVoice($data['to'], $data['media'], $data['options'] ?? []),
    );
});

Route::post('kapso/send/document', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'media' => ['required', 'string'],
        'caption' => ['nullable', 'string'],
        'filename' => ['nullable', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendDocument($data['to'], $data['media'], $data['caption'] ?? '', $data['filename'] ?? '', $data['options'] ?? []),
    );
});

Route::post('kapso/send/sticker', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'media' => ['required', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendSticker($data['to'], $data['media'], $data['options'] ?? []),
    );
});

Route::post('kapso/send/location', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'latitude' => ['required', 'numeric'],
        'longitude' => ['required', 'numeric'],
        'name' => ['nullable', 'string'],
        'address' => ['nullable', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendLocation($data['to'], (float) $data['latitude'], (float) $data['longitude'], $data['name'] ?? '', $data['address'] ?? '', $data['options'] ?? []),
    );
});

Route::post('kapso/send/contacts', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'contacts' => ['required', 'array', 'min:1'],
        'contacts.*.name.formatted_name' => ['required', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendContacts($data['to'], $data['contacts'], $data['options'] ?? []),
    );
});

Route::post('kapso/send/reaction', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'message_id' => ['required', 'string'],
        'emoji' => ['required', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendReaction($data['to'], $data['message_id'], $data['emoji'], $data['options'] ?? []),
    );
});

Route::post('kapso/send/reaction/remove', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'message_id' => ['required', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->removeReaction($data['to'], $data['message_id'], $data['options'] ?? []),
    );
});

Route::post('kapso/send/template', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'name' => ['required', 'string'],
        'language' => ['nullable', 'string'],
        'components' => ['nullable', 'array'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendTemplate($data['to'], $data['name'], $data['language'] ?? 'en_US', $data['components'] ?? [], $data['options'] ?? []),
    );
});

Route::post('kapso/send/marketing-template', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'name' => ['required', 'string'],
        'language' => ['nullable', 'string'],
        'components' => ['nullable', 'array'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendMarketingTemplate($data['to'], $data['name'], $data['language'] ?? 'en_US', $data['components'] ?? [], $data['options'] ?? []),
    );
});

Route::post('kapso/send/raw', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'payload' => ['required', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendMessage($data['payload']),
    );
});

Route::post('kapso/messages/read', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'message_id' => ['required', 'string'],
        'typing_indicator' => ['nullable', 'boolean'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->markAsRead($data['message_id'], (bool) ($data['typing_indicator'] ?? false)),
    );
});

/*
|--------------------------------------------------------------------------
| Messaggi interattivi
|--------------------------------------------------------------------------
*/

Route::post('kapso/send/interactive', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'interactive' => ['required', 'array'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendInteractive($data['to'], $data['interactive'], $data['options'] ?? []),
    );
});

Route::post('kapso/send/buttons', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'body' => ['required', 'string'],
        'buttons' => ['required', 'array', 'min:1', 'max:3'],
        'header' => ['nullable', 'array'],
        'footer' => ['nullable', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendButtons($data['to'], $data['body'], $data['buttons'], $data['header'] ?? [], $data['footer'] ?? '', $data['options'] ?? []),
    );
});

Route::post('kapso/send/list', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'body' => ['required', 'string'],
        'button_text' => ['required', 'string'],
        'sections' => ['required', 'array', 'min:1'],
        'header' => ['nullable', 'array'],
        'footer' => ['nullable', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendList($data['to'], $data['body'], $data['button_text'], $data['sections'], $data['header'] ?? [], $data['footer'] ?? '', $data['options'] ?? []),
    );
});

Route::post('kapso/send/cta-url', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'body' => ['required', 'string'],
        'display_text' => ['required', 'string'],
        'url' => ['required', 'url'],
        'header' => ['nullable', 'array'],
        'footer' => ['nullable', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendCtaUrl($data['to'], $data['body'], $data['display_text'], $data['url'], $data['header'] ?? [], $data['footer'] ?? '', $data['options'] ?? []),
    );
});

Route::post('kapso/send/flow', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'body' => ['required', 'string'],
        'flow_id' => ['required', 'string'],
        'flow_cta' => ['required', 'string'],
        'flow_token' => ['nullable', 'string'],
        'flow_action' => ['nullable', 'string', 'in:navigate,data_exchange'],
        'flow_action_payload' => ['nullable', 'array'],
        'header' => ['nullable', 'array'],
        'footer' => ['nullable', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendFlow(
            $data['to'],
            $data['body'],
            $data['flow_id'],
            $data['flow_cta'],
            $data['flow_token'] ?? '',
            $data['flow_action'] ?? 'navigate',
            $data['flow_action_payload'] ?? [],
            $data['header'] ?? [],
            $data['footer'] ?? '',
            $data['options'] ?? [],
        ),
    );
});

Route::post('kapso/send/location-request', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'body' => ['required', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendLocationRequest($data['to'], $data['body'], $data['options'] ?? []),
    );
});

Route::post('kapso/send/contact-info-request', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'body' => ['required', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendContactInfoRequest($data['to'], $data['body'], $data['options'] ?? []),
    );
});

Route::post('kapso/send/call-permission-request', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'body' => ['required', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendCallPermissionRequest($data['to'], $data['body'], $data['options'] ?? []),
    );
});

Route::post('kapso/send/address', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'body' => ['required', 'string'],
        'country' => ['required', 'string', 'size:2'],
        'values' => ['nullable', 'array'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendAddressMessage($data['to'], $data['body'], $data['country'], $data['values'] ?? [], $data['options'] ?? []),
    );
});

Route::post('kapso/send/carousel', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'body' => ['required', 'string'],
        'cards' => ['required', 'array', 'min:2', 'max:10'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendCarousel($data['to'], $data['body'], $data['cards'], $data['options'] ?? []),
    );
});

Route::post('kapso/send/product', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'catalog_id' => ['required', 'string'],
        'product_retailer_id' => ['required', 'string'],
        'body' => ['nullable', 'string'],
        'footer' => ['nullable', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendProduct($data['to'], $data['catalog_id'], $data['product_retailer_id'], $data['body'] ?? '', $data['footer'] ?? '', $data['options'] ?? []),
    );
});

Route::post('kapso/send/product-list', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'catalog_id' => ['required', 'string'],
        'header_text' => ['required', 'string'],
        'body' => ['required', 'string'],
        'sections' => ['required', 'array', 'min:1'],
        'footer' => ['nullable', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendProductList($data['to'], $data['catalog_id'], $data['header_text'], $data['body'], $data['sections'], $data['footer'] ?? '', $data['options'] ?? []),
    );
});

Route::post('kapso/send/catalog', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'body' => ['required', 'string'],
        'thumbnail_product_retailer_id' => ['nullable', 'string'],
        'footer' => ['nullable', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendCatalog($data['to'], $data['body'], $data['thumbnail_product_retailer_id'] ?? '', $data['footer'] ?? '', $data['options'] ?? []),
    );
});

Route::post('kapso/send/order-details', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'body' => ['required', 'string'],
        'parameters' => ['required', 'array'],
        'header' => ['nullable', 'array'],
        'footer' => ['nullable', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendOrderDetails($data['to'], $data['body'], $data['parameters'], $data['header'] ?? [], $data['footer'] ?? '', $data['options'] ?? []),
    );
});

Route::post('kapso/send/order-status', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'body' => ['required', 'string'],
        'reference_id' => ['required', 'string'],
        'status' => ['required', 'string'],
        'description' => ['nullable', 'string'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->sendOrderStatus($data['to'], $data['body'], $data['reference_id'], $data['status'], $data['description'] ?? '', $data['options'] ?? []),
    );
});

/*
|--------------------------------------------------------------------------
| Messaggi - lettura
|--------------------------------------------------------------------------
*/

Route::get('kapso/messages', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'conversation_id' => ['nullable', 'string'],
        'direction' => ['nullable', 'string', 'in:inbound,outbound'],
        'status' => ['nullable', 'string', 'in:pending,sent,delivered,read,failed'],
        'since' => ['nullable', 'date'],
        'until' => ['nullable', 'date'],
        'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        'before' => ['nullable', 'string'],
        'after' => ['nullable', 'string'],
        'fields' => ['nullable', 'string'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->listMessages($data),
    );
});

Route::get('kapso/messages/{messageId}', function (Request $request, Kapso $kapso, string $messageId): JsonResponse {
    $rules = [
        'fields' => ['nullable', 'string'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->getMessage($messageId, $data['fields'] ?? ''),
    );
});

/*
|--------------------------------------------------------------------------
| Username business
|--------------------------------------------------------------------------
*/

Route::get('kapso/username/suggestions', fn (Kapso $kapso): JsonResponse => response()->json(
    $kapso->getUsernameSuggestions(),
));

Route::get('kapso/username', fn (Kapso $kapso): JsonResponse => response()->json(
    $kapso->getUsername(),
));

Route::post('kapso/username', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'username' => ['required', 'string'],
        'transfer_action' => ['nullable', 'string', 'in:none,force_transfer'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->setUsername($data['username'], $data['transfer_action'] ?? 'none'),
    );
});

Route::delete('kapso/username', fn (Kapso $kapso): JsonResponse => response()->json(
    $kapso->deleteUsername(),
));

/*
|--------------------------------------------------------------------------
| Conversazioni
|--------------------------------------------------------------------------
*/

Route::get('kapso/conversations', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'status' => ['nullable', 'string', 'in:active,ended'],
        'assigned_user_id' => ['nullable', 'string'],
        'unassigned' => ['nullable', 'boolean'],
        'last_active_since' => ['nullable', 'date'],
        'last_active_until' => ['nullable', 'date'],
        'phone_number' => ['nullable', 'string'],
        'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        'before' => ['nullable', 'string'],
        'after' => ['nullable', 'string'],
        'fields' => ['nullable', 'string'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->listConversations($data),
    );
});

Route::get('kapso/conversations/{conversationId}', function (Request $request, Kapso $kapso, string $conversationId): JsonResponse {
    $rules = [
        'fields' => ['nullable', 'string'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->getConversation($conversationId, $data['fields'] ?? ''),
    );
});

/*
|--------------------------------------------------------------------------
| Contatti
|--------------------------------------------------------------------------
*/

Route::get('kapso/contacts', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'wa_id' => ['nullable', 'string'],
        'customer_id' => ['nullable', 'string'],
        'has_customer' => ['nullable', 'boolean'],
        'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        'before' => ['nullable', 'string'],
        'after' => ['nullable', 'string'],
        'fields' => ['nullable', 'string'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->listContacts($data),
    );
});

Route::get('kapso/contacts/{waId}', function (Request $request, Kapso $kapso, string $waId): JsonResponse {
    $rules = [
        'fields' => ['nullable', 'string'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->getContact($waId, $data['fields'] ?? ''),
    );
});

/*
|--------------------------------------------------------------------------
| Chiamate
|--------------------------------------------------------------------------
*/

Route::get('kapso/calls', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'direction' => ['nullable', 'string', 'in:inbound,outbound'],
        'status' => ['nullable', 'string', 'in:initiated,ringing,answered,completed,failed'],
        'since' => ['nullable', 'date'],
        'until' => ['nullable', 'date'],
        'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        'before' => ['nullable', 'string'],
        'after' => ['nullable', 'string'],
        'fields' => ['nullable', 'string'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->listCalls($data),
    );
});

Route::get('kapso/calls/permissions', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'user_wa_id' => ['required', 'string'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->getCallPermissions($data['user_wa_id']),
    );
});

Route::post('kapso/calls/action', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'action' => ['required', 'string', 'in:connect,pre_accept,accept,reject,terminate'],
        'payload' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->callAction($data['action'], $data['payload'] ?? []),
    );
});

Route::post('kapso/calls/connect', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'to' => ['required', 'string'],
        'sdp' => ['required', 'string'],
        'sdp_type' => ['nullable', 'string', 'in:offer,answer'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->connectCall($data['to'], $data['sdp'], $data['sdp_type'] ?? 'offer', $data['options'] ?? []),
    );
});

Route::post('kapso/calls/pre-accept', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'call_id' => ['required', 'string'],
        'sdp' => ['required', 'string'],
        'sdp_type' => ['nullable', 'string', 'in:offer,answer'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->preAcceptCall($data['call_id'], $data['sdp'], $data['sdp_type'] ?? 'answer'),
    );
});

Route::post('kapso/calls/accept', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'call_id' => ['required', 'string'],
        'sdp' => ['required', 'string'],
        'sdp_type' => ['nullable', 'string', 'in:offer,answer'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->acceptCall($data['call_id'], $data['sdp'], $data['sdp_type'] ?? 'answer'),
    );
});

Route::post('kapso/calls/reject', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'call_id' => ['required', 'string'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->rejectCall($data['call_id']),
    );
});

Route::post('kapso/calls/terminate', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'call_id' => ['required', 'string'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->terminateCall($data['call_id']),
    );
});

/*
|--------------------------------------------------------------------------
| Media
|--------------------------------------------------------------------------
*/

Route::post('kapso/media', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'file' => ['required', 'file'],
    ];
    $request->validate($rules);

    $file = $request->file('file');

    abort_unless($file instanceof UploadedFile, 422, 'File non valido.');

    return response()->json(
        $kapso->uploadMedia($file->getPathname(), $file->getMimeType() ?? ''),
    );
});

Route::get('kapso/media/download', function (Request $request, Kapso $kapso): Response {
    $rules = [
        'token' => ['required', 'string'],
    ];
    $data = $request->validate($rules);

    return response($kapso->downloadMedia($data['token']));
});

Route::get('kapso/media/{mediaId}', fn (Kapso $kapso, string $mediaId): JsonResponse => response()->json(
    $kapso->getMediaUrl($mediaId),
));

Route::delete('kapso/media/{mediaId}', function (Request $request, Kapso $kapso, string $mediaId): JsonResponse {
    $rules = [
        'verify_phone_number' => ['nullable', 'boolean'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->deleteMedia($mediaId, (bool) ($data['verify_phone_number'] ?? true)),
    );
});

/*
|--------------------------------------------------------------------------
| Template
|--------------------------------------------------------------------------
*/

Route::get('kapso/templates', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'name' => ['nullable', 'string'],
        'status' => ['nullable', 'string', 'in:APPROVED,PENDING,REJECTED'],
        'category' => ['nullable', 'string', 'in:AUTHENTICATION,MARKETING,UTILITY'],
        'language' => ['nullable', 'string'],
        'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->listTemplates($data),
    );
});

Route::post('kapso/templates', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'name' => ['required', 'string'],
        'language' => ['required', 'string'],
        'category' => ['required', 'string', 'in:AUTHENTICATION,MARKETING,UTILITY'],
        'components' => ['required', 'array', 'min:1'],
        'parameter_format' => ['nullable', 'string', 'in:NAMED,POSITIONAL'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->createTemplate($data['name'], $data['language'], $data['category'], $data['components'], $data['parameter_format'] ?? ''),
    );
});

Route::delete('kapso/templates', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'name' => ['required_without:template_id', 'nullable', 'string'],
        'template_id' => ['required_without:name', 'nullable', 'string'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->deleteTemplate($data['name'] ?? '', $data['template_id'] ?? ''),
    );
});

Route::get('kapso/templates/{templateId}', function (Request $request, Kapso $kapso, string $templateId): JsonResponse {
    $rules = [
        'fields' => ['nullable', 'string'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->getTemplate($templateId, $data['fields'] ?? ''),
    );
});

Route::post('kapso/templates/{templateId}', function (Request $request, Kapso $kapso, string $templateId): JsonResponse {
    $rules = [
        'payload' => ['required', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->updateTemplate($templateId, $data['payload']),
    );
});

/*
|--------------------------------------------------------------------------
| Profilo business
|--------------------------------------------------------------------------
*/

Route::get('kapso/business-profile', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'fields' => ['nullable', 'string'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->getBusinessProfile($data['fields'] ?? ''),
    );
});

Route::post('kapso/business-profile', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'about' => ['nullable', 'string'],
        'address' => ['nullable', 'string'],
        'description' => ['nullable', 'string'],
        'email' => ['nullable', 'email'],
        'profile_picture_handle' => ['nullable', 'string'],
        'vertical' => ['nullable', 'string'],
        'websites' => ['nullable', 'array', 'max:2'],
        'websites.*' => ['url'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->updateBusinessProfile($data),
    );
});

/*
|--------------------------------------------------------------------------
| Utenti bloccati
|--------------------------------------------------------------------------
*/

Route::get('kapso/block-users', fn (Kapso $kapso): JsonResponse => response()->json(
    $kapso->listBlockedUsers(),
));

Route::post('kapso/block-users', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'users' => ['required', 'array', 'min:1'],
        'users.*' => ['string'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->blockUsers($data['users']),
    );
});

Route::delete('kapso/block-users', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'users' => ['required', 'array', 'min:1'],
        'users.*' => ['string'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->unblockUsers($data['users']),
    );
});

/*
|--------------------------------------------------------------------------
| Flows
|--------------------------------------------------------------------------
*/

Route::get('kapso/flows/phone', fn (Kapso $kapso): JsonResponse => response()->json(
    $kapso->listPhoneNumberFlows(),
));

Route::post('kapso/flows/phone', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'name' => ['required', 'string'],
        'categories' => ['required', 'array', 'min:1'],
        'categories.*' => ['string', 'in:SIGN_UP,SIGN_IN,APPOINTMENT_BOOKING,LEAD_GENERATION,CONTACT_US,CUSTOMER_SUPPORT,SURVEY,OTHER'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->createPhoneNumberFlow($data['name'], $data['categories'], $data['options'] ?? []),
    );
});

Route::get('kapso/flows', fn (Kapso $kapso): JsonResponse => response()->json(
    $kapso->listFlows(),
));

Route::post('kapso/flows', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'name' => ['required', 'string'],
        'categories' => ['required', 'array', 'min:1'],
        'categories.*' => ['string', 'in:SIGN_UP,SIGN_IN,APPOINTMENT_BOOKING,LEAD_GENERATION,CONTACT_US,CUSTOMER_SUPPORT,SURVEY,OTHER'],
        'options' => ['nullable', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->createFlow($data['name'], $data['categories'], $data['options'] ?? []),
    );
});

Route::get('kapso/flows/{flowId}/assets', fn (Kapso $kapso, string $flowId): JsonResponse => response()->json(
    $kapso->getFlowAssets($flowId),
));

Route::post('kapso/flows/{flowId}/assets', function (Request $request, Kapso $kapso, string $flowId): JsonResponse {
    $rules = [
        'file' => ['required', 'file'],
        'asset_type' => ['nullable', 'string'],
    ];
    $data = $request->validate($rules);

    $file = $request->file('file');

    abort_unless($file instanceof UploadedFile, 422, 'File non valido.');

    return response()->json(
        $kapso->uploadFlowJson($flowId, $file->getPathname(), $data['asset_type'] ?? 'FLOW_JSON'),
    );
});

Route::post('kapso/flows/{flowId}/publish', fn (Kapso $kapso, string $flowId): JsonResponse => response()->json(
    $kapso->publishFlow($flowId),
));

Route::post('kapso/flows/{flowId}/deprecate', fn (Kapso $kapso, string $flowId): JsonResponse => response()->json(
    $kapso->deprecateFlow($flowId),
));

Route::get('kapso/flows/{flowId}', function (Request $request, Kapso $kapso, string $flowId): JsonResponse {
    $rules = [
        'fields' => ['nullable', 'string'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->getFlow($flowId, $data['fields'] ?? ''),
    );
});

Route::post('kapso/flows/{flowId}', function (Request $request, Kapso $kapso, string $flowId): JsonResponse {
    $rules = [
        'payload' => ['required', 'array'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->updateFlow($flowId, $data['payload']),
    );
});

Route::delete('kapso/flows/{flowId}', fn (Kapso $kapso, string $flowId): JsonResponse => response()->json(
    $kapso->deleteFlow($flowId),
));

/*
|--------------------------------------------------------------------------
| Numeri di telefono
|--------------------------------------------------------------------------
*/

Route::get('kapso/phone-numbers', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'fields' => ['nullable', 'string'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->listPhoneNumbers($data['fields'] ?? ''),
    );
});

Route::get('kapso/phone-number', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'fields' => ['nullable', 'string'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->getPhoneNumber($data['fields'] ?? ''),
    );
});

Route::post('kapso/phone-number', function (Request $request, Kapso $kapso): JsonResponse {
    $rules = [
        'pin' => ['required', 'string', 'digits:6'],
    ];
    $data = $request->validate($rules);

    return response()->json(
        $kapso->updatePhoneNumber($data['pin']),
    );
});
