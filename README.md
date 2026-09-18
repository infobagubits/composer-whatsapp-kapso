<div align="center">
    <h1>Laravel Kapso</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/infobagubits/whatsapp-kapso"><img src="https://img.shields.io/packagist/v/infobagubits/whatsapp-kapso.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/infobagubits/whatsapp-kapso"><img src="https://img.shields.io/packagist/php-v/infobagubits/whatsapp-kapso.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/infobagubits/whatsapp-kapso"><img src="https://badge.laravel.cloud/badge/infobagubits/whatsapp-kapso?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/infobagubits/whastapp-kapso/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/infobagubits/whatsapp-kapso/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/infobagubits/whatsapp-kapso"><img src="https://img.shields.io/packagist/dt/infobagubits/whatsapp-kapso.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Laravel integration for Kapso WhatsApp API

## Installation

You can install the package via Composer:

```bash
composer require infobagubits/whatsapp-kapso
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="laravel-kapso"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="laravel-kapso-config"
```

## Configuration

```dotenv
KAPSO_BASE_URL=https://api.kapso.ai/meta/whatsapp/v24.0
KAPSO_PHONE_ID=your_phone_number_id
KAPSO_API_KEY=your_project_api_key
KAPSO_BUSINESS_ID=your_whatsapp_business_account_id
```

`KAPSO_BUSINESS_ID` is only required by the endpoints scoped to the WhatsApp
Business Account: templates, flows and phone number listing.

## Usage

Resolve `Integrations\Kapso\Kapso` from the container, or type-hint it:

```php
use Integrations\Kapso\Kapso;

public function notify(Kapso $kapso): void
{
    $kapso->sendText('15551234567', 'Your order #12345 has shipped.');
}
```

Every method returns the decoded JSON response as an array and throws
`Illuminate\Http\Client\RequestException` when Kapso answers with an error.

Media arguments accept either a public URL (sent as `link`) or a media ID
returned by `uploadMedia()` (sent as `id`) — the distinction is automatic.

The trailing `$options` array on the send methods is merged into the top level
of the payload, which is how you pass `context` (reply to a message),
`recipient_type`, `recipient` (BSUID) or `biz_opaque_callback_data`:

```php
$kapso->sendText('15551234567', 'Sure, here it is.', options: [
    'context' => ['message_id' => 'wamid.HBgNMTU1NTE0OTU5Nzg1...'],
]);
```

### Sending messages

| Method | Type |
| --- | --- |
| `sendText($to, $text, $previewUrl, $options)` | `text` |
| `sendImage($to, $media, $caption, $options)` | `image` |
| `sendVideo($to, $media, $caption, $options)` | `video` |
| `sendAudio($to, $media, $voice, $options)` | `audio` |
| `sendVoice($to, $media, $options)` | `audio` voice note |
| `sendDocument($to, $media, $caption, $filename, $options)` | `document` |
| `sendSticker($to, $media, $options)` | `sticker` |
| `sendLocation($to, $lat, $lng, $name, $address, $options)` | `location` |
| `sendContacts($to, $contacts, $options)` | `contacts` |
| `sendReaction($to, $messageId, $emoji, $options)` | `reaction` |
| `removeReaction($to, $messageId, $options)` | `reaction` |
| `sendTemplate($to, $name, $language, $components, $options)` | `template` |
| `sendMarketingTemplate($to, $name, $language, $components, $options)` | marketing template |
| `sendMessage($payload)` | raw payload |
| `markAsRead($messageId, $typingIndicator)` | read receipt |

### Interactive messages

| Method | Interactive type |
| --- | --- |
| `sendButtons($to, $body, $buttons, $header, $footer, $options)` | `button` |
| `sendList($to, $body, $buttonText, $sections, $header, $footer, $options)` | `list` |
| `sendCtaUrl($to, $body, $displayText, $url, $header, $footer, $options)` | `cta_url` |
| `sendFlow($to, $body, $flowId, $flowCta, $flowToken, $flowAction, $payload, $header, $footer, $options)` | `flow` |
| `sendLocationRequest($to, $body, $options)` | `location_request_message` |
| `sendContactInfoRequest($to, $body, $options)` | `request_contact_info` |
| `sendCallPermissionRequest($to, $body, $options)` | `call_permission_request` |
| `sendAddressMessage($to, $body, $country, $values, $options)` | `address_message` |
| `sendCarousel($to, $body, $cards, $options)` | `carousel` |
| `sendProduct($to, $catalogId, $productRetailerId, $body, $footer, $options)` | `product` |
| `sendProductList($to, $catalogId, $headerText, $body, $sections, $footer, $options)` | `product_list` |
| `sendCatalog($to, $body, $thumbnailProductRetailerId, $footer, $options)` | `catalog_message` |
| `sendOrderDetails($to, $body, $parameters, $header, $footer, $options)` | `order_details` |
| `sendOrderStatus($to, $body, $referenceId, $status, $description, $options)` | `order_status` |
| `sendInteractive($to, $interactive, $options)` | any raw interactive |

`textHeader($text)` and `mediaHeader($type, $media, $filename)` build the
`$header` argument.

```php
$kapso->sendButtons('15551234567', 'Proceed with your order?', [
    ['id' => 'yes', 'title' => 'Yes'],
    ['id' => 'no', 'title' => 'No'],
], $kapso->textHeader('Confirmation'), 'Powered by Kapso');
```

