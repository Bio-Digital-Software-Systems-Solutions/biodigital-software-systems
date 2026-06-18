import { FormEventHandler, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import {
    EnvelopeIcon,
    PhoneIcon,
    UserIcon,
    ChatBubbleLeftRightIcon,
    MapPinIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import { useTranslation } from 'react-i18next';
import { resolveDesign, type DesignSettings } from '@/lib/sectionDesign';

export interface ContactContent {
    badge?: string;
    heading?: string;
    subtitle?: string;
    email?: string;
    phone?: string;
    address?: string;
}

interface ContactSectionProps {
    isAuthenticated: boolean;
    content?: ContactContent;
    design?: DesignSettings | null;
}

const fieldClass =
    'w-full rounded-[10px] border border-bd-line bg-bd-surface px-3.5 py-2.5 text-[15px] text-bd-ink placeholder:text-bd-ink-3 transition-colors focus:border-bd-brand focus:outline-none focus:ring-2 focus:ring-bd-brand/20';
const labelClass = 'block text-[13px] font-semibold text-bd-ink';
const primaryButtonClass =
    'inline-flex items-center justify-center rounded-[10px] bg-bd-brand px-5 py-2.5 text-[14.5px] font-semibold text-white transition-all hover:-translate-y-px hover:bg-bd-brand-deep disabled:translate-y-0 disabled:opacity-60';
const outlineButtonClass =
    'inline-flex items-center justify-center rounded-[10px] border border-bd-line bg-bd-surface px-5 py-2.5 text-[14.5px] font-semibold text-bd-ink transition-colors hover:border-bd-brand hover:text-bd-brand-deep';

export default function ContactSection({ isAuthenticated, content, design }: ContactSectionProps) {
    const { t } = useTranslation();
    const resolved = resolveDesign(design);
    const badge = content?.badge ?? t('home.contact.badge');
    const heading = content?.heading ?? t('home.contact.heading');
    const subtitle = content?.subtitle ?? t('home.contact.subtitle');
    const email = content?.email ?? 'elmarce.bounda.ndinga@gmail.com';
    const phone = content?.phone ?? '';
    const address = content?.address ?? `Van-Gogh-Straße 2\n85521 Ottobrunn\n${t('home.contact.country')}`;
    const [contactData, setContactData] = useState({
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: '',
    });
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const handleContactSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        router.post(route('contacts.store'), contactData, {
            onSuccess: () => {
                toast.success(t('home.contact.toast.success'), {
                    description: t('home.contact.toast.successDesc'),
                    duration: 5000,
                });
                setContactData({ name: '', email: '', phone: '', subject: '', message: '' });
            },
            onError: (serverErrors) => {
                setErrors(serverErrors as Record<string, string>);
                toast.error(t('home.contact.toast.error'), {
                    description: t('home.contact.toast.errorDesc'),
                    duration: 5000,
                });
            },
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <section
            id="contact"
            className={`border-t border-bd-line ${resolved.sectionClass} ${resolved.hasPadding ? '' : 'py-16 sm:py-20 lg:py-24'}`}
            style={resolved.sectionStyle}
        >
            <div className="mx-auto max-w-none px-5 sm:px-8 lg:px-10">
                <div className={`max-w-[62ch] ${resolved.alignmentClass}`}>
                    <p className="mb-3.5 text-[12.5px] font-semibold uppercase tracking-[0.14em] text-bd-brand-deep">
                        {badge}
                    </p>
                    <h2
                        className={`font-display font-semibold tracking-tight text-bd-ink ${resolved.headingClass || 'text-[clamp(1.7rem,3.4vw,2.5rem)]'}`}
                    >
                        {heading}
                    </h2>
                    <p className={`mt-3.5 text-bd-ink-2 ${resolved.paragraphClass || 'text-[1.05rem]'}`}>{subtitle}</p>
                </div>

                <div className="mt-11 grid gap-5 lg:grid-cols-2">
                    {/* Left Column - Contact Info & CTA */}
                    <div className="flex flex-col gap-5">
                        <div className="rounded-2xl border border-bd-line bg-bd-surface p-7">
                            <h3 className="flex items-center gap-2 font-display text-[1.25rem] font-semibold text-bd-ink">
                                <EnvelopeIcon className="h-5 w-5 text-bd-brand" />
                                {t('home.contact.coordinates')}
                            </h3>
                            <div className="mt-5 flex flex-col gap-4">
                                <div className="flex items-start gap-3">
                                    <EnvelopeIcon className="mt-0.5 h-5 w-5 text-bd-ink-3" />
                                    <div>
                                        <p className="text-[0.95rem] font-medium text-bd-ink">{t('home.contact.email')}</p>
                                        <a
                                            href={`mailto:${email}`}
                                            className="text-sm text-bd-brand-deep transition-colors hover:text-bd-brand hover:underline"
                                        >
                                            {email}
                                        </a>
                                    </div>
                                </div>
                                {phone && (
                                    <div className="flex items-start gap-3">
                                        <PhoneIcon className="mt-0.5 h-5 w-5 text-bd-ink-3" />
                                        <div>
                                            <p className="text-[0.95rem] font-medium text-bd-ink">{t('home.contact.phone')}</p>
                                            <a
                                                href={`tel:${phone.replace(/[^+\d]/g, '')}`}
                                                className="text-sm text-bd-brand-deep transition-colors hover:text-bd-brand hover:underline"
                                            >
                                                {phone}
                                            </a>
                                        </div>
                                    </div>
                                )}
                                <div className="flex items-start gap-3">
                                    <ChatBubbleLeftRightIcon className="mt-0.5 h-5 w-5 text-bd-ink-3" />
                                    <div>
                                        <p className="text-[0.95rem] font-medium text-bd-ink">{t('home.contact.hours')}</p>
                                        <p className="whitespace-pre-line text-sm text-bd-ink-2">
                                            {t('home.contact.hoursValue')}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-start gap-3">
                                    <MapPinIcon className="mt-0.5 h-5 w-5 text-bd-ink-3" />
                                    <div>
                                        <p className="text-[0.95rem] font-medium text-bd-ink">{t('home.contact.address')}</p>
                                        <p className="whitespace-pre-line text-sm text-bd-ink-2">{address}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {!isAuthenticated && (
                            <div className="rounded-2xl border border-bd-line bg-bd-brand-soft p-7">
                                <h3 className="font-display text-[1.25rem] font-semibold text-bd-ink">
                                    {t('home.contact.joinTitle')}
                                </h3>
                                <p className="mt-2 text-[0.97rem] text-bd-ink-2">{t('home.contact.joinDesc')}</p>
                                <div className="mt-5 flex flex-col gap-3">
                                    <Link href={route('register')} className={`${primaryButtonClass} w-full`}>
                                        {t('home.contact.registerFree')}
                                    </Link>
                                    <Link href={route('login')} className={`${outlineButtonClass} w-full`}>
                                        {t('home.contact.signIn')}
                                    </Link>
                                </div>
                            </div>
                        )}

                        {isAuthenticated && (
                            <div className="rounded-2xl border border-bd-line bg-bd-brand-soft p-7">
                                <h3 className="font-display text-[1.25rem] font-semibold text-bd-ink">
                                    {t('home.contact.dashboardTitle')}
                                </h3>
                                <p className="mt-2 text-[0.97rem] text-bd-ink-2">{t('home.contact.dashboardDesc')}</p>
                                <div className="mt-5">
                                    <Link href={route('dashboard')} className={`${primaryButtonClass} w-full`}>
                                        {t('home.contact.dashboardBtn')}
                                    </Link>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Right Column - Contact Form */}
                    <div className="rounded-2xl border border-bd-line bg-bd-surface p-7 shadow-[0_18px_40px_-28px_oklch(0.5_0.08_205_/_0.6)]">
                        <h3 className="font-display text-[1.25rem] font-semibold text-bd-ink">
                            {t('home.contact.formTitle')}
                        </h3>
                        <p className="mt-2 text-[0.97rem] text-bd-ink-2">{t('home.contact.formDesc')}</p>

                        <form onSubmit={handleContactSubmit} className="mt-6 flex flex-col gap-5">
                            <div className="flex flex-col gap-2">
                                <label htmlFor="name" className={labelClass}>
                                    {t('home.contact.fullName')} <span className="text-vermillion-600">*</span>
                                </label>
                                <div className="relative">
                                    <UserIcon className="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-bd-ink-3" />
                                    <input
                                        id="name"
                                        type="text"
                                        value={contactData.name}
                                        onChange={(e) => setContactData({ ...contactData, name: e.target.value })}
                                        className={`${fieldClass} pl-10`}
                                        placeholder={t('home.contact.namePlaceholder')}
                                        required
                                    />
                                </div>
                                {errors.name && <p className="text-sm text-vermillion-600">{errors.name}</p>}
                            </div>

                            <div className="flex flex-col gap-2">
                                <label htmlFor="email" className={labelClass}>
                                    {t('home.contact.email')} <span className="text-vermillion-600">*</span>
                                </label>
                                <div className="relative">
                                    <EnvelopeIcon className="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-bd-ink-3" />
                                    <input
                                        id="email"
                                        type="email"
                                        value={contactData.email}
                                        onChange={(e) => setContactData({ ...contactData, email: e.target.value })}
                                        className={`${fieldClass} pl-10`}
                                        placeholder={t('home.contact.emailPlaceholder')}
                                        required
                                    />
                                </div>
                                {errors.email && <p className="text-sm text-vermillion-600">{errors.email}</p>}
                            </div>

                            <div className="flex flex-col gap-2">
                                <label htmlFor="phone" className={labelClass}>
                                    {t('home.contact.phone')}
                                </label>
                                <div className="relative">
                                    <PhoneIcon className="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-bd-ink-3" />
                                    <input
                                        id="phone"
                                        type="tel"
                                        value={contactData.phone}
                                        onChange={(e) => setContactData({ ...contactData, phone: e.target.value })}
                                        className={`${fieldClass} pl-10`}
                                        placeholder="+49 151 23456789"
                                    />
                                </div>
                                {errors.phone && <p className="text-sm text-vermillion-600">{errors.phone}</p>}
                            </div>

                            <div className="flex flex-col gap-2">
                                <label htmlFor="subject" className={labelClass}>
                                    {t('home.contact.subject')} <span className="text-vermillion-600">*</span>
                                </label>
                                <input
                                    id="subject"
                                    type="text"
                                    value={contactData.subject}
                                    onChange={(e) => setContactData({ ...contactData, subject: e.target.value })}
                                    className={fieldClass}
                                    placeholder={t('home.contact.subjectPlaceholder')}
                                    required
                                />
                                {errors.subject && <p className="text-sm text-vermillion-600">{errors.subject}</p>}
                            </div>

                            <div className="flex flex-col gap-2">
                                <label htmlFor="message" className={labelClass}>
                                    {t('home.contact.message')} <span className="text-vermillion-600">*</span>
                                </label>
                                <textarea
                                    id="message"
                                    value={contactData.message}
                                    onChange={(e) => setContactData({ ...contactData, message: e.target.value })}
                                    placeholder={t('home.contact.messagePlaceholder')}
                                    className={`${fieldClass} min-h-[150px] resize-y`}
                                    required
                                />
                                {errors.message && <p className="text-sm text-vermillion-600">{errors.message}</p>}
                            </div>

                            <button type="submit" className={`${primaryButtonClass} w-full`} disabled={processing}>
                                {processing ? (
                                    <>
                                        <span className="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                                        {t('home.contact.sending')}
                                    </>
                                ) : (
                                    <>
                                        <EnvelopeIcon className="mr-2 h-5 w-5" />
                                        {t('home.contact.send')}
                                    </>
                                )}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    );
}
