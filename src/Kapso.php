<?php

declare(strict_types=1);

namespace Integrations\Kapso;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

/**
 * Client per la Meta Proxy API di Kapso (WhatsApp Cloud API).
 *
 * Base URL: https://api.kapso.ai/meta/whatsapp/v24.0
 * Autenticazione: header X-API-Key.
 *
 * Ogni metodo pubblico restituisce la risposta JSON decodificata come array.
 * Il parametro $options presente sugli invii viene unito al payload di primo
 * livello, cosi' da poter passare context (risposta a un messaggio),
 * recipient_type, recipient (BSUID) o biz_opaque_callback_data.
 */
class Kapso
{
    /* =======================================================================
     | Infrastruttura
     ======================================================================= */

    /**
     * Client HTTP gia' autenticato verso Kapso.
     */
    private function client(): PendingRequest
    {
        return Http::acceptJson()->withHeaders(['X-API-Key' => $this->apiKey()]);
    }

    /**
     * Esegue una chiamata e restituisce il JSON decodificato.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload = [], array $query = []): array
    {
        $url = $this->url($path, $query);

        $client = $this->client();

        $response = match (strtoupper($method)) {
            'GET' => $client->get($url),
            'POST' => $client->post($url, $payload),
            'PUT' => $client->put($url, $payload),
            'PATCH' => $client->patch($url, $payload),
            'DELETE' => $client->delete($url, $payload),
            default => throw new InvalidArgumentException("Metodo HTTP non supportato: {$method}."),
        };

        return $this->decode($response);
    }

    /**
     * Valida la risposta e restituisce il body JSON come array.
     *
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        $decoded = $response->throw()->json();

        if (! is_array($decoded)) {
            throw new RuntimeException('Kapso ha restituito una risposta inattesa.');
        }

        return $decoded;
    }

    /**
     * Costruisce l'URL assoluto a partire dal path e dalla query string.
     *
     * @param  array<string, mixed>  $query
     */
    private function url(string $path, array $query = []): string
    {
        $url = $this->baseUrl().'/'.ltrim($path, '/');

        $query = $this->clean($query);

        return $query === [] ? $url : $url.'?'.http_build_query($query);
    }

    private function baseUrl(): string
    {
        return $this->setting('base_url');
    }

    private function apiKey(): string
    {
        return $this->setting('api_key');
    }

    /**
     * ID del numero WhatsApp su cui operare.
     */
    public function phoneId(): string
    {
        return $this->setting('phone_id');
    }

    /**
     * ID del WhatsApp Business Account (serve a template, flows e numeri).
     */
    public function businessId(): string
    {
        return $this->setting('business_id');
    }

    /**
     * Legge una chiave di configurazione obbligatoria.
     */
    private function setting(string $key): string
    {
        $value = config("laravel-kapso.kapso.{$key}");

        if (! is_string($value) || $value === '') {
            throw new RuntimeException("Configurazione Kapso incompleta: manca {$key}.");
        }

        return rtrim($value, '/');
    }

    /**
     * Rimuove dal payload le chiavi non valorizzate.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function clean(array $payload): array
    {
        return array_filter(
            $payload,
            static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== [],
        );
    }

    /**
     * Costruisce un oggetto media: URL http(s) diventa "link", altrimenti "id".
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function media(string $media, array $extra = []): array
    {
        $key = str_starts_with($media, 'http://') || str_starts_with($media, 'https://')
            ? 'link'
            : 'id';

        return $this->clean(array_merge([$key => $media], $extra));
    }

    /* REGISTER WEBHOOK */

    /**
     * @return array<string, mixed>
     */
    public function registerWebhook(): array
    {
        $baseUrl = config('laravel-kapso.kapso.base_url');
        $phoneId = config('laravel-kapso.kapso.phone_id');
        $apiKey = config('laravel-kapso.kapso.api_key');

        $response = Http::withHeaders([
            'X-API-Key' => $apiKey,
        ])->post(
            "https://api.kapso.ai/platform/v1/whatsapp/phone_numbers/{$phoneId}/webhooks",
            [
                'whatsapp_webhook' => [
                    'kind' => 'kapso',
                    'url' => route('kapso.webhook'),

                    'events' => [
                        'whatsapp.message.received',
                    ],

                    'secret_key' => config(
                        'laravel-kapso.webhook.secret',
                    ),
                ],
            ],
        );

        $response->throw();

        return $response->json();
    }

