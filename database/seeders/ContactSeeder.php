<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ContactSeeder extends Seeder
{
    /**
     * Seed contact form messages with realistic data spread over
     * the last 6 months, covering every status of the workflow.
     */
    public function run(): void
    {
        $assignees = $this->assignableUsers();

        if ($assignees->isEmpty()) {
            $this->command->warn('No users with the "manage contacts" permission found. Contacts will be seeded unassigned.');
        }

        foreach ($this->messageTemplates() as $template) {
            $status = $this->weightedStatus();
            $createdAt = fake()->dateTimeBetween('-6 months', 'now');

            $factory = Contact::factory()->state([
                'subject' => $template['subject'],
                'message' => $template['message'],
                'status' => $status,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            if ($status === 'new') {
                $factory = $factory->state([
                    'read_at' => fake()->boolean(40) ? fake()->dateTimeBetween($createdAt, 'now') : null,
                ]);
            } else {
                $factory = $factory->state([
                    'read_at' => fake()->dateTimeBetween($createdAt, 'now'),
                ]);

                if ($assignees->isNotEmpty() && fake()->boolean(70)) {
                    $factory = $factory->assigned($assignees->random());
                }
            }

            $factory->create();
        }

        $this->command->info('Contact form messages seeded: '.Contact::count().' total.');
    }

    /**
     * Users allowed to handle contact messages.
     *
     * @return Collection<int, User>
     */
    private function assignableUsers(): Collection
    {
        if (! Permission::where('name', 'manage contacts')->exists()) {
            return new Collection;
        }

        return User::permission('manage contacts')->get();
    }

    /**
     * Pick a status with a realistic distribution.
     */
    private function weightedStatus(): string
    {
        $roll = fake()->numberBetween(1, 100);

        return match (true) {
            $roll <= 25 => 'new',
            $roll <= 40 => 'in_progress',
            $roll <= 80 => 'resolved',
            default => 'closed',
        };
    }

    /**
     * Realistic contact form subjects and messages.
     *
     * @return array<int, array{subject: string, message: string}>
     */
    private function messageTemplates(): array
    {
        return [
            ['subject' => 'Demande d\'informations sur les événements', 'message' => "Bonjour,\n\nJe souhaiterais recevoir plus d'informations sur les prochains événements organisés. Est-il possible de recevoir le programme du mois ?\n\nMerci d'avance."],
            ['subject' => 'Devenir bénévole', 'message' => "Bonjour,\n\nJe suis intéressé(e) par le bénévolat au sein de votre organisation. Quelles sont les démarches à suivre pour rejoindre l'équipe de volontaires ?\n\nCordialement."],
            ['subject' => 'Horaires de la bibliothèque', 'message' => "Bonjour,\n\nPourriez-vous m'indiquer les horaires d'ouverture de la bibliothèque ainsi que les conditions d'emprunt des livres ?\n\nMerci."],
            ['subject' => 'Inscription aux formations', 'message' => "Bonjour,\n\nJ'aimerais m'inscrire à l'une de vos formations. Comment puis-je consulter le catalogue et les dates disponibles ?\n\nBien cordialement."],
            ['subject' => 'Demande de rendez-vous pastoral', 'message' => "Bonjour,\n\nJe traverse une période difficile et j'aimerais prendre rendez-vous avec un membre de l'équipe pastorale. Quelles sont les disponibilités ?\n\nMerci pour votre écoute."],
            ['subject' => 'Proposition de partenariat', 'message' => "Bonjour,\n\nNotre association souhaiterait établir un partenariat avec votre organisation pour un projet communautaire. Serait-il possible d'organiser une rencontre ?\n\nCordialement."],
            ['subject' => 'Problème de connexion au site', 'message' => "Bonjour,\n\nJe n'arrive pas à me connecter à mon compte depuis quelques jours. Le message « identifiants invalides » s'affiche alors que mon mot de passe est correct.\n\nPouvez-vous m'aider ?"],
            ['subject' => 'Faire un don', 'message' => "Bonjour,\n\nJe souhaiterais soutenir vos actions par un don. Quelles sont les modalités (virement, chèque, en ligne) ?\n\nMerci beaucoup."],
            ['subject' => 'Location de salle', 'message' => "Bonjour,\n\nDisposez-vous de salles à louer pour un événement familial d'une cinquantaine de personnes ? Si oui, quels sont les tarifs ?\n\nCordialement."],
            ['subject' => 'Question sur les groupes', 'message' => "Bonjour,\n\nJe viens d'arriver dans la région et j'aimerais rejoindre un groupe près de chez moi. Comment connaître les groupes existants et leurs lieux de rencontre ?\n\nMerci."],
            ['subject' => 'Demande de certificat', 'message' => "Bonjour,\n\nJ'ai suivi une formation chez vous l'année dernière et j'aurais besoin d'une attestation de participation pour mon employeur.\n\nMerci d'avance."],
            ['subject' => 'Suggestion d\'amélioration', 'message' => "Bonjour,\n\nSerait-il possible d'ajouter un calendrier téléchargeable des événements sur le site ? Cela faciliterait l'organisation pour les familles.\n\nBonne journée."],
            ['subject' => 'Perte d\'un objet lors d\'un événement', 'message' => "Bonjour,\n\nJ'ai perdu une veste noire lors de l'événement de samedi dernier. Avez-vous un service d'objets trouvés ?\n\nMerci."],
            ['subject' => 'Demande de stage', 'message' => "Bonjour,\n\nÉtudiant(e) en communication, je recherche un stage de 3 mois. Accueillez-vous des stagiaires au sein de votre organisation ?\n\nCordialement."],
            ['subject' => 'Accessibilité PMR', 'message' => "Bonjour,\n\nVos locaux sont-ils accessibles aux personnes à mobilité réduite ? Je souhaiterais participer aux événements avec un proche en fauteuil roulant.\n\nMerci."],
            ['subject' => 'Newsletter', 'message' => "Bonjour,\n\nJe ne reçois plus votre newsletter depuis deux mois alors que je suis bien inscrit(e). Pouvez-vous vérifier mon abonnement ?\n\nMerci."],
            ['subject' => 'Réservation pour un groupe scolaire', 'message' => "Bonjour,\n\nEnseignant(e) dans une école voisine, j'aimerais organiser une visite avec ma classe. Proposez-vous des accueils de groupes scolaires ?\n\nCordialement."],
            ['subject' => 'Remerciements', 'message' => "Bonjour,\n\nJe tenais simplement à remercier toute l'équipe pour l'accueil chaleureux lors de l'événement de dimanche. Continuez ainsi !\n\nBien à vous."],
            ['subject' => 'Problème de réservation de livre', 'message' => "Bonjour,\n\nJ'ai réservé un livre il y a deux semaines mais je n'ai reçu aucune confirmation de disponibilité. Pouvez-vous vérifier l'état de ma réservation ?\n\nMerci."],
            ['subject' => 'Demande d\'intervention extérieure', 'message' => "Bonjour,\n\nNous organisons une conférence et souhaiterions inviter l'un de vos intervenants. À qui devons-nous adresser notre demande officielle ?\n\nCordialement."],
            ['subject' => 'Covoiturage pour les événements', 'message' => "Bonjour,\n\nExiste-t-il un système de covoiturage pour se rendre aux événements ? Je n'ai pas de véhicule et j'habite en périphérie.\n\nMerci d'avance."],
            ['subject' => 'Mise à jour de mes coordonnées', 'message' => "Bonjour,\n\nJ'ai récemment déménagé et changé de numéro de téléphone. Comment mettre à jour mes coordonnées dans votre base de données ?\n\nCordialement."],
            ['subject' => 'Demande de partenariat média', 'message' => "Bonjour,\n\nJournaliste pour un média local, je souhaiterais couvrir votre prochain événement public. Puis-je obtenir une accréditation presse ?\n\nCordialement."],
            ['subject' => 'Question sur les horaires d\'ouverture', 'message' => "Bonjour,\n\nVos bureaux sont-ils ouverts pendant les vacances scolaires ? Je souhaiterais passer déposer un dossier.\n\nMerci."],
            ['subject' => 'Problème d\'affichage sur mobile', 'message' => "Bonjour,\n\nLe site ne s'affiche pas correctement sur mon téléphone : le menu ne s'ouvre pas et certaines images sont coupées.\n\nJe voulais vous le signaler. Bonne journée."],
            ['subject' => 'Demande d\'aide alimentaire', 'message' => "Bonjour,\n\nMa famille traverse une période difficile. Proposez-vous une aide alimentaire ou pouvez-vous nous orienter vers un organisme partenaire ?\n\nMerci de votre discrétion."],
            ['subject' => 'Inscription de mon enfant aux activités', 'message' => "Bonjour,\n\nJ'aimerais inscrire mon fils de 10 ans aux activités jeunesse. Quels sont les créneaux et les documents nécessaires ?\n\nCordialement."],
            ['subject' => 'Facture manquante', 'message' => "Bonjour,\n\nJ'ai réglé ma participation à la formation du mois dernier mais je n'ai jamais reçu de facture. Pouvez-vous me la faire parvenir ?\n\nMerci d'avance."],
            ['subject' => 'Proposition d\'animation musicale', 'message' => "Bonjour,\n\nMusicien professionnel, je propose mes services pour animer vos événements. Vous trouverez mon portfolio sur demande.\n\nCordialement."],
            ['subject' => 'Signalement d\'un contenu obsolète', 'message' => "Bonjour,\n\nLa page des horaires sur votre site mentionne encore les horaires d'hiver alors que nous sommes en été. Pensez à la mettre à jour.\n\nBonne journée."],
        ];
    }
}
