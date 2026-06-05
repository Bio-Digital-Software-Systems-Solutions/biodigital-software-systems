<x-mail::message>
# Nouveau rendez-vous de soin pastoral

Bonjour {{ $pastor->first_name }},

Un nouveau rendez-vous de soin pastoral a été planifié avec vous. Voici les détails :

## Détails du rendez-vous

**Client :** {{ $appointment->client_name }}
**Email :** [{{ $appointment->client_email }}](mailto:{{ $appointment->client_email }})
@if($appointment->client_phone)
**Téléphone :** {{ $appointment->client_phone }}
@endif

**Date :** {{ $appointment->appointment_date->format('d/m/Y') }}
**Heure :** {{ $appointment->appointment_time->format('H:i') }}
**Durée :** {{ $appointment->duration_minutes }} minutes
**Type :** {{ $appointment->location_type === 'in_person' ? 'En présentiel' : ($appointment->location_type === 'zoom' ? 'Visioconférence' : 'Hybride') }}

@if($appointment->location_type === 'zoom' && $appointment->zoom_link)
**Lien Zoom :** [{{ $appointment->zoom_link }}]({{ $appointment->zoom_link }})
@endif

@if($appointment->notes)
## Notes du client
{{ $appointment->notes }}
@endif

---

## Actions requises

**Statut actuel :** {{ $appointment->status === 'pending' ? 'En attente de confirmation' : ucfirst($appointment->status) }}

Le client a reçu un email de confirmation avec des liens pour confirmer ou annuler le rendez-vous.

<x-mail::button :url="$appointmentUrl" color="primary">
Voir le rendez-vous
</x-mail::button>

<x-mail::button :url="$dashboardUrl" color="secondary">
Tableau de bord pastoral
</x-mail::button>

---

## Préparation recommandée

Pour ce rendez-vous de soin pastoral, nous vous suggérons de :

- Prendre un moment de prière pour cette rencontre
- Réviser les notes si c'est un suivi
- Préparer un espace calme et confidentiel
- Si c'est en visioconférence, vérifier votre connexion technique

@if($appointment->location_type === 'zoom')
## Instructions pour la visioconférence

- Rejoignez la réunion 5 minutes avant l'heure prévue
- Assurez-vous d'être dans un environnement calme et privé
- Vérifiez votre audio et vidéo avant la connexion
- Ayez votre Bible et bloc-notes à portée de main
@endif

---

## Contact avec le client

Le client vous a été assigné pour ce rendez-vous. N'hésitez pas à le contacter directement si nécessaire :

**{{ $appointment->client_name }}**
Email : [{{ $appointment->client_email }}](mailto:{{ $appointment->client_email }})
@if($appointment->client_phone)
Téléphone : {{ $appointment->client_phone }}
@endif

---

## Gestion des rendez-vous

Depuis votre tableau de bord, vous pouvez :

- Confirmer ou modifier le statut du rendez-vous
- Ajouter des notes avant ou après la rencontre
- Gérer vos disponibilités
- Consulter l'historique de vos rendez-vous

---

**Important :** Ce rendez-vous nécessite une confirmation du client. Si le client n'a pas confirmé 24h avant la date prévue, le rendez-vous sera automatiquement annulé.

Que Dieu vous bénisse dans ce ministère de soin pastoral.

L'équipe administrative de {{ $churchName }}

---

**Assistance technique :** [{{ $churchEmail }}](mailto:{{ $churchEmail }})
</x-mail::message>