    /* =======================================================================
     | Messaggi - invio      POST /{phone_number_id}/messages
     ======================================================================= */

    /**
     * Invia un payload messaggio gia' costruito. Utile per i tipi non coperti
     * da un metodo dedicato.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sendMessage(array $payload): array
    {
        return $this->request(
            'POST',
            $this->phoneId().'/messages',
            array_merge(['messaging_product' => 'whatsapp'], $this->clean($payload)),
        );
    }

    /**
     * Messaggio di testo.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendText(string $to, string $text, bool $previewUrl = false, array $options = []): array
    {
        return $this->sendMessage(array_merge([
            'to' => $to,
            'type' => 'text',
            'text' => $this->clean([
                'body' => $text,
                'preview_url' => $previewUrl ?: null,
            ]),
        ], $options));
    }

    /**
     * Immagine (jpeg, png - max 5MB).
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendImage(string $to, string $media, string $caption = '', array $options = []): array
    {
        return $this->sendMessage(array_merge([
            'to' => $to,
            'type' => 'image',
            'image' => $this->media($media, ['caption' => $caption]),
        ], $options));
    }

    /**
     * Video (mp4, 3gp - max 16MB).
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendVideo(string $to, string $media, string $caption = '', array $options = []): array
    {
        return $this->sendMessage(array_merge([
            'to' => $to,
            'type' => 'video',
            'video' => $this->media($media, ['caption' => $caption]),
        ], $options));
    }

    /**
     * Audio (aac, mp3, ogg, opus - max 16MB).
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendAudio(string $to, string $media, bool $voice = false, array $options = []): array
    {
        return $this->sendMessage(array_merge([
            'to' => $to,
            'type' => 'audio',
            'audio' => $this->media($media, ['voice' => $voice ?: null]),
        ], $options));
    }

    /**
     * Nota vocale: file .ogg con codec OPUS, con trascrizione automatica.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendVoice(string $to, string $media, array $options = []): array
    {
        return $this->sendAudio($to, $media, true, $options);
    }

    /**
     * Documento (pdf, doc, docx, ppt, pptx, xls, xlsx - max 100MB).
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendDocument(string $to, string $media, string $caption = '', string $filename = '', array $options = []): array
    {
        return $this->sendMessage(array_merge([
            'to' => $to,
            'type' => 'document',
            'document' => $this->media($media, [
                'caption' => $caption,
                'filename' => $filename,
            ]),
        ], $options));
    }

    /**
     * Sticker WEBP (statico max 100KB, animato max 500KB).
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendSticker(string $to, string $media, array $options = []): array
    {
        return $this->sendMessage(array_merge([
            'to' => $to,
            'type' => 'sticker',
            'sticker' => $this->media($media),
        ], $options));
    }

    /**
     * Posizione geografica.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendLocation(string $to, float $latitude, float $longitude, string $name = '', string $address = '', array $options = []): array
    {
        return $this->sendMessage(array_merge([
            'to' => $to,
            'type' => 'location',
            'location' => $this->clean([
                'latitude' => $latitude,
                'longitude' => $longitude,
                'name' => $name,
                'address' => $address,
            ]),
        ], $options));
    }

    /**
     * Una o piu' schede contatto.
     *
     * @param  array<int, array<string, mixed>>  $contacts
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendContacts(string $to, array $contacts, array $options = []): array
    {
        return $this->sendMessage(array_merge([
            'to' => $to,
            'type' => 'contacts',
            'contacts' => array_values($contacts),
        ], $options));
    }

    /**
     * Reazione emoji a un messaggio ricevuto.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendReaction(string $to, string $messageId, string $emoji, array $options = []): array
    {
        return $this->sendMessage(array_merge([
            'to' => $to,
            'type' => 'reaction',
            'reaction' => [
                'message_id' => $messageId,
                'emoji' => $emoji,
            ],
        ], $options));
    }

    /**
     * Rimuove una reazione (emoji vuota).
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function removeReaction(string $to, string $messageId, array $options = []): array
    {
        return $this->sendMessage(array_merge([
            'to' => $to,
            'type' => 'reaction',
            'reaction' => [
                'message_id' => $messageId,
                'emoji' => '',
            ],
        ], $options));
    }

    /**
     * Messaggio template approvato: unico modo per aprire una conversazione.
     *
     * @param  array<int, array<string, mixed>>  $components
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendTemplate(string $to, string $name, string $language = 'en_US', array $components = [], array $options = []): array
    {
        return $this->sendMessage(array_merge([
            'to' => $to,
            'type' => 'template',
            'template' => $this->clean([
                'name' => $name,
                'language' => ['code' => $language],
                'components' => array_values($components),
            ]),
        ], $options));
    }

    /**
     * Segna un messaggio come letto, con indicatore "sta scrivendo" opzionale.
     *
     * @return array<string, mixed>
     */
    public function markAsRead(string $messageId, bool $typingIndicator = false): array
    {
        return $this->request('POST', $this->phoneId().'/messages', $this->clean([
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
            'typing_indicator' => $typingIndicator ? ['type' => 'text'] : null,
        ]));
    }

