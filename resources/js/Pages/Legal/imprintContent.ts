import { SITE_CONTACT } from '@/Components/LandingPage/siteContact';
import type { LegalLanguage, LegalPageContent } from '@/Pages/Legal/legalShared';

const address = SITE_CONTACT.addressLines.join(', ');
const hasRegister = Boolean(SITE_CONTACT.registerCourt && SITE_CONTACT.registerNumber);

const ODR_URL = 'https://ec.europa.eu/consumers/odr/';

export const IMPRINT_CONTENT: Record<LegalLanguage, LegalPageContent> = {
    fr: {
        metaTitle: 'Mentions légales',
        title: 'Mentions légales',
        backToHome: "Retour à l'accueil",
        lastUpdatedLabel: 'Dernière mise à jour',
        sections: [
            {
                id: 'provider',
                title: 'Éditeur du site (§ 5 DDG)',
                blocks: [
                    {
                        bullets: [
                            SITE_CONTACT.company,
                            `Représentée par : ${SITE_CONTACT.owner} (gérant)`,
                            `Adresse : ${address}, Allemagne`,
                            ...(hasRegister
                                ? [`Registre du commerce : ${SITE_CONTACT.registerCourt}, ${SITE_CONTACT.registerNumber}`]
                                : []),
                        ],
                    },
                ],
            },
            {
                id: 'contact',
                title: 'Contact',
                blocks: [
                    {
                        paragraphs: ['Vous pouvez nous joindre par e-mail :'],
                        links: [{ label: SITE_CONTACT.email, href: `mailto:${SITE_CONTACT.email}` }],
                    },
                ],
            },
            {
                id: 'tax',
                title: 'Identifiants fiscaux',
                blocks: [
                    {
                        bullets: [
                            `Numéro fiscal (Steuernummer) : ${SITE_CONTACT.taxNumber}`,
                            `Numéro de TVA intracommunautaire (§ 27a UStG) : ${SITE_CONTACT.vatId}`,
                        ],
                    },
                ],
            },
            {
                id: 'editorial',
                title: 'Responsable éditorial (§ 18 al. 2 MStV)',
                blocks: [
                    {
                        paragraphs: [`${SITE_CONTACT.owner}, ${address}, Allemagne.`],
                    },
                ],
            },
            {
                id: 'dispute',
                title: 'Règlement des litiges',
                blocks: [
                    {
                        paragraphs: [
                            'La Commission européenne met à disposition une plateforme de règlement en ligne des litiges (RLL) :',
                        ],
                        links: [{ label: ODR_URL, href: ODR_URL }],
                    },
                    {
                        paragraphs: [
                            "Nous ne sommes ni disposés ni tenus de participer à une procédure de règlement des litiges devant un organisme de conciliation des consommateurs (§ 36 VSBG).",
                        ],
                    },
                ],
            },
            {
                id: 'liability',
                title: 'Responsabilité relative aux contenus et aux liens',
                blocks: [
                    {
                        paragraphs: [
                            "En tant qu'éditeur, nous sommes responsables de nos propres contenus sur ces pages conformément au droit commun. Nous ne sommes toutefois pas tenus de surveiller les informations transmises ou stockées par des tiers, ni de rechercher des circonstances révélant une activité illicite. Les obligations de retrait ou de blocage prévues par la loi demeurent inchangées ; une responsabilité à ce titre n'est engagée qu'à compter de la connaissance d'une violation concrète.",
                            "Notre site contient des liens vers des sites externes de tiers sur le contenu desquels nous n'avons aucune influence. Le fournisseur ou l'exploitant des pages liées est toujours responsable de leur contenu. Les pages liées ont été vérifiées au moment de la création du lien ; en cas de violation constatée, nous retirerons immédiatement le lien concerné.",
                        ],
                    },
                ],
            },
            {
                id: 'copyright',
                title: "Droits d'auteur",
                blocks: [
                    {
                        paragraphs: [
                            "Les contenus et œuvres créés par l'éditeur sur ces pages sont soumis au droit d'auteur allemand. Toute reproduction, modification, diffusion ou exploitation en dehors des limites du droit d'auteur nécessite l'accord écrit préalable de l'auteur. Les contenus de tiers sont identifiés comme tels ; si vous constatez néanmoins une violation du droit d'auteur, merci de nous en informer.",
                        ],
                    },
                ],
            },
        ],
    },
    en: {
        metaTitle: 'Imprint',
        title: 'Imprint',
        backToHome: 'Back to home',
        lastUpdatedLabel: 'Last updated',
        sections: [
            {
                id: 'provider',
                title: 'Site provider (Section 5 DDG)',
                blocks: [
                    {
                        bullets: [
                            SITE_CONTACT.company,
                            `Represented by: ${SITE_CONTACT.owner} (managing director)`,
                            `Address: ${address}, Germany`,
                            ...(hasRegister
                                ? [`Commercial register: ${SITE_CONTACT.registerCourt}, ${SITE_CONTACT.registerNumber}`]
                                : []),
                        ],
                    },
                ],
            },
            {
                id: 'contact',
                title: 'Contact',
                blocks: [
                    {
                        paragraphs: ['You can reach us by e-mail:'],
                        links: [{ label: SITE_CONTACT.email, href: `mailto:${SITE_CONTACT.email}` }],
                    },
                ],
            },
            {
                id: 'tax',
                title: 'Tax identifiers',
                blocks: [
                    {
                        bullets: [
                            `Tax number (Steuernummer): ${SITE_CONTACT.taxNumber}`,
                            `VAT identification number (Section 27a UStG): ${SITE_CONTACT.vatId}`,
                        ],
                    },
                ],
            },
            {
                id: 'editorial',
                title: 'Responsible for editorial content (Section 18 (2) MStV)',
                blocks: [
                    {
                        paragraphs: [`${SITE_CONTACT.owner}, ${address}, Germany.`],
                    },
                ],
            },
            {
                id: 'dispute',
                title: 'Dispute resolution',
                blocks: [
                    {
                        paragraphs: [
                            'The European Commission provides a platform for online dispute resolution (ODR):',
                        ],
                        links: [{ label: ODR_URL, href: ODR_URL }],
                    },
                    {
                        paragraphs: [
                            'We are neither willing nor obliged to participate in dispute resolution proceedings before a consumer arbitration board (Section 36 VSBG).',
                        ],
                    },
                ],
            },
            {
                id: 'liability',
                title: 'Liability for content and links',
                blocks: [
                    {
                        paragraphs: [
                            'As a service provider we are responsible for our own content on these pages in accordance with general law. However, we are not obliged to monitor transmitted or stored third-party information, or to investigate circumstances indicating illegal activity. Obligations to remove or block the use of information under general law remain unaffected; liability in this respect only arises from the moment we become aware of a specific infringement.',
                            'Our website contains links to external third-party websites over whose content we have no influence. The respective provider or operator of the linked pages is always responsible for their content. The linked pages were checked for possible legal violations at the time the link was created; should we become aware of any infringement, we will remove the link concerned immediately.',
                        ],
                    },
                ],
            },
            {
                id: 'copyright',
                title: 'Copyright',
                blocks: [
                    {
                        paragraphs: [
                            'The content and works created by the site provider on these pages are subject to German copyright law. Reproduction, editing, distribution or any kind of exploitation outside the limits of copyright law require the prior written consent of the author. Third-party content is marked as such; should you nevertheless become aware of a copyright infringement, please let us know.',
                        ],
                    },
                ],
            },
        ],
    },
    de: {
        metaTitle: 'Impressum',
        title: 'Impressum',
        backToHome: 'Zurück zur Startseite',
        lastUpdatedLabel: 'Zuletzt aktualisiert',
        sections: [
            {
                id: 'provider',
                title: 'Angaben gemäß § 5 DDG',
                blocks: [
                    {
                        bullets: [
                            SITE_CONTACT.company,
                            `Vertreten durch: ${SITE_CONTACT.owner} (Geschäftsführer)`,
                            `Anschrift: ${address}, Deutschland`,
                            ...(hasRegister
                                ? [`Handelsregister: ${SITE_CONTACT.registerCourt}, ${SITE_CONTACT.registerNumber}`]
                                : []),
                        ],
                    },
                ],
            },
            {
                id: 'contact',
                title: 'Kontakt',
                blocks: [
                    {
                        paragraphs: ['Sie erreichen uns per E-Mail:'],
                        links: [{ label: SITE_CONTACT.email, href: `mailto:${SITE_CONTACT.email}` }],
                    },
                ],
            },
            {
                id: 'tax',
                title: 'Steuerliche Angaben',
                blocks: [
                    {
                        bullets: [
                            `Steuernummer: ${SITE_CONTACT.taxNumber}`,
                            `Umsatzsteuer-Identifikationsnummer gemäß § 27a UStG: ${SITE_CONTACT.vatId}`,
                        ],
                    },
                ],
            },
            {
                id: 'editorial',
                title: 'Verantwortlich für den Inhalt (§ 18 Abs. 2 MStV)',
                blocks: [
                    {
                        paragraphs: [`${SITE_CONTACT.owner}, ${address}, Deutschland.`],
                    },
                ],
            },
            {
                id: 'dispute',
                title: 'Streitbeilegung',
                blocks: [
                    {
                        paragraphs: [
                            'Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung (OS) bereit:',
                        ],
                        links: [{ label: ODR_URL, href: ODR_URL }],
                    },
                    {
                        paragraphs: [
                            'Wir sind nicht bereit und nicht verpflichtet, an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen (§ 36 VSBG).',
                        ],
                    },
                ],
            },
            {
                id: 'liability',
                title: 'Haftung für Inhalte und Links',
                blocks: [
                    {
                        paragraphs: [
                            'Als Diensteanbieter sind wir für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich. Wir sind jedoch nicht verpflichtet, übermittelte oder gespeicherte fremde Informationen zu überwachen oder nach Umständen zu forschen, die auf eine rechtswidrige Tätigkeit hinweisen. Verpflichtungen zur Entfernung oder Sperrung der Nutzung von Informationen nach den allgemeinen Gesetzen bleiben hiervon unberührt; eine diesbezügliche Haftung ist erst ab dem Zeitpunkt der Kenntnis einer konkreten Rechtsverletzung möglich.',
                            'Unser Angebot enthält Links zu externen Websites Dritter, auf deren Inhalte wir keinen Einfluss haben. Für die Inhalte der verlinkten Seiten ist stets der jeweilige Anbieter oder Betreiber verantwortlich. Die verlinkten Seiten wurden zum Zeitpunkt der Verlinkung auf mögliche Rechtsverstöße überprüft; bei Bekanntwerden von Rechtsverletzungen werden wir derartige Links umgehend entfernen.',
                        ],
                    },
                ],
            },
            {
                id: 'copyright',
                title: 'Urheberrecht',
                blocks: [
                    {
                        paragraphs: [
                            'Die durch den Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen dem deutschen Urheberrecht. Die Vervielfältigung, Bearbeitung, Verbreitung und jede Art der Verwertung außerhalb der Grenzen des Urheberrechtes bedürfen der vorherigen schriftlichen Zustimmung des Autors. Inhalte Dritter sind als solche gekennzeichnet; sollten Sie trotzdem auf eine Urheberrechtsverletzung aufmerksam werden, bitten wir um einen entsprechenden Hinweis.',
                        ],
                    },
                ],
            },
        ],
    },
};
