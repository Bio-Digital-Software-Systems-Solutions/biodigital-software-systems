import { useTranslation } from 'react-i18next';
import { SITE_CONTACT, mailtoHref } from '@/Components/LandingPage/siteContact';

/**
 * Closing call-to-action band inviting visitors to book a free intro call,
 * matching the Bio-Digital landing proposal.
 */
export default function CtaSection() {
    const { t } = useTranslation();

    return (
        <div className="mx-auto max-w-[1140px] px-5 sm:px-8 lg:px-10">
            <section
                id="contact"
                className="my-16 rounded-2xl bg-bd-brand px-8 py-12 text-center text-white sm:my-20 sm:px-12 sm:py-16 lg:my-24"
            >
                <h2 className="font-display text-[clamp(1.7rem,3.4vw,2.4rem)] font-semibold tracking-tight">
                    {t('home.cta.title')}
                </h2>
                <p className="mx-auto mt-3.5 max-w-[50ch] text-[1.05rem] text-white/90">{t('home.cta.subtitle')}</p>
                <div className="mt-7 flex flex-wrap justify-center gap-3">
                    <a
                        href={mailtoHref('Anfrage Bio-Digital')}
                        className="inline-flex items-center rounded-[10px] bg-white px-5 py-2.5 text-[14.5px] font-semibold text-bd-brand-deep transition-all hover:-translate-y-px"
                    >
                        {t('home.cta.button')}
                    </a>
                    <a
                        href={mailtoHref()}
                        className="inline-flex items-center rounded-[10px] border border-white/40 px-5 py-2.5 text-[14.5px] font-semibold text-white transition-colors hover:border-white"
                    >
                        {SITE_CONTACT.email}
                    </a>
                </div>
            </section>
        </div>
    );
}
