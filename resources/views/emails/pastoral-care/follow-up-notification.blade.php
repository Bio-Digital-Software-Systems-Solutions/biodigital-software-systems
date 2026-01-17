<x-mail::message>
    # Nouveau rendez-vous de suivi planifié

    Bonjour {{ $appointment->client_name }},

    Suite à votre précédent rendez-vous de soin pastoral, un nouveau rendez-vous de suivi a été planifié pour vous.

    ## Détails du rendez-vous de suivi

    **Date :** {{ $appointment->appointment_date->format('d/m/Y') }}
    **Heure :** {{ $appointment->appointment_time->format('H:i') }}
    **Durée :** {{ $appointment->duration_minutes }} minutes
    **Type :**
    {{ $appointment->location_type === 'in_person' ? 'En présentiel' : ($appointment->location_type === 'zoom' ? 'Visioconférence' : 'Hybride') }}

    @if($appointment->zoom_link)
        **Lien de connexion :** [{{ $appointment->zoom_link }}]({{ $appointment->zoom_link }})
    @endif

    ## Rendez-vous précédent

    Ce suivi fait suite à votre rendez-vous du **{{ $parentAppointment->appointment_date->format('d/m/Y') }}** à
    **{{ $parentAppointment->appointment_time->format('H:i') }}**.

    ## Votre pasteur

    **{{ $pastor->first_name }} {{ $pastor->last_name }}**
    Email : [{{ $pastor->email }}](mailto:{{ $pastor->email }})

    ---

    ## Actions requises

    **Veuillez confirmer votre présence** en cliquant sur le bouton ci-dessous :

    <x-mail::button :url="$confirmUrl" color="success">
        Confirmer le rendez-vous
    </x-mail::button>

    Si vous ne pouvez pas être présent(e), vous pouvez annuler votre rendez-vous :

    <x-mail::button :url="$cancelUrl" color="error">
        Annuler le rendez-vous
    </x-mail::button>

    ---

    ## Informations importantes

    - **Confirmation requise :** Ce rendez-vous doit être confirmé avant la date prévue
    - **Annulation :** Les annulations doivent être effectuées au moins 24h à l'avance
    - **Reprogrammation :** Pour reprogrammer, veuillez d'abord annuler ce rendez-vous puis prendre un nouveau créneau

    ---

    ## Nous contacter

    **{{ $churchName }}**
    Email : [{{ $churchEmail }}](mailto:{{ $churchEmail }})
    Site web : [{{ $churchWebsite }}]({{ $churchWebsite }})

    **Note :** Cet email a été envoyé automatiquement. Pour toute question, veuillez répondre directement à
    {{ $pastor->first_name }} {{ $pastor->last_name }} à l'adresse [{{ $pastor->email }}](mailto:{{ $pastor->email }}).

    Que la paix du Seigneur soit avec vous,

    L'équipe pastorale de {{ $churchName }}
</x-mail::message>