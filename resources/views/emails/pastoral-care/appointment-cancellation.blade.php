<x-mail::message>
# Votre rendez-vous de soin pastoral a été annulé

Bonjour {{ $appointment->client_name }},

Nous vous confirmons que votre rendez-vous de soin pastoral a bien été annulé.

## Détails du rendez-vous annulé

**Date :** {{ $appointment->appointment_date->format('d/m/Y') }}
**Heure :** {{ $appointment->appointment_time->format('H:i') }}
**Pasteur :** {{ $pastor->first_name }} {{ $pastor->last_name }}

@if($appointment->cancellation_reason)
**Raison de l'annulation :** {{ $appointment->cancellation_reason }}
@endif

---

## Prendre un nouveau rendez-vous

Si vous souhaitez reprogrammer un rendez-vous, vous pouvez facilement le faire via notre système de réservation en ligne :

<x-mail::button :url="$bookingUrl" color="primary">
Prendre un nouveau rendez-vous
</x-mail::button>

Ou contactez directement {{ $pastor->first_name }} {{ $pastor->last_name }} : [{{ $pastor->email }}](mailto:{{ $pastor->email }})

---

## Besoin d'aide immédiate ?

Si vous traversez une période difficile et avez besoin d'un accompagnement spirituel urgent, n'hésitez pas à nous contacter :

- **Email :** [{{ $churchEmail }}](mailto:{{ $churchEmail }})
- **Téléphone :** {{ $churchPhone }}
- **Site web :** [{{ $churchWebsite }}]({{ $churchWebsite }})

Notre équipe pastorale reste disponible pour vous accompagner dans votre cheminement spirituel.

---

## Ressources spirituelles

En attendant votre prochain rendez-vous, nous vous encourageons à :

- Participer à nos cultes et événements communautaires
- Rejoindre un groupe de maison ou d'étude biblique
- Consulter les ressources spirituelles sur notre site web
- Prendre du temps personnel pour la prière et la méditation

Nous comprenons que la vie peut parfois présenter des défis inattendus. Sachez que nous sommes là pour vous soutenir dans votre parcours de foi.

Que la paix et la grâce de Dieu vous accompagnent,

L'équipe pastorale de {{ $churchName }}

---

**{{ $churchName }}**
{{ $churchEmail }} | {{ $churchPhone }}
[{{ $churchWebsite }}]({{ $churchWebsite }})
</x-mail::message>
