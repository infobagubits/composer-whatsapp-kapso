<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Integrations\Kapso\Kapso;

beforeEach(function (): void {
    config()->set('laravel-kapso.kapso', [
        'base_url' => 'https://api.kapso.ai/meta/whatsapp/v24.0',
        'phone_id' => 'PHONE',
        'api_key' => 'KEY',
        'business_id' => 'WABA',
    ]);

    Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.TEST']]])]);
});

function kapso(): Kapso
{
    return app(Kapso::class);
}

/**
 * Sostituisce il factory HTTP: Http::fake() accumula gli stub e vince il primo
 * registrato, quindi per cambiare risposta serve un factory pulito.
 */
function rifaiHttp(mixed $body, int $status = 200): void
{
    Http::swap(new Factory);
    Http::fake(['*' => Http::response($body, $status)]);
}

it('autentica con X-API-Key e centra l\'endpoint dei messaggi', function (): void {
    kapso()->sendText('15551234567', 'Ciao');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.kapso.ai/meta/whatsapp/v24.0/PHONE/messages'
        && $request->hasHeader('X-API-Key', 'KEY')
        && $request->method() === 'POST');
});

it('costruisce il payload di un messaggio di testo', function (): void {
    kapso()->sendText('15551234567', 'Ciao');

    Http::assertSent(fn (Request $request): bool => $request->data() === [
        'messaging_product' => 'whatsapp',
        'to' => '15551234567',
        'type' => 'text',
        'text' => ['body' => 'Ciao'],
    ]);
});

it('aggiunge preview_url solo quando richiesto', function (): void {
    kapso()->sendText('15551234567', 'https://esempio.it', true);

    Http::assertSent(fn (Request $request): bool => $request->data()['text'] === [
        'body' => 'https://esempio.it',
        'preview_url' => true,
    ]);
});

it('usa link per gli URL e id per i media caricati', function (): void {
    kapso()->sendImage('15551234567', 'https://esempio.it/foto.jpg', 'Guarda qui');
    kapso()->sendImage('15551234567', '1013859600285441');

    Http::assertSent(fn (Request $request): bool => ($request->data()['image'] ?? null) === [
        'link' => 'https://esempio.it/foto.jpg',
        'caption' => 'Guarda qui',
    ]);

    Http::assertSent(fn (Request $request): bool => ($request->data()['image'] ?? null) === [
        'id' => '1013859600285441',
    ]);
});

it('invia un documento con nome file', function (): void {
    kapso()->sendDocument('15551234567', 'https://esempio.it/fattura.pdf', 'Fattura', 'fattura-12345.pdf');

    Http::assertSent(fn (Request $request): bool => $request->data()['document'] === [
        'link' => 'https://esempio.it/fattura.pdf',
        'caption' => 'Fattura',
        'filename' => 'fattura-12345.pdf',
    ]);
});

it('marca voice solo per le note vocali', function (): void {
    kapso()->sendVoice('15551234567', '1013859600285441');
    kapso()->sendAudio('15551234567', 'https://esempio.it/suono.mp3');

    Http::assertSent(fn (Request $request): bool => ($request->data()['audio'] ?? null) === [
        'id' => '1013859600285441',
        'voice' => true,
    ]);

    Http::assertSent(fn (Request $request): bool => ($request->data()['audio'] ?? null) === [
        'link' => 'https://esempio.it/suono.mp3',
    ]);
});

it('invia una posizione tenendo le coordinate a zero', function (): void {
    kapso()->sendLocation('15551234567', 0.0, -122.4194, 'Magazzino');

    Http::assertSent(fn (Request $request): bool => $request->data()['location'] === [
        'latitude' => 0.0,
        'longitude' => -122.4194,
        'name' => 'Magazzino',
    ]);
});

it('formatta i bottoni di risposta rapida', function (): void {
    kapso()->sendButtons('15551234567', 'Confermi?', [
        ['id' => 'si', 'title' => 'Si'],
        ['id' => 'no', 'title' => 'No'],
    ], footer: 'Grazie');

    Http::assertSent(fn (Request $request): bool => $request->data()['interactive'] === [
        'type' => 'button',
        'body' => ['text' => 'Confermi?'],
        'footer' => ['text' => 'Grazie'],
        'action' => [
            'buttons' => [
                ['type' => 'reply', 'reply' => ['id' => 'si', 'title' => 'Si']],
                ['type' => 'reply', 'reply' => ['id' => 'no', 'title' => 'No']],
            ],
        ],
    ]);
});

it('costruisce una lista con header testuale', function (): void {
    $sections = [['title' => 'Spedizione', 'rows' => [['id' => 'std', 'title' => 'Standard']]]];

    kapso()->sendList('15551234567', 'Scegli', 'Apri', $sections, kapso()->textHeader('Consegna'));

    Http::assertSent(fn (Request $request): bool => $request->data()['interactive']['header'] === ['type' => 'text', 'text' => 'Consegna']
        && $request->data()['interactive']['action'] === ['button' => 'Apri', 'sections' => $sections]);
});

