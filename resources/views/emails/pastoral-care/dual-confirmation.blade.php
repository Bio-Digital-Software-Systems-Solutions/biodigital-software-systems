<x-mail::message>
    # Rendez-vous confirmé - Les deux parties ont validé

    @if($recipientType === 'client')
        Bonjour {{ $appointment->client_name }},

        Bonne nouvelle ! Votre rendez-vous de soin pastoral a été confirmé par les deux parties.
    @else
        Bonjour {{ $pastor->first_name }},

        Le rendez-vous de soin pastoral avec {{ $appointment->client_name }} a été confirmé par les deux parties.
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

    ## Historique des confirmations

    <x-mail::panel>
        **Client confirmé le :** {{ $appointment->client_confirmed_at?->format('d/m/Y à H:i') ?? 'Non confirmé' }}
        **Pasteur confirmé le :** {{ $appointment->pastor_confirmed_at?->format('d/m/Y à H:i') ?? 'Non confirmé' }}
    </x-mail::panel>

    ---

    ## Rappel

    - Veuillez vous présenter à l'heure convenue
    - En cas d'empêchement, merci de prévenir au moins 24h à l'avance

    ---

    ## Nous contacter

    **{{ $churchName }}**
    Email : [{{ $churchEmail }}](mailto:{{ $churchEmail }})
    Site web : [{{ $churchWebsite }}]({{ $churchWebsite }})

    Que la paix du Seigneur soit avec vous,

    L'équipe pastorale de {{ $churchName }}
</x-mail::message>