    /**
     * Template marketing.  POST /{phone_number_id}/marketing_messages
     *
     * @param  array<int, array<string, mixed>>  $components
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendMarketingTemplate(string $to, string $name, string $language = 'en_US', array $components = [], array $options = []): array
    {
        return $this->request('POST', $this->phoneId().'/marketing_messages', array_merge([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => $this->clean([
                'name' => $name,
                'language' => ['code' => $language],
                'components' => array_values($components),
            ]),
        ], $this->clean($options)));
    }

    /* =======================================================================
     | Messaggi interattivi      POST /{phone_number_id}/messages
     ======================================================================= */

    /**
     * Messaggio interattivo generico: utile per i tipi non coperti sotto.
     *
     * @param  array<string, mixed>  $interactive
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendInteractive(string $to, array $interactive, array $options = []): array
    {
        return $this->sendMessage(array_merge([
            'to' => $to,
            'type' => 'interactive',
            'interactive' => $this->clean($interactive),
        ], $options));
    }

    /**
     * Assembla il blocco "interactive" comune a tutti i tipi.
     *
     * @param  array<string, mixed>  $action
     * @param  array<string, mixed>  $header
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function interactive(string $to, string $type, array $action, string $body = '', array $header = [], string $footer = '', array $options = []): array
    {
        return $this->sendInteractive($to, [
            'type' => $type,
            'header' => $this->clean($header),
            'body' => $body === '' ? [] : ['text' => $body],
            'footer' => $footer === '' ? [] : ['text' => $footer],
            'action' => $action,
        ], $options);
    }

    /**
     * Header testuale da passare ai metodi interattivi.
     *
     * @return array<string, mixed>
     */
    public function textHeader(string $text): array
    {
        return ['type' => 'text', 'text' => $text];
    }

    /**
     * Header media (image, video, document) per i metodi interattivi.
     *
     * @return array<string, mixed>
     */
    public function mediaHeader(string $type, string $media, string $filename = ''): array
    {
        if (! in_array($type, ['image', 'video', 'document'], true)) {
            throw new InvalidArgumentException("Header media non supportato: {$type}.");
        }

        return [
            'type' => $type,
            $type => $this->media($media, ['filename' => $filename]),
        ];
    }

