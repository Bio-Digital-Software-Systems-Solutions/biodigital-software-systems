import { FormEventHandler, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
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

export default function ContactSection({ isAuthenticated, content, design }: ContactSectionProps) {
    const { t } = useTranslation();
    const resolved = resolveDesign(design);
    const badge = content?.badge ?? t('home.contact.badge');
    const heading = content?.heading ?? t('home.contact.heading');
    const subtitle = content?.subtitle ?? t('home.contact.subtitle');
    const email = content?.email ?? 'contact@icc-munich.de';
    const phone = content?.phone ?? '+49 (0) 17673200275';
    const address = content?.address ?? `Kapellenstraße 22\n82008 Unterhaching\n${t('home.contact.country')}`;
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
            className={`bg-gradient-to-br from-icc-blue/5 via-icc-purple/5 to-icc-red/5 ${resolved.sectionClass} ${resolved.hasPadding ? '' : 'py-20'}`}
            style={resolved.sectionStyle}
        >
            <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                <div className="max-w-6xl mx-auto">
                    <div className={`space-y-4 mb-12 ${resolved.alignmentClass || 'text-center'}`}>
                        <Badge variant="secondary" className="mb-2">
                            {badge}
                        </Badge>
                        <h2 className={`font-bold tracking-tight ${resolved.headingClass || 'text-3xl sm:text-4xl md:text-5xl'}`}>
                            {heading}
                        </h2>
                        <p className={`mx-auto max-w-2xl text-muted-foreground ${resolved.paragraphClass || 'text-lg'}`}>
                            {subtitle}
                        </p>
                    </div>

                    <div className="grid gap-8 lg:grid-cols-2">
                        {/* Left Column - Contact Info & CTA */}
                        <div className="space-y-6">
                            <Card className="border-icc-blue/20">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <EnvelopeIcon className="h-5 w-5 text-icc-blue" />
                                        {t('home.contact.coordinates')}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex items-start gap-3">
                                        <EnvelopeIcon className="h-5 w-5 text-muted-foreground mt-0.5" />
                                        <div>
                                            <p className="font-medium">{t('home.contact.email')}</p>
                                            <a href={`mailto:${email}`} className="text-sm text-icc-blue hover:underline">
                                                {email}
                                            </a>
                                        </div>
                                    </div>
                                    <div className="flex items-start gap-3">
                                        <PhoneIcon className="h-5 w-5 text-muted-foreground mt-0.5" />
                                        <div>
                                            <p className="font-medium">{t('home.contact.phone')}</p>
                                            <a href={`tel:${phone.replace(/[^+\d]/g, '')}`} className="text-sm text-icc-blue hover:underline">
                                                {phone}
                                            </a>
                                        </div>
                                    </div>
                                    <div className="flex items-start gap-3">
                                        <ChatBubbleLeftRightIcon className="h-5 w-5 text-muted-foreground mt-0.5" />
                                        <div>
                                            <p className="font-medium">{t('home.contact.hours')}</p>
                                            <p className="text-sm text-muted-foreground whitespace-pre-line">
                                                {t('home.contact.hoursValue')}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-start gap-3">
                                        <MapPinIcon className="h-5 w-5 text-muted-foreground mt-0.5" />
                                        <div>
                                            <p className="font-medium">{t('home.contact.address')}</p>
                                            <p className="text-sm text-muted-foreground whitespace-pre-line">
                                                {address}
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {!isAuthenticated && (
                                <Card className="bg-gradient-to-br from-icc-blue/10 to-icc-purple/10 border-icc-blue/20">
                                    <CardHeader>
                                        <CardTitle>{t('home.contact.joinTitle')}</CardTitle>
                                        <CardDescription>
                                            {t('home.contact.joinDesc')}
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="flex flex-col gap-3">
                                        <Button size="lg" asChild className="w-full">
                                            <Link href={route('register')}>
                                                {t('home.contact.registerFree')}
                                            </Link>
                                        </Button>
                                        <Button size="lg" variant="outline" asChild className="w-full">
                                            <Link href={route('login')}>
                                                {t('home.contact.signIn')}
                                            </Link>
                                        </Button>
                                    </CardContent>
                                </Card>
                            )}

                            {isAuthenticated && (
                                <Card className="bg-gradient-to-br from-icc-blue/10 to-icc-purple/10 border-icc-blue/20">
                                    <CardHeader>
                                        <CardTitle>{t('home.contact.dashboardTitle')}</CardTitle>
                                        <CardDescription>
                                            {t('home.contact.dashboardDesc')}
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <Button size="lg" asChild className="w-full">
                                            <Link href={route('dashboard')}>
                                                {t('home.contact.dashboardBtn')}
                                            </Link>
                                        </Button>
                                    </CardContent>
                                </Card>
                            )}
                        </div>

                        {/* Right Column - Contact Form */}
                        <Card className="shadow-lg">
                            <CardHeader>
                                <CardTitle>{t('home.contact.formTitle')}</CardTitle>
                                <CardDescription>
                                    {t('home.contact.formDesc')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleContactSubmit} className="space-y-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">
                                            {t('home.contact.fullName')} <span className="text-destructive">*</span>
                                        </Label>
                                        <div className="relative">
                                            <UserIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-muted-foreground" />
                                            <Input
                                                id="name"
                                                type="text"
                                                value={contactData.name}
                                                onChange={(e) => setContactData({ ...contactData, name: e.target.value })}
                                                className="pl-10"
                                                placeholder={t('home.contact.namePlaceholder')}
                                                required
                                            />
                                        </div>
                                        {errors.name && (
                                            <p className="text-sm text-destructive">{errors.name}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="email">
                                            {t('home.contact.email')} <span className="text-destructive">*</span>
                                        </Label>
                                        <div className="relative">
                                            <EnvelopeIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-muted-foreground" />
                                            <Input
                                                id="email"
                                                type="email"
                                                value={contactData.email}
                                                onChange={(e) => setContactData({ ...contactData, email: e.target.value })}
                                                className="pl-10"
                                                placeholder={t('home.contact.emailPlaceholder')}
                                                required
                                            />
                                        </div>
                                        {errors.email && (
                                            <p className="text-sm text-destructive">{errors.email}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="phone">{t('home.contact.phone')}</Label>
                                        <div className="relative">
                                            <PhoneIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-muted-foreground" />
                                            <Input
                                                id="phone"
                                                type="tel"
                                                value={contactData.phone}
                                                onChange={(e) => setContactData({ ...contactData, phone: e.target.value })}
                                                className="pl-10"
                                                placeholder="+33 6 12 34 56 78"
                                            />
                                        </div>
                                        {errors.phone && (
                                            <p className="text-sm text-destructive">{errors.phone}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="subject">
                                            {t('home.contact.subject')} <span className="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id="subject"
                                            type="text"
                                            value={contactData.subject}
                                            onChange={(e) => setContactData({ ...contactData, subject: e.target.value })}
                                            placeholder={t('home.contact.subjectPlaceholder')}
                                            required
                                        />
                                        {errors.subject && (
                                            <p className="text-sm text-destructive">{errors.subject}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="message">
                                            {t('home.contact.message')} <span className="text-destructive">*</span>
                                        </Label>
                                        <Textarea
                                            id="message"
                                            value={contactData.message}
                                            onChange={(e) => setContactData({ ...contactData, message: e.target.value })}
                                            placeholder={t('home.contact.messagePlaceholder')}
                                            className="min-h-[150px]"
                                            required
                                        />
                                        {errors.message && (
                                            <p className="text-sm text-destructive">{errors.message}</p>
                                        )}
                                    </div>

                                    <Button
                                        type="submit"
                                        className="w-full"
                                        disabled={processing}
                                    >
                                        {processing ? (
                                            <>
                                                <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2" />
                                                {t('home.contact.sending')}
                                            </>
                                        ) : (
                                            <>
                                                <EnvelopeIcon className="h-5 w-5 mr-2" />
                                                {t('home.contact.send')}
                                            </>
                                        )}
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </section>
    );
}