### Reading messages, conversations, contacts

```php
$kapso->listMessages(['direction' => 'inbound', 'limit' => 50, 'fields' => 'kapso()']);
$kapso->getMessage('wamid.HBgNMTU1NTE0OTU5Nzg1...');
$kapso->listConversations(['status' => 'active']);
$kapso->getConversation($conversationId);
$kapso->listContacts(['has_customer' => true]);
$kapso->getContact('15551234567');
```

### Media

```php
$media = $kapso->uploadMedia(storage_path('app/invoice.pdf'));
$kapso->sendDocument('15551234567', $media['id'], 'Your invoice', 'invoice.pdf');

$kapso->getMediaUrl($media['id']);          // temporary URL, expires in 5 minutes
$bytes = $kapso->downloadMedia($token);     // raw body from a signed download_url
$kapso->deleteMedia($media['id']);
```

### Templates

```php
$kapso->listTemplates(['status' => 'APPROVED']);
$kapso->getTemplate($templateId);
$kapso->createTemplate('order_shipped', 'en_US', 'UTILITY', $components);
$kapso->updateTemplate($templateId, ['components' => $components]);
$kapso->deleteTemplate(name: 'order_shipped');
```

### Calls

```php
$kapso->listCalls(['status' => 'completed']);
$kapso->getCallPermissions('15551234567');
$kapso->connectCall('15551234567', $sdp);
$kapso->preAcceptCall($callId, $sdp);
$kapso->acceptCall($callId, $sdp);
$kapso->rejectCall($callId);
$kapso->terminateCall($callId);
$kapso->callAction('terminate', ['call_id' => $callId]);   // raw
```

### Flows

```php
$kapso->listFlows();                                    // business account scoped
$kapso->listPhoneNumberFlows();                         // phone number scoped
$kapso->createFlow('Signup', ['SIGN_UP'], ['publish' => false]);
$kapso->uploadFlowJson($flowId, resource_path('flows/signup.json'));
$kapso->getFlow($flowId);
$kapso->getFlowAssets($flowId);
$kapso->updateFlow($flowId, ['name' => 'Signup v2']);
$kapso->publishFlow($flowId);
$kapso->deprecateFlow($flowId);
$kapso->deleteFlow($flowId);
```

### Account management

```php
$kapso->getBusinessProfile();
$kapso->updateBusinessProfile(['about' => 'We ship worldwide', 'email' => 'hi@example.com']);

$kapso->listBlockedUsers();
$kapso->blockUsers(['15551234567']);
$kapso->unblockUsers(['15551234567']);

$kapso->getUsername();
$kapso->setUsername('lucky-shrub');
$kapso->getUsernameSuggestions();
$kapso->deleteUsername();

$kapso->listPhoneNumbers();
$kapso->getPhoneNumber();
$kapso->updatePhoneNumber('123456');
```

## HTTP routes

The package registers one route per method under the `kapso/` prefix, for
example:

```
POST    kapso/send/message          {to, text, preview_url?, options?}
POST    kapso/send/image            {to, media, caption?, options?}
POST    kapso/send/buttons          {to, body, buttons[], header?, footer?, options?}
GET     kapso/messages              ?direction=&status=&limit=&fields=
GET     kapso/messages/{messageId}
POST    kapso/media                 multipart: file
GET     kapso/media/{mediaId}
DELETE  kapso/media/{mediaId}
GET     kapso/templates
POST    kapso/flows/{flowId}/publish
```

Run `php artisan route:list --path=kapso` for the full list.

## Webhooks

Point your Kapso webhook at `POST /kapso/webhook` and set the handler class in
the config. The handler receives the event name and one payload at a time —
batched deliveries are unwrapped for you.

```php
// config/laravel-kapso.php
'webhook' => [
    'handler' => App\Webhooks\KapsoHandler::class,
    'secret' => env('KAPSO_WEBHOOK_SECRET'),
],
```

```php
class KapsoHandler
{
    public function handle(?string $event, array $payload): void
    {
        // ...
    }
}
```

### Idempotency

Kapso retries a delivery at 10 and 40 seconds when it does not get a `200`, so
the same event can reach you more than once. Each payload is deduplicated
individually, which means the retry of a partially processed batch replays only
the events that did not get through the first time.

`X-Idempotency-Key` is used when present — Meta webhooks send it, Kapso ones do
not — otherwise the key is derived from the payload's `id`, `message_id`,
`wamid` or `event_id`, falling back to a hash of the payload.

```dotenv
KAPSO_WEBHOOK_IDEMPOTENCY=true
KAPSO_WEBHOOK_CACHE_STORE=redis      # empty: default store
KAPSO_WEBHOOK_IDEMPOTENCY_TTL=3600
```

### Queue

Your endpoint has to answer within 10 seconds. Handlers therefore run in a
queued job by default, and the request returns as soon as the signature is
verified and the events are deduplicated.

```dotenv
KAPSO_WEBHOOK_QUEUE=true
KAPSO_WEBHOOK_QUEUE_CONNECTION=redis
KAPSO_WEBHOOK_QUEUE_NAME=webhooks
```

Set `KAPSO_WEBHOOK_QUEUE=false` to run handlers inside the request instead.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Laravel Kapso! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Integrations](https://github.com/infobagubits)
- [All Contributors](../../contributors)

## License

Laravel Kapso is open-sourced software licensed under the [MIT license](LICENSE.md).