    /**
     * Bottoni di risposta rapida (massimo 3).
     *
     * @param  array<int, array<string, string>>  $buttons  coppie id/title, oppure bottoni gia' formattati
     * @param  array<string, mixed>  $header
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendButtons(string $to, string $body, array $buttons, array $header = [], string $footer = '', array $options = []): array
    {
        $formatted = array_map(static function (array $button): array {
            if (isset($button['type'])) {
                return $button;
            }

            return [
                'type' => 'reply',
                'reply' => [
                    'id' => $button['id'],
                    'title' => $button['title'],
                ],
            ];
        }, array_values($buttons));

        return $this->interactive($to, 'button', ['buttons' => $formatted], $body, $header, $footer, $options);
    }

    /**
     * Lista a scelta singola (massimo 10 righe complessive).
     *
     * @param  array<int, array<string, mixed>>  $sections
     * @param  array<string, mixed>  $header
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendList(string $to, string $body, string $buttonText, array $sections, array $header = [], string $footer = '', array $options = []): array
    {
        return $this->interactive($to, 'list', [
            'button' => $buttonText,
            'sections' => array_values($sections),
        ], $body, $header, $footer, $options);
    }

    /**
     * Bottone call-to-action che apre un URL.
     *
     * @param  array<string, mixed>  $header
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendCtaUrl(string $to, string $body, string $displayText, string $url, array $header = [], string $footer = '', array $options = []): array
    {
        return $this->interactive($to, 'cta_url', [
            'name' => 'cta_url',
            'parameters' => [
                'display_text' => $displayText,
                'url' => $url,
            ],
        ], $body, $header, $footer, $options);
    }

    /**
     * Invio di un WhatsApp Flow.
     *
     * @param  string  $flowAction  navigate oppure data_exchange
     * @param  array<string, mixed>  $flowActionPayload  screen e data iniziali del flow
     * @param  array<string, mixed>  $header
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendFlow(string $to, string $body, string $flowId, string $flowCta, string $flowToken = '', string $flowAction = 'navigate', array $flowActionPayload = [], array $header = [], string $footer = '', array $options = []): array
    {
        return $this->interactive($to, 'flow', [
            'name' => 'flow',
            'parameters' => $this->clean([
                'flow_message_version' => '3',
                'flow_id' => $flowId,
                'flow_cta' => $flowCta,
                'flow_token' => $flowToken,
                'flow_action' => $flowAction,
                'flow_action_payload' => $flowActionPayload,
            ]),
        ], $body, $header, $footer, $options);
    }

    /**
     * Chiede all'utente di condividere la propria posizione.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendLocationRequest(string $to, string $body, array $options = []): array
    {
        return $this->interactive($to, 'location_request_message', ['name' => 'send_location'], $body, [], '', $options);
    }

    /**
     * Chiede all'utente di condividere numero di telefono e contatti.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendContactInfoRequest(string $to, string $body, array $options = []): array
    {
        return $this->interactive($to, 'request_contact_info', ['name' => 'request_contact_info'], $body, [], '', $options);
    }

    /**
     * Chiede il permesso di chiamare l'utente.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendCallPermissionRequest(string $to, string $body, array $options = []): array
    {
        return $this->interactive($to, 'call_permission_request', ['name' => 'call_permission_request'], $body, [], '', $options);
    }

    /**
     * Raccolta indirizzo (disponibile solo in India e Singapore).
     *
     * @param  array<string, mixed>  $values  valori precompilati del modulo
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendAddressMessage(string $to, string $body, string $country, array $values = [], array $options = []): array
    {
        return $this->interactive($to, 'address_message', [
            'name' => 'address_message',
            'parameters' => $this->clean([
                'country' => $country,
                'values' => $values,
            ]),
        ], $body, [], '', $options);
    }

    /**
     * Carosello free-form con 2-10 card.
     *
     * @param  array<int, array<string, mixed>>  $cards
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendCarousel(string $to, string $body, array $cards, array $options = []): array
    {
        return $this->interactive($to, 'carousel', ['cards' => array_values($cards)], $body, [], '', $options);
    }

    /**
     * Messaggio con un singolo prodotto del catalogo.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendProduct(string $to, string $catalogId, string $productRetailerId, string $body = '', string $footer = '', array $options = []): array
    {
        return $this->interactive($to, 'product', [
            'catalog_id' => $catalogId,
            'product_retailer_id' => $productRetailerId,
        ], $body, [], $footer, $options);
    }

    /**
     * Messaggio multi prodotto (fino a 30 prodotti).
     *
     * @param  array<int, array<string, mixed>>  $sections
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendProductList(string $to, string $catalogId, string $headerText, string $body, array $sections, string $footer = '', array $options = []): array
    {
        return $this->interactive($to, 'product_list', [
            'catalog_id' => $catalogId,
            'sections' => array_values($sections),
        ], $body, $this->textHeader($headerText), $footer, $options);
    }

    /**
     * Messaggio che apre il catalogo completo.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendCatalog(string $to, string $body, string $thumbnailProductRetailerId = '', string $footer = '', array $options = []): array
    {
        return $this->interactive($to, 'catalog_message', $this->clean([
            'name' => 'catalog_message',
            'parameters' => $this->clean([
                'thumbnail_product_retailer_id' => $thumbnailProductRetailerId,
            ]),
        ]), $body, [], $footer, $options);
    }

    /**
     * Dettaglio ordine da pagare (Pix, Brasile).
     *
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $header
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendOrderDetails(string $to, string $body, array $parameters, array $header = [], string $footer = '', array $options = []): array
    {
        return $this->interactive($to, 'order_details', [
            'name' => 'review_and_pay',
            'parameters' => $parameters,
        ], $body, $header, $footer, $options);
    }

    /**
     * Aggiornamento di stato di un ordine gia' inviato (Pix, Brasile).
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendOrderStatus(string $to, string $body, string $referenceId, string $status, string $description = '', array $options = []): array
    {
        return $this->interactive($to, 'order_status', [
            'name' => 'review_order',
            'parameters' => $this->clean([
                'reference_id' => $referenceId,
                'order' => $this->clean([
                    'status' => $status,
                    'description' => $description,
                ]),
            ]),
        ], $body, [], '', $options);
    }

    /* =======================================================================
     | Messaggi - lettura
     ======================================================================= */

