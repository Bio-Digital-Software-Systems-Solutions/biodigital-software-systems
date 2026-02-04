<x-mail::message>
# Rendez-vous de soin pastoral modifie

@if($recipientType === 'client')
Bonjour {{ $appointment->client_name ?? 'cher(e) membre' }},

Votre rendez-vous de soin pastoral a ete modifie.
@else
Bonjour {{ $pastor->first_name }},

Un rendez-vous de soin pastoral qui vous concerne a ete modifie.
@endif

## Modifications apportees

@foreach($changes as $field => $change)
**{{ $fieldLabels[$field] ?? ucfirst(str_replace('_', ' ', $field)) }} :**
- Avant : {{ $change['old'] }}
- Apres : {{ $change['new'] }}

@endforeach

---

## Details actuels du rendez-vous

**Client :** {{ $appointment->client_name }}
**Email :** [{{ $appointment->client_email }}](mailto:{{ $appointment->client_email }})
@if($appointment->client_phone)
**Telephone :** {{ $appointment->client_phone }}
@endif

**Date :** {{ $appointment->appointment_date->format('d/m/Y') }}
**Heure :** {{ $appointment->appointment_time->format('H:i') }}
**Duree :** {{ $appointment->duration_minutes }} minutes
**Type :** {{ $appointment->location_type === 'in_person' ? 'En presentiel' : ($appointment->location_type === 'zoom' ? 'Visioconference' : 'Hybride') }}

@if($appointment->location_type === 'zoom' && $appointment->zoom_link)
**Lien Zoom :** [{{ $appointment->zoom_link }}]({{ $appointment->zoom_link }})
@endif

@if($recipientType === 'pastor')
**Pasteur/Conseiller :** {{ $pastor->first_name }} {{ $pastor->last_name }}
@endif

**Statut :** {{ $appointment->status === 'pending' ? 'En attente de confirmation' : ($appointment->status === 'confirmed' ? 'Confirme' : ucfirst($appointment->status)) }}

---

<x-mail::button :url="$appointmentUrl" color="primary">
Voir le rendez-vous
</x-mail::button>

@if($recipientType === 'pastor')
<x-mail::button :url="$dashboardUrl" color="secondary">
Tableau de bord pastoral
</x-mail::button>
@endif

---

@if($recipientType === 'client')
## Besoin d'aide ?

Si vous avez des questions concernant ces modifications ou si vous souhaitez reporter le rendez-vous, n'hesitez pas a nous contacter.

@else
## Actions possibles

Depuis votre tableau de bord, vous pouvez :

- Confirmer ou modifier le statut du rendez-vous
- Contacter le client si necessaire
- Ajouter des notes concernant ces modifications
@endif

---

Cordialement,

L'equipe administrative de {{ $churchName }}

---

**Assistance :** [{{ $churchEmail }}](mailto:{{ $churchEmail }})
</x-mail::message>
