<x-mail::message>
    # Nouveau rendez-vous de suivi créé

    Bonjour {{ $pastor->first_name }},

    Vous avez créé un rendez-vous de suivi pour {{ $appointment->client_name }}. Ce rendez-vous fait suite à celui du **{{ $parentAppointment->appointment_date->format('d/m/Y') }}**.

    ## Détails du rendez-vous de suivi

    **Date :** {{ $appointment->appointment_date->format('d/m/Y') }}
    **Heure :** {{ $appointment->appointment_time->format('H:i') }}
    **Durée :** {{ $appointment->duration_minutes }} minutes
    **Type :**
    {{ $appointment->location_type === 'in_person' ? 'En présentiel' : ($appointment->location_type === 'zoom' ? 'Visioconférence' : 'Hybride') }}

    @if($appointment->zoom_link)
        **Lien de connexion :** [{{ $appointment->zoom_link }}]({{ $appointment->zoom_link }})
    @endif

    ## Informations du client

    **Nom :** {{ $appointment->client_name ?? 'Non renseigné' }}
    **Email :** {{ $appointment->client_email ?? 'Non renseigné' }}
    **Téléphone :** {{ $appointment->client_phone ?? 'Non renseigné' }}

    ## Rendez-vous précédent

    Ce suivi fait suite à votre rendez-vous du **{{ $parentAppointment->appointment_date->format('d/m/Y') }}** à
    **{{ $parentAppointment->appointment_time->format('H:i') }}**.

    ---

    ## Double confirmation requise

    <x-mail::panel>
        ⚠️ **Important :** Ce rendez-vous nécessite une confirmation de votre part ET du client.

        Le client recevra également un email avec un lien de confirmation.

        Le rendez-vous ne sera définitivement validé que lorsque les deux parties auront confirmé.
    </x-mail::panel>

    **Veuillez confirmer votre disponibilité** en cliquant sur le bouton ci-dessous :

    <x-mail::button :url="$confirmUrl" color="success">
        Confirmer le rendez-vous
    </x-mail::button>

    Si vous devez annuler ce rendez-vous :

    <x-mail::button :url="$cancelUrl" color="error">
        Annuler le rendez-vous
    </x-mail::button>

    ---

    ## Informations importantes

    - **Confirmation requise :** Ce rendez-vous doit être confirmé par les deux parties avant la date prévue
    - **Annulation :** Les annulations doivent être effectuées au moins 24h à l'avance
    - **Historique :** L'historique des confirmations sera visible dans le système

    ---

    ## Nous contacter

    **{{ $churchName }}**
    Email : [{{ $churchEmail }}](mailto:{{ $churchEmail }})
    Site web : [{{ $churchWebsite }}]({{ $churchWebsite }})

    Que la paix du Seigneur soit avec vous,

    L'équipe pastorale de {{ $churchName }}
</x-mail::message>
