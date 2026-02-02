<x-mail::message>
@if($recipientType === 'new_pastor')
# Nouveau rendez-vous pastoral transféré

Bonjour {{ $recipientName }},

Un rendez-vous de soin pastoral vous a été transféré par **{{ $oldPastor->first_name }} {{ $oldPastor->last_name }}**.

@elseif($recipientType === 'old_pastor')
# Rendez-vous pastoral transféré

Bonjour {{ $recipientName }},

Votre rendez-vous de soin pastoral avec **{{ $appointment->client_name }}** a été transféré à **{{ $newPastor->first_name }} {{ $newPastor->last_name }}**.

@else
# Changement de responsable pour votre rendez-vous

Bonjour {{ $recipientName }},

Votre rendez-vous de soin pastoral a été transféré de **{{ $oldPastor->first_name }} {{ $oldPastor->last_name }}** à **{{ $newPastor->first_name }} {{ $newPastor->last_name }}**.

@endif

## Détails du rendez-vous

**Date :** {{ $appointment->appointment_date->format('d/m/Y') }}
**Heure :** {{ $appointment->appointment_time->format('H:i') }}
**Durée :** {{ $appointment->duration_minutes }} minutes
**Type :** {{ $appointment->location_type === 'in_person' ? 'En présentiel' : ($appointment->location_type === 'zoom' ? 'Visioconférence' : 'Hybride') }}

@if($transferReason)
---

## Raison du transfert

{{ $transferReason }}

@endif

@if($recipientType === 'new_pastor')
---

## Informations du client

**Nom :** {{ $appointment->client_name }}
**Email :** [{{ $appointment->client_email }}](mailto:{{ $appointment->client_email }})
@if($appointment->client_phone)
**Téléphone :** {{ $appointment->client_phone }}
@endif

@if($appointment->notes)
**Notes :** {{ $appointment->notes }}
@endif

---

## Actions requises

Veuillez confirmer ce rendez-vous dès que possible. Le client a été notifié du changement de responsable.

@elseif($recipientType === 'old_pastor')
---

## Information

Ce rendez-vous n'apparaît plus dans votre liste de rendez-vous. {{ $newPastor->first_name }} {{ $newPastor->last_name }} en est désormais responsable.

@else
---

## Votre nouveau responsable

**{{ $newPastor->first_name }} {{ $newPastor->last_name }}**
Email : [{{ $newPastor->email }}](mailto:{{ $newPastor->email }})

---

## Information importante

Suite à ce transfert, votre rendez-vous est en attente de confirmation par votre nouveau responsable. Vous recevrez une notification dès que le rendez-vous sera confirmé.

Si vous avez des questions ou souhaitez modifier votre rendez-vous, n'hésitez pas à contacter {{ $newPastor->first_name }} {{ $newPastor->last_name }}.

@endif

---

## Nous contacter

**{{ $churchName }}**
Email : [{{ $churchEmail }}](mailto:{{ $churchEmail }})
Téléphone : {{ $churchPhone }}
Site web : [{{ $churchWebsite }}]({{ $churchWebsite }})

Que la paix du Seigneur soit avec vous,

L'équipe pastorale de {{ $churchName }}
</x-mail::message>
