# Laravel Kapso

Il pacchetto semplifica l'integrazione con Kapso.ai permettendo di:

- utilizzare le API di Kapso per gestire messaggi WhatsApp;
- registrare webhook;
- definire callback personalizzati per gestire i messaggi ricevuti.

## Installazione

composer require infobagubits/whatsapp-kapso

## Configurazione

Dentro il file env ci devono essere queste keys:

- KAPSO_PHONE_ID -> ID del telefono registrato su Kaspo (lo posso registrate sulla piattaforma o con api)
- KAPSO_API_KEY -> api-key creata direttamente sul sito di Kapso
- KAPSO_WEBHOOK_SECRET -> token segreto che viene usato per motivi di sicurezza nel webhook per la verifica delle firme

## Registrazione del Webhook

Dopo aver configurato il numero WhatsApp, possiamo registrare il webhook con:
php artisan laravel-kapso:webhook-register

Il package registrerà automaticamente l'endpoint Laravel per ricevere gli eventi WhatsApp da Kapso.

## Pubblicare il file di configurazione laravel-kapso

php artisan laravel-kapso:webhook-register

questo pubblicherà il file laravel-kapso.php

## Esempio di come inviare un messaggio semplice

Dopo aver configurato tutto il necessario possiamo procedere importando la classe Kapso, che ci permette di accedere ai metodi messi a disposizione dal package.

Esempio:

$kapso = new Kapso(); // Classe del package

$to = '393445685734'; // Numero a cui inviare il messaggio

$message = 'Matteo mandami un messaggio'; // Messaggio da inviare

$kapso->sendText($to, $message);

# Esempio di come gestire un webhook

Quando pubblichiamo il file di configurazione laravel-kapso, la voce handler sarà inizialmente vuota:

'webhook' => [
'handler' => null,
],

Possiamo registrare una classe personalizzata per gestire i messaggi in arrivo con evento:

whatsapp.message.receivedreceived

Possiamo creare una classe handler nel nostro progetto esempio:
class KapsoHandler
{
/\*\*
_ @param array<string, mixed> $payload
_/
public function handle(?string $event, array $payload): void
{
Log::info('Kapso webhook ricevuto', [
'event' => $event,
'id' => $payload['id'] ?? null,
'payload' => $payload,
]);
}
}

Infine inseriamo nel file di configurazione il path della classe:
'webhook' => [ 'handler' => \App\Webhooks\KapsoHandler::class, .... ],

Da questo momento, quando Kapso invierà un evento whatsapp.message.received inviera i dati ricevuti al metodo handle
