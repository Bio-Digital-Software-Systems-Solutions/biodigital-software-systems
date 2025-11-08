<x-mail::message>
# Confirmation de votre rendez-vous de soin pastoral

Bonjour {{ $appointment->client_name }},

Nous accusons réception de votre demande de rendez-vous de soin pastoral avec notre équipe pastorale. Votre rendez-vous a été enregistré avec succès.

## Détails du rendez-vous

**Date :** {{ $appointment->appointment_date->format('d/m/Y') }}
**Heure :** {{ $appointment->appointment_time->format('H:i') }}
**Durée :** {{ $appointment->duration_minutes }} minutes
**Type :** {{ $appointment->location_type === 'in_person' ? 'En présentiel' : ($appointment->location_type === 'zoom' ? 'Visioconférence' : 'Hybride') }}

@if($appointment->zoom_link)
**Lien de connexion :** [{{ $appointment->zoom_link }}]({{ $appointment->zoom_link }})
@endif

## Votre pasteur

**{{ $pastor->first_name }} {{ $pastor->last_name }}**
Email : [{{ $pastor->email }}](mailto:{{ $pastor->email }})

@if($appointment->notes)
## Notes
{{ $appointment->notes }}
@endif

---

## Actions requises

**Veuillez confirmer votre présence** en cliquant sur le bouton ci-dessous avant {{ $appointment->appointment_date->subDays(1)->format('d/m/Y') }} :

<x-mail::button :url="$confirmUrl" color="success">
Confirmer le rendez-vous
</x-mail::button>

Si vous ne pouvez pas être présent(e), vous pouvez annuler votre rendez-vous :

<x-mail::button :url="$cancelUrl" color="error">
Annuler le rendez-vous
</x-mail::button>

---

## Informations importantes

- **Confirmation requise :** Ce rendez-vous doit être confirmé avant la date limite indiquée ci-dessus
- **Annulation :** Les annulations doivent être effectuées au moins 24h à l'avance
- **Reprogrammation :** Pour reprogrammer, veuillez d'abord annuler ce rendez-vous puis prendre un nouveau créneau
- **Contact d'urgence :** En cas d'urgence, contactez-nous au {{ $churchPhone }}

## À propos du soin pastoral

Le soin pastoral est un accompagnement spirituel offert par notre église pour vous soutenir dans votre cheminement de foi et répondre à vos préoccupations personnelles et spirituelles.

---

## Nous contacter

**{{ $churchName }}**
Email : [{{ $churchEmail }}](mailto:{{ $churchEmail }})
Téléphone : {{ $churchPhone }}
Site web : [{{ $churchWebsite }}]({{ $churchWebsite }})

**Note :** Cet email a été envoyé automatiquement. Pour toute question, veuillez répondre directement à {{ $pastor->first_name }} {{ $pastor->last_name }} à l'adresse [{{ $pastor->email }}](mailto:{{ $pastor->email }}).

Que la paix du Seigneur soit avec vous,

L'équipe pastorale de {{ $churchName }}
</x-mail::message>
