<x-mail::message>
    # Confirmation reçue - En attente de votre confirmation

    @if($recipientType === 'client')
        Bonjour {{ $appointment->client_name }},

        Bonne nouvelle ! {{ $pastor->first_name }} {{ $pastor->last_name }} a confirmé le rendez-vous de soin pastoral.

        **Il ne manque plus que votre confirmation pour que le rendez-vous soit définitivement validé.**
    @else
        Bonjour {{ $pastor->first_name }},

        {{ $appointment->client_name }} a confirmé sa présence au rendez-vous de soin pastoral.

        **Il ne manque plus que votre confirmation pour que le rendez-vous soit définitivement validé.**
    @endif

    ## Détails du rendez-vous

    **Date :** {{ $appointment->appointment_date->format('d/m/Y') }}
    **Heure :** {{ $appointment->appointment_time->format('H:i') }}
    **Durée :** {{ $appointment->duration_minutes }} minutes
    **Type :**
    {{ $appointment->location_type === 'in_person' ? 'En présentiel' : ($appointment->location_type === 'zoom' ? 'Visioconférence' : 'Hybride') }}

    @if($appointment->zoom_link)
        **Lien de connexion :** [{{ $appointment->zoom_link }}]({{ $appointment->zoom_link }})
    @endif

    ---

    ## État des confirmations

    <x-mail::panel>
        @if($confirmedBy === 'client')
            **Client :** ✅ Confirmé le {{ $appointment->client_confirmed_at?->format('d/m/Y à H:i') }}
            **Pasteur :** ⏳ En attente de confirmation
        @else
            **Client :** ⏳ En attente de confirmation
            **Pasteur :** ✅ Confirmé le {{ $appointment->pastor_confirmed_at?->format('d/m/Y à H:i') }}
        @endif
    </x-mail::panel>

    ---

    ## Action requise

    **Veuillez confirmer votre présence** en cliquant sur le bouton ci-dessous :

    <x-mail::button :url="$confirmUrl" color="success">
        Confirmer le rendez-vous
    </x-mail::button>

    ---

    @if($recipientType === 'client')
        ## Votre pasteur

        **{{ $pastor->first_name }} {{ $pastor->last_name }}**
        Email : [{{ $pastor->email }}](mailto:{{ $pastor->email }})
    @else
        ## Informations du client

        **Nom :** {{ $appointment->client_name ?? 'Non renseigné' }}
        **Email :** {{ $appointment->client_email ?? 'Non renseigné' }}
        **Téléphone :** {{ $appointment->client_phone ?? 'Non renseigné' }}
    @endif

    ---

    ## Nous contacter

    **{{ $churchName }}**
    Email : [{{ $churchEmail }}](mailto:{{ $churchEmail }})
    Site web : [{{ $churchWebsite }}]({{ $churchWebsite }})

    Que la paix du Seigneur soit avec vous,

    L'équipe pastorale de {{ $churchName }}
</x-mail::message>
