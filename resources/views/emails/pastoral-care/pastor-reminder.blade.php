<x-mail::message>
# Rappel : Vous avez un rendez-vous pastoral demain

Bonjour {{ $pastor->first_name }},

Ceci est un rappel concernant votre rendez-vous de soin pastoral prévu pour **demain**.

## Informations sur le rendez-vous

**Client :** {{ $appointment->client_name }}
**Email :** {{ $appointment->client_email }}
@if($appointment->client_phone)
**Téléphone :** {{ $appointment->client_phone }}
@endif

**Date :** {{ $appointment->appointment_date->format('d/m/Y') }}
**Heure :** {{ $appointment->appointment_time->format('H:i') }}
**Durée :** {{ $appointment->duration_minutes }} minutes
**Type :** {{ $appointment->location_type === 'in_person' ? 'En présentiel' : ($appointment->location_type === 'zoom' ? 'Visioconférence' : 'Hybride') }}

@if($appointment->notes)
## Notes du client

{{ $appointment->notes }}
@endif

@if($appointment->location_type === 'zoom' && $appointment->zoom_link)
## Lien de visioconférence

[{{ $appointment->zoom_link }}]({{ $appointment->zoom_link }})
@endif

---

## Actions

<x-mail::button :url="$appointmentUrl" color="primary">
Voir le rendez-vous
</x-mail::button>

---

## Préparation suggérée

- Consultez les notes éventuelles du client
- Préparez l'espace de rencontre (en présentiel) ou testez la connexion (visio)
- Prenez un moment de prière pour ce temps d'accompagnement

Que le Seigneur vous guide dans cet entretien pastoral.

Cordialement,

**{{ $churchName }}**
{{ $churchEmail }} | {{ $churchPhone }}
</x-mail::message>