    /**
     * Elenco messaggi paginato.  GET /{phone_number_id}/messages
     *
     * Filtri: conversation_id, direction, status, since, until, limit, before,
     * after, fields (es. "kapso()" per le estensioni Kapso).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function listMessages(array $filters = []): array
    {
        return $this->request('GET', $this->phoneId().'/messages', [], $filters);
    }

    /**
     * Singolo messaggio per ID.  GET /{phone_number_id}/messages/{message_id}
     *
     * @return array<string, mixed>
     */
    public function getMessage(string $messageId, string $fields = ''): array
    {
        return $this->request('GET', $this->phoneId().'/messages/'.$messageId, [], ['fields' => $fields]);
    }

    /* =======================================================================
     | Username business
     ======================================================================= */

    /**
     * Username attualmente associato al numero.  GET /{phone_number_id}/username
     *
     * @return array<string, mixed>
     */
    public function getUsername(): array
    {
        return $this->request('GET', $this->phoneId().'/username');
    }

    /**
     * Richiede o cambia lo username.  POST /{phone_number_id}/username
     *
     * @param  string  $transferAction  none oppure force_transfer
     * @return array<string, mixed>
     */
    public function setUsername(string $username, string $transferAction = 'none'): array
    {
        return $this->request('POST', $this->phoneId().'/username', [
            'username' => $username,
            'transfer_action' => $transferAction,
        ]);
    }

    /**
     * Rilascia lo username.  DELETE /{phone_number_id}/username
     *
     * @return array<string, mixed>
     */
    public function deleteUsername(): array
    {
        return $this->request('DELETE', $this->phoneId().'/username');
    }

    /**
     * Username riservati suggeriti.  GET /{phone_number_id}/username_suggestions
     *
     * @return array<string, mixed>
     */
    public function getUsernameSuggestions(): array
    {
        return $this->request('GET', $this->phoneId().'/username_suggestions');
    }

    /* =======================================================================
     | Conversazioni
     ======================================================================= */

    /**
     * Elenco conversazioni.  GET /{phone_number_id}/conversations
     *
     * Filtri: status, assigned_user_id, unassigned, last_active_since,
     * last_active_until, phone_number, limit, before, after, fields.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function listConversations(array $filters = []): array
    {
        return $this->request('GET', $this->phoneId().'/conversations', [], $filters);
    }

    /**
     * Dettaglio conversazione.  GET /{phone_number_id}/conversations/{conversation_id}
     *
     * @return array<string, mixed>
     */
    public function getConversation(string $conversationId, string $fields = ''): array
    {
        return $this->request('GET', $this->phoneId().'/conversations/'.$conversationId, [], ['fields' => $fields]);
    }

    /* =======================================================================
     | Contatti
     ======================================================================= */

    /**
     * Elenco contatti.  GET /{phone_number_id}/contacts
     *
     * Filtri: wa_id, customer_id, has_customer, limit, before, after, fields.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function listContacts(array $filters = []): array
    {
        return $this->request('GET', $this->phoneId().'/contacts', [], $filters);
    }

    /**
     * Dettaglio contatto.  GET /{phone_number_id}/contacts/{wa_id}
     *
     * @return array<string, mixed>
     */
    public function getContact(string $waId, string $fields = ''): array
    {
        return $this->request('GET', $this->phoneId().'/contacts/'.$waId, [], ['fields' => $fields]);
    }

    /* =======================================================================
     | Chiamate
     ======================================================================= */

