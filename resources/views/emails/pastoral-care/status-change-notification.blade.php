<x-mail::message>
@if($recipientType === 'pastor')
# Mise à jour du statut de rendez-vous pastoral

Bonjour {{ $recipientName }},

Le rendez-vous de soin pastoral avec **{{ $appointment->client_name }}** a été {{ $statusAction }}.
@else
# Mise à jour de votre rendez-vous de soin pastoral

Bonjour {{ $recipientName }},

Votre rendez-vous de soin pastoral a été {{ $statusAction }}.
@endif

## Détails du rendez-vous

**Date :** {{ $appointment->appointment_date->format('d/m/Y') }}
**Heure :** {{ $appointment->appointment_time->format('H:i') }}
**Durée :** {{ $appointment->duration_minutes }} minutes
**Type :** {{ $appointment->location_type === 'in_person' ? 'En présentiel' : ($appointment->location_type === 'zoom' ? 'Visioconférence' : 'Hybride') }}
**Statut :** {{ ucfirst($statusLabel) }}

@if($recipientType === 'pastor')
## Client
**Nom :** {{ $appointment->client_name }}
**Email :** [{{ $appointment->client_email }}](mailto:{{ $appointment->client_email }})
@if($appointment->client_phone)
**Téléphone :** {{ $appointment->client_phone }}
@endif
@else
## Votre pasteur
**{{ $pastor->first_name }} {{ $pastor->last_name }}**
Email : [{{ $pastor->email }}](mailto:{{ $pastor->email }})
@endif

@if($newStatus === 'confirmed')
---

## Informations importantes

- **Présence :** Merci de vous présenter à l'heure convenue
- **Annulation :** Si vous ne pouvez pas être présent(e), veuillez nous prévenir au moins 24h à l'avance
@if($appointment->zoom_link)
- **Connexion :** [{{ $appointment->zoom_link }}]({{ $appointment->zoom_link }})
@endif

@elseif($newStatus === 'completed')
---

@if($recipientType === 'pastor')
## Actions de suivi

Vous pouvez ajouter des notes de suivi dans le système pour ce rendez-vous si nécessaire.
@else
## Merci pour votre confiance

Nous espérons que ce rendez-vous vous a été bénéfique. N'hésitez pas à nous contacter si vous avez besoin d'un autre rendez-vous.
@endif

@elseif($newStatus === 'cancelled')
---

## Rendez-vous annulé

@if($recipientType === 'pastor')
Ce rendez-vous a été annulé. Vous pouvez consulter les détails dans le système de gestion.
@else
Si vous souhaitez prendre un nouveau rendez-vous, vous pouvez le faire à tout moment via notre plateforme.
@endif

@elseif($newStatus === 'no_show')
---

@if($recipientType === 'pastor')
## Non-présentation

Le client ne s'est pas présenté au rendez-vous prévu. Vous pouvez le contacter pour reprogrammer si nécessaire.
@else
## Rendez-vous manqué

Nous avons constaté que vous n'avez pas pu assister à votre rendez-vous. Si vous souhaitez reprogrammer, n'hésitez pas à nous contacter.
@endif

@endif

---

## Nous contacter

**{{ $churchName }}**
Email : [{{ $churchEmail }}](mailto:{{ $churchEmail }})
Téléphone : {{ $churchPhone }}
Site web : [{{ $churchWebsite }}]({{ $churchWebsite }})

@if($recipientType === 'client')
**Note :** Cet email a été envoyé automatiquement. Pour toute question, veuillez contacter {{ $pastor->first_name }} {{ $pastor->last_name }} à l'adresse [{{ $pastor->email }}](mailto:{{ $pastor->email }}).
@endif

Que la paix du Seigneur soit avec vous,

L'équipe pastorale de {{ $churchName }}
</x-mail::message>
