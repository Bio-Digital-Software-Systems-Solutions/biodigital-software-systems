@component('mail::message')
# Nouveau message

Bonjour **{{ $recipient->first_name }}**,

Vous avez reçu un nouveau message de **{{ $sender->first_name }} {{ $sender->last_name }}** :

---

@if($message->subject)
**Sujet :** {{ $message->subject }}
@endif

{{ $message->content }}

---

@component('mail::button', ['url' => $messageUrl])
📩 Voir le message
@endcomponent

Merci,<br>
L'équipe {{ config('app.name') }}
@endcomponent