    /**
     * Elenco chiamate.  GET /{phone_number_id}/calls
     *
     * Filtri: direction, status, since, until, limit, before, after, fields.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function listCalls(array $filters = []): array
    {
        return $this->request('GET', $this->phoneId().'/calls', [], $filters);
    }

    /**
     * Azione generica sulla Calling API.  POST /{phone_number_id}/calls
     *
     * @param  string  $action  connect, pre_accept, accept, reject o terminate
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function callAction(string $action, array $payload = []): array
    {
        return $this->request('POST', $this->phoneId().'/calls', array_merge([
            'messaging_product' => 'whatsapp',
            'action' => $action,
        ], $this->clean($payload)));
    }

    /**
     * Avvia una chiamata verso un utente.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function connectCall(string $to, string $sdp, string $sdpType = 'offer', array $options = []): array
    {
        return $this->callAction('connect', array_merge([
            'to' => $to,
            'session' => ['sdp_type' => $sdpType, 'sdp' => $sdp],
        ], $options));
    }

    /**
     * Pre accetta una chiamata in ingresso (riduce il tempo di connessione).
     *
     * @return array<string, mixed>
     */
    public function preAcceptCall(string $callId, string $sdp, string $sdpType = 'answer'): array
    {
        return $this->callAction('pre_accept', [
            'call_id' => $callId,
            'session' => ['sdp_type' => $sdpType, 'sdp' => $sdp],
        ]);
    }

    /**
     * Accetta una chiamata in ingresso.
     *
     * @return array<string, mixed>
     */
    public function acceptCall(string $callId, string $sdp, string $sdpType = 'answer'): array
    {
        return $this->callAction('accept', [
            'call_id' => $callId,
            'session' => ['sdp_type' => $sdpType, 'sdp' => $sdp],
        ]);
    }

    /**
     * Rifiuta una chiamata in ingresso.
     *
     * @return array<string, mixed>
     */
    public function rejectCall(string $callId): array
    {
        return $this->callAction('reject', ['call_id' => $callId]);
    }

    /**
     * Chiude una chiamata in corso.
     *
     * @return array<string, mixed>
     */
    public function terminateCall(string $callId): array
    {
        return $this->callAction('terminate', ['call_id' => $callId]);
    }

    /**
     * Stato del permesso di chiamata per un utente.
     * GET /{phone_number_id}/call_permissions
     *
     * @return array<string, mixed>
     */
    public function getCallPermissions(string $userWaId): array
    {
        return $this->request('GET', $this->phoneId().'/call_permissions', [], ['user_wa_id' => $userWaId]);
    }

    /* =======================================================================
     | Media
     ======================================================================= */

