<x-mail::message>
# Rappel : Votre rendez-vous de soin pastoral demain

Bonjour {{ $appointment->client_name }},

Nous espérons que vous allez bien. Ceci est un rappel amical concernant votre rendez-vous de soin pastoral prévu pour **demain**.

## Détails du rendez-vous

**Date :** {{ $appointment->appointment_date->format('d/m/Y') }}
**Heure :** {{ $appointment->appointment_time->format('H:i') }}
**Durée :** {{ $appointment->duration_minutes }} minutes
**Type :** {{ $appointment->location_type === 'in_person' ? 'En présentiel' : ($appointment->location_type === 'zoom' ? 'Visioconférence' : 'Hybride') }}

## Votre pasteur

**{{ $pastor->first_name }} {{ $pastor->last_name }}**
Email : [{{ $pastor->email }}](mailto:{{ $pastor->email }})

@if($appointment->location_type === 'zoom' && $appointment->zoom_link)
## Lien de connexion

Pour rejoindre la visioconférence demain :
[{{ $appointment->zoom_link }}]({{ $appointment->zoom_link }})

**Important :** Nous vous recommandons de tester votre connexion 15 minutes avant le rendez-vous.
@endif

@if($appointment->location_type === 'in_person')
## Lieu de rendez-vous

**{{ $churchName }}**

Merci d'arriver 5 minutes avant l'heure prévue. En cas de difficulté pour nous trouver, contactez-nous au {{ $churchPhone }}.
@endif

---

## Vous ne pouvez plus vous présenter ?

Si un imprévu vous empêche d'être présent(e) demain, merci de nous prévenir le plus rapidement possible :

<x-mail::button :url="$cancelUrl" color="error">
Annuler le rendez-vous
</x-mail::button>

Ou contactez directement {{ $pastor->first_name }} {{ $pastor->last_name }} : [{{ $pastor->email }}](mailto:{{ $pastor->email }})

---

## Préparation

Pour tirer le meilleur parti de votre temps avec {{ $pastor->first_name }}, vous pouvez :

- Réfléchir aux sujets que vous aimeriez aborder
- Préparer vos questions sur la foi, la spiritualité ou les défis personnels
- Prendre un moment de prière avant notre rencontre

---

## Contact d'urgence

En cas d'urgence le jour même, contactez-nous :
- Email : [{{ $churchEmail }}](mailto:{{ $churchEmail }})
- Téléphone : {{ $churchPhone }}

Nous avons hâte de vous rencontrer demain.

Que Dieu vous bénisse,

{{ $pastor->first_name }} {{ $pastor->last_name }}
{{ $churchName }}
</x-mail::message>
