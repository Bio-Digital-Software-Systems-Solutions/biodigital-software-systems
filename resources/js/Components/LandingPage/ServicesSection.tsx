import { useTranslation } from 'react-i18next';
import { Zap, Code2, LineChart, ShieldCheck, type LucideIcon } from 'lucide-react';

interface ServiceDef {
    icon: LucideIcon;
    titleKey: string;
    bodyKey: string;
}

const SERVICES: ServiceDef[] = [
    { icon: Zap, titleKey: 'home.about.services.mvp.title', bodyKey: 'home.about.services.mvp.body' },
    { icon: Code2, titleKey: 'home.about.services.middleware.title', bodyKey: 'home.about.services.middleware.body' },
    { icon: LineChart, titleKey: 'home.about.services.saas.title', bodyKey: 'home.about.services.saas.body' },
    { icon: ShieldCheck, titleKey: 'home.about.services.custom.title', bodyKey: 'home.about.services.custom.body' },
];

/**
 * "Leistungen / Services" band: four focus areas presented as hover-lift cards,
 * matching the Bio-Digital landing proposal.
 */
export default function ServicesSection() {
    const { t } = useTranslation();

    return (
        <section id="services" className="border-t border-bd-line py-16 sm:py-20 lg:py-24">
            <div className="mx-auto max-w-none px-5 sm:px-8 lg:px-10">
                <div className="max-w-[62ch]">
                    <p className="mb-3.5 text-[12.5px] font-semibold uppercase tracking-[0.14em] text-bd-brand-deep">
                        {t('home.services.kicker')}
                    </p>
                    <h2 className="font-display text-[clamp(1.7rem,3.4vw,2.5rem)] font-semibold tracking-tight text-bd-ink">
                        {t('home.services.title')}
                    </h2>
                    <p className="mt-3.5 text-[1.05rem] text-bd-ink-2">{t('home.services.subtitle')}</p>
                </div>

                <div className="mt-11 grid gap-5 md:grid-cols-2">
                    {SERVICES.map(({ icon: Icon, titleKey, bodyKey }) => (
                        <article
                            key={titleKey}
                            className="group rounded-2xl border border-bd-line bg-bd-surface p-7 transition-all duration-200 hover:-translate-y-0.5 hover:border-bd-brand hover:shadow-[0_18px_40px_-28px_oklch(0.5_0.08_205_/_0.6)]"
                        >
                            <div className="mb-4 grid h-11 w-11 place-items-center rounded-xl bg-bd-brand-soft text-bd-brand-deep">
                                <Icon className="h-5 w-5" aria-hidden="true" />
                            </div>
                            <h3 className="font-display text-[1.25rem] font-semibold text-bd-ink">{t(titleKey)}</h3>
                            <p className="mt-2 text-[0.97rem] text-bd-ink-2">{t(bodyKey)}</p>
                        </article>
                    ))}
                </div>
            </div>
        </section>
    );
}