    /**
     * Carica un file e restituisce il media ID.  POST /{phone_number_id}/media
     *
     * @return array<string, mixed>
     */
    public function uploadMedia(string $path, string $mimeType = ''): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("File non leggibile: {$path}.");
        }

        if ($mimeType === '' && function_exists('mime_content_type')) {
            $detected = mime_content_type($path);
            $mimeType = $detected === false ? '' : $detected;
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException("Impossibile aprire il file: {$path}.");
        }

        $headers = $mimeType === '' ? [] : ['Content-Type' => $mimeType];

        $response = $this->client()
            ->attach('file', $handle, basename($path), $headers)
            ->post($this->url($this->phoneId().'/media'), ['messaging_product' => 'whatsapp']);

        return $this->decode($response);
    }

    /**
     * URL di download temporaneo (valido 5 minuti).  GET /{media_id}
     *
     * @return array<string, mixed>
     */
    public function getMediaUrl(string $mediaId): array
    {
        return $this->request('GET', $mediaId, [], ['phone_number_id' => $this->phoneId()]);
    }

    /**
     * Elimina un media.  DELETE /{media_id}
     *
     * @return array<string, mixed>
     */
    public function deleteMedia(string $mediaId, bool $verifyPhoneNumber = true): array
    {
        return $this->request('DELETE', $mediaId, [], $verifyPhoneNumber ? ['phone_number_id' => $this->phoneId()] : []);
    }

    /**
     * Scarica il contenuto binario di un media.  GET /media_download
     *
     * @param  string  $token  token firmato restituito nel campo download_url
     */
    public function downloadMedia(string $token): string
    {
        return $this->client()
            ->get($this->url('media_download', ['token' => $token]))
            ->throw()
            ->body();
    }

    /* =======================================================================
     | Template
     ======================================================================= */

    /**
     * Elenco template.  GET /{business_account_id}/message_templates
     *
     * Filtri: name, status, category, language, limit.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function listTemplates(array $filters = []): array
    {
        return $this->request('GET', $this->businessId().'/message_templates', [], $filters);
    }

    /**
     * Crea un template.  POST /{business_account_id}/message_templates
     *
     * @param  string  $category  AUTHENTICATION, MARKETING o UTILITY
     * @param  array<int, array<string, mixed>>  $components
     * @param  string  $parameterFormat  NAMED oppure POSITIONAL
     * @return array<string, mixed>
     */
    public function createTemplate(string $name, string $language, string $category, array $components, string $parameterFormat = ''): array
    {
        return $this->request('POST', $this->businessId().'/message_templates', $this->clean([
            'name' => $name,
            'language' => $language,
            'category' => $category,
            'parameter_format' => $parameterFormat,
            'components' => array_values($components),
        ]));
    }

    /**
     * Aggiorna un template esistente.
     * POST /{business_account_id}/message_templates?hsm_id=...
     *
     * @param  array<string, mixed>  $payload  components, category, ...
     * @return array<string, mixed>
     */
    public function updateTemplate(string $templateId, array $payload): array
    {
        return $this->request('POST', $this->businessId().'/message_templates', $this->clean($payload), ['hsm_id' => $templateId]);
    }

    /**
     * Elimina un template per nome oppure per ID.
     * DELETE /{business_account_id}/message_templates
     *
     * @return array<string, mixed>
     */
    public function deleteTemplate(string $name = '', string $templateId = ''): array
    {
        if ($name === '' && $templateId === '') {
            throw new InvalidArgumentException('Serve il nome oppure l\'ID del template da eliminare.');
        }

        return $this->request('DELETE', $this->businessId().'/message_templates', [], [
            'name' => $name,
            'hsm_id' => $templateId,
        ]);
    }

    /**
     * Dettaglio template.
     * GET /{business_account_id}/message_templates/{template_id}
     *
     * @return array<string, mixed>
     */
    public function getTemplate(string $templateId, string $fields = ''): array
    {
        return $this->request('GET', $this->businessId().'/message_templates/'.$templateId, [], ['fields' => $fields]);
    }

    /* =======================================================================
     | Profilo business
     ======================================================================= */

    /**
     * Profilo business del numero.
     * GET /{phone_number_id}/whatsapp_business_profile
     *
     * @return array<string, mixed>
     */
    public function getBusinessProfile(string $fields = ''): array
    {
        return $this->request('GET', $this->phoneId().'/whatsapp_business_profile', [], ['fields' => $fields]);
    }

    /**
     * Aggiorna il profilo business.
     * POST /{phone_number_id}/whatsapp_business_profile
     *
     * @param  array<string, mixed>  $profile  about, address, description, email, vertical, websites, profile_picture_handle
     * @return array<string, mixed>
     */
    public function updateBusinessProfile(array $profile): array
    {
        return $this->request('POST', $this->phoneId().'/whatsapp_business_profile', array_merge([
            'messaging_product' => 'whatsapp',
        ], $this->clean($profile)));
    }

    /* =======================================================================
     | Utenti bloccati
     ======================================================================= */

    /**
     * Elenco utenti bloccati.  GET /{phone_number_id}/block_users
     *
     * @return array<string, mixed>
     */
    public function listBlockedUsers(): array
    {
        return $this->request('GET', $this->phoneId().'/block_users');
    }

    /**
     * Blocca uno o piu' utenti.  POST /{phone_number_id}/block_users
     *
     * @param  array<int, string>  $users  numeri in formato wa_id
     * @return array<string, mixed>
     */
    public function blockUsers(array $users): array
    {
        return $this->request('POST', $this->phoneId().'/block_users', [
            'block_users' => $this->blockUsersPayload($users),
        ]);
    }

    /**
     * Sblocca uno o piu' utenti.  DELETE /{phone_number_id}/block_users
     *
     * @param  array<int, string>  $users  numeri in formato wa_id
     * @return array<string, mixed>
     */
    public function unblockUsers(array $users): array
    {
        return $this->request('DELETE', $this->phoneId().'/block_users', [
            'block_users' => $this->blockUsersPayload($users),
        ]);
    }

    /**
     * @param  array<int, string>  $users
     * @return array<int, array<string, string>>
     */
    private function blockUsersPayload(array $users): array
    {
        return array_map(static fn (string $user): array => ['user' => $user], array_values($users));
    }

    /* =======================================================================
     | Flows
     ======================================================================= */

    /**
     * Flows del business account.  GET /{business_account_id}/flows
     *
     * @return array<string, mixed>
     */
    public function listFlows(): array
    {
        return $this->request('GET', $this->businessId().'/flows');
    }

    /**
     * Crea un flow sul business account.  POST /{business_account_id}/flows
     *
     * @param  array<int, string>  $categories  SIGN_UP, SIGN_IN, APPOINTMENT_BOOKING, LEAD_GENERATION, CONTACT_US, CUSTOMER_SUPPORT, SURVEY, OTHER
     * @param  array<string, mixed>  $options  clone_flow_id, endpoint_uri, flow_json, publish
     * @return array<string, mixed>
     */
    public function createFlow(string $name, array $categories, array $options = []): array
    {
        return $this->request('POST', $this->businessId().'/flows', array_merge([
            'name' => $name,
            'categories' => array_values($categories),
        ], $this->clean($options)));
    }

    /**
     * Flows visibili al numero.  GET /{phone_number_id}/flows
     *
     * @return array<string, mixed>
     */
    public function listPhoneNumberFlows(): array
    {
        return $this->request('GET', $this->phoneId().'/flows');
    }

    /**
     * Crea un flow sul numero.  POST /{phone_number_id}/flows
     *
     * @param  array<int, string>  $categories
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function createPhoneNumberFlow(string $name, array $categories, array $options = []): array
    {
        return $this->request('POST', $this->phoneId().'/flows', array_merge([
            'name' => $name,
            'categories' => array_values($categories),
        ], $this->clean($options)));
    }

    /**
     * Dettaglio flow.  GET /{flow_id}
     *
     * @return array<string, mixed>
     */
    public function getFlow(string $flowId, string $fields = ''): array
    {
        return $this->request('GET', $flowId, [], [
            'fields' => $fields,
            'phone_number_id' => $this->phoneId(),
        ]);
    }

    /**
     * Aggiorna i metadati di un flow.  POST /{flow_id}
     *
     * @param  array<string, mixed>  $payload  name, categories, endpoint_uri, application_id
     * @return array<string, mixed>
     */
    public function updateFlow(string $flowId, array $payload): array
    {
        return $this->request('POST', $flowId, $this->clean($payload));
    }

    /**
     * Elimina un flow in bozza.  DELETE /flows/{flow_id}
     *
     * @return array<string, mixed>
     */
    public function deleteFlow(string $flowId): array
    {
        return $this->request('DELETE', 'flows/'.$flowId);
    }

    /**
     * Pubblica un flow.  POST /{flow_id}/publish
     *
     * @return array<string, mixed>
     */
    public function publishFlow(string $flowId): array
    {
        return $this->request('POST', $flowId.'/publish');
    }

    /**
     * Deprecata un flow pubblicato.  POST /{flow_id}/deprecate
     *
     * @return array<string, mixed>
     */
    public function deprecateFlow(string $flowId): array
    {
        return $this->request('POST', $flowId.'/deprecate');
    }

    /**
     * Asset di un flow.  GET /{flow_id}/assets
     *
     * @return array<string, mixed>
     */
    public function getFlowAssets(string $flowId): array
    {
        return $this->request('GET', $flowId.'/assets');
    }

    /**
     * Carica il Flow JSON.  POST /{flow_id}/assets
     *
     * @return array<string, mixed>
     */
    public function uploadFlowJson(string $flowId, string $path, string $assetType = 'FLOW_JSON'): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("File non leggibile: {$path}.");
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException("Impossibile aprire il file: {$path}.");
        }

        $response = $this->client()
            ->attach('file', $handle, basename($path), ['Content-Type' => 'application/json'])
            ->post($this->url($flowId.'/assets'), [
                'name' => basename($path),
                'asset_type' => $assetType,
            ]);

        return $this->decode($response);
    }

    /* =======================================================================
     | Numeri di telefono
     ======================================================================= */

    /**
     * Numeri del business account.  GET /{business_account_id}/phone_numbers
     *
     * @return array<string, mixed>
     */
    public function listPhoneNumbers(string $fields = ''): array
    {
        return $this->request('GET', $this->businessId().'/phone_numbers', [], ['fields' => $fields]);
    }

    /**
     * Dettaglio del numero configurato.  GET /{phone_number_id}
     *
     * @return array<string, mixed>
     */
    public function getPhoneNumber(string $fields = ''): array
    {
        return $this->request('GET', $this->phoneId(), [], ['fields' => $fields]);
    }

    /**
     * Aggiorna il PIN di verifica in due passaggi.  POST /{phone_number_id}
     *
     * @return array<string, mixed>
     */
    public function updatePhoneNumber(string $pin): array
    {
        return $this->request('POST', $this->phoneId(), ['pin' => $pin]);
    }
}