it('invia un template con lingua e componenti', function (): void {
    kapso()->sendTemplate('15551234567', 'benvenuto', 'it', [['type' => 'body', 'parameters' => []]]);

    Http::assertSent(fn (Request $request): bool => $request->data()['template'] === [
        'name' => 'benvenuto',
        'language' => ['code' => 'it'],
        'components' => [['type' => 'body', 'parameters' => []]],
    ]);
});

it('marca come letto con indicatore di scrittura', function (): void {
    kapso()->markAsRead('wamid.ABC', true);

    Http::assertSent(fn (Request $request): bool => $request->data() === [
        'messaging_product' => 'whatsapp',
        'status' => 'read',
        'message_id' => 'wamid.ABC',
        'typing_indicator' => ['type' => 'text'],
    ]);
});

it('rimuove la reazione con emoji vuota', function (): void {
    kapso()->removeReaction('15551234567', 'wamid.ABC');

    Http::assertSent(fn (Request $request): bool => $request->data()['reaction'] === [
        'message_id' => 'wamid.ABC',
        'emoji' => '',
    ]);
});

it('usa marketing_messages per i template marketing', function (): void {
    kapso()->sendMarketingTemplate('15551234567', 'saldi');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.kapso.ai/meta/whatsapp/v24.0/PHONE/marketing_messages');
});

it('trasforma i filtri in query string e scarta i valori vuoti', function (): void {
    kapso()->listMessages(['direction' => 'inbound', 'limit' => 50, 'status' => '']);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.kapso.ai/meta/whatsapp/v24.0/PHONE/messages?direction=inbound&limit=50'
        && $request->method() === 'GET');
});

it('passa il phone number id quando chiede l\'URL di un media', function (): void {
    kapso()->getMediaUrl('MEDIA123');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.kapso.ai/meta/whatsapp/v24.0/MEDIA123?phone_number_id=PHONE');
});

it('omette il phone number id se la verifica e\' disattivata', function (): void {
    kapso()->deleteMedia('MEDIA123', false);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.kapso.ai/meta/whatsapp/v24.0/MEDIA123'
        && $request->method() === 'DELETE');
});

it('usa il business account id per i template', function (): void {
    kapso()->listTemplates(['status' => 'APPROVED']);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.kapso.ai/meta/whatsapp/v24.0/WABA/message_templates?status=APPROVED');
});

it('rifiuta la cancellazione di un template senza nome ne\' id', function (): void {
    kapso()->deleteTemplate();
})->throws(InvalidArgumentException::class);

it('impacchetta gli utenti da bloccare', function (): void {
    kapso()->blockUsers(['15551234567', '15559876543']);

    Http::assertSent(fn (Request $request): bool => $request->data() === [
        'block_users' => [
            ['user' => '15551234567'],
            ['user' => '15559876543'],
        ],
    ]);
});

it('sblocca gli utenti con una DELETE che porta il body', function (): void {
    kapso()->unblockUsers(['15551234567']);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
        && $request->data() === ['block_users' => [['user' => '15551234567']]]);
});

it('carica un media in multipart', function (): void {
    $path = sys_get_temp_dir().'/kapso-test-upload.txt';
    file_put_contents($path, 'contenuto');

    kapso()->uploadMedia($path, 'text/plain');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.kapso.ai/meta/whatsapp/v24.0/PHONE/media'
        && $request->isMultipart());

    unlink($path);
});

it('rifiuta un percorso file inesistente', function (): void {
    kapso()->uploadMedia('/percorso/che/non/esiste.jpg');
})->throws(InvalidArgumentException::class);

it('restituisce il corpo grezzo quando scarica un media', function (): void {
    rifaiHttp('BINARIO');

    expect(kapso()->downloadMedia('TOKEN'))->toBe('BINARIO');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.kapso.ai/meta/whatsapp/v24.0/media_download?token=TOKEN');
});

it('pubblica un flow sul suo endpoint', function (): void {
    kapso()->publishFlow('FLOW1');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.kapso.ai/meta/whatsapp/v24.0/FLOW1/publish'
        && $request->method() === 'POST');
});

it('elimina un flow sotto il prefisso flows', function (): void {
    kapso()->deleteFlow('FLOW1');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.kapso.ai/meta/whatsapp/v24.0/flows/FLOW1');
});

it('solleva un errore se manca una chiave di configurazione', function (): void {
    config()->set('laravel-kapso.kapso.api_key', null);

    kapso()->sendText('15551234567', 'Ciao');
})->throws(RuntimeException::class, 'Configurazione Kapso incompleta: manca api_key.');

it('solleva un errore se manca il business account id', function (): void {
    config()->set('laravel-kapso.kapso.business_id', null);

    kapso()->listTemplates();
})->throws(RuntimeException::class, 'Configurazione Kapso incompleta: manca business_id.');

it('propaga gli errori HTTP di Kapso', function (): void {
    rifaiHttp(['error' => ['message' => 'Invalid parameter']], 400);

    kapso()->sendText('15551234567', 'Ciao');
})->throws(RequestException::class);

it('rifiuta un header media non supportato', function (): void {
    kapso()->mediaHeader('audio', 'https://esempio.it/a.mp3');
})->throws(InvalidArgumentException::class);
