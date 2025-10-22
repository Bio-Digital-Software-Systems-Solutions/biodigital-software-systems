import { FormEventHandler, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
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
    CheckCircleIcon,
} from '@heroicons/react/24/outline';

interface FormData {
    name: string;
    email: string;
    phone: string;
    subject: string;
    message: string;
}

interface Errors {
    name?: string;
    email?: string;
    phone?: string;
    subject?: string;
    message?: string;
}

export default function Create() {
    const { errors: serverErrors, flash } = usePage().props as {
        errors: Errors;
        flash: { success?: string };
    };

    const [data, setData] = useState<FormData>({
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: '',
    });
    const [processing, setProcessing] = useState(false);
    const [showSuccess, setShowSuccess] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(route('contacts.store'), data as any, {
            onSuccess: () => {
                setShowSuccess(true);
                setData({ name: '', email: '', phone: '', subject: '', message: '' });
                setTimeout(() => setShowSuccess(false), 5000);
            },
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <>
            <Head title="Contactez-nous" />
            <div className="min-h-screen bg-gradient-to-br from-icc-blue/5 via-background to-icc-purple/5">
                {/* Header Navigation */}
                <nav className="border-b bg-card/50 backdrop-blur supports-[backdrop-filter]:bg-card/50 sticky top-0 z-50">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between h-16">
                            <div className="flex items-center">
                                <div className="flex-shrink-0 flex items-center gap-3">
                                    <img src="/Logo.png" alt="ICC München" className="h-10 w-10 object-contain" />
                                    <h1 className="text-2xl font-bold bg-gradient-to-r from-icc-blue via-icc-purple to-icc-red bg-clip-text text-transparent">
                                        ICC München
                                    </h1>
                                </div>
                            </div>
                            <div className="flex items-center gap-3">
                                <Button variant="ghost" asChild>
                                    <a href="/">Retour à l'accueil</a>
                                </Button>
                            </div>
                        </div>
                    </div>
                </nav>

                {/* Contact Form Section */}
                <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-16">
                    <div className="mx-auto">
                        {/* Success Message */}
                        {(showSuccess || flash?.success) && (
                            <div className="mb-8 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 animate-in fade-in slide-in-from-top-2 duration-300">
                                <div className="flex items-start gap-3">
                                    <CheckCircleIcon className="h-6 w-6 text-green-600 dark:text-green-400 flex-shrink-0" />
                                    <div>
                                        <h3 className="font-semibold text-green-900 dark:text-green-100">
                                            Message envoyé avec succès !
                                        </h3>
                                        <p className="text-sm text-green-700 dark:text-green-300 mt-1">
                                            {flash?.success || 'Votre message a été envoyé avec succès. Nous vous répondrons bientôt.'}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}

                        <div className="grid gap-8 lg:grid-cols-2">
                            {/* Left Column - Contact Info */}
                            <div className="space-y-6">
                                <div className="space-y-4">
                                    <h1 className="text-4xl font-bold tracking-tight bg-gradient-to-r from-icc-blue via-icc-purple to-icc-red bg-clip-text text-transparent">
                                        Contactez-nous
                                    </h1>
                                    <p className="text-lg text-muted-foreground">
                                        Vous avez une question, une suggestion ou besoin d'aide ? N'hésitez pas à nous contacter. Notre équipe vous répondra dans les plus brefs délais.
                                    </p>
                                </div>

                                <Card className="border-icc-blue/20">
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <EnvelopeIcon className="h-5 w-5 text-icc-blue" />
                                            Coordonnées
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="flex items-start gap-3">
                                            <EnvelopeIcon className="h-5 w-5 text-muted-foreground mt-0.5" />
                                            <div>
                                                <p className="font-medium">Email</p>
                                                <a href="mailto:contact@icc-muenchen.de" className="text-sm text-icc-blue hover:underline">
                                                    contact@icc-muenchen.de
                                                </a>
                                            </div>
                                        </div>
                                        <div className="flex items-start gap-3">
                                            <PhoneIcon className="h-5 w-5 text-muted-foreground mt-0.5" />
                                            <div>
                                                <p className="font-medium">Téléphone</p>
                                                <a href="tel:+498912345678" className="text-sm text-icc-blue hover:underline">
                                                    +49 89 1234 5678
                                                </a>
                                            </div>
                                        </div>
                                        <div className="flex items-start gap-3">
                                            <ChatBubbleLeftRightIcon className="h-5 w-5 text-muted-foreground mt-0.5" />
                                            <div>
                                                <p className="font-medium">Horaires</p>
                                                <p className="text-sm text-muted-foreground">
                                                    Lun - Ven : 9h00 - 18h00<br />
                                                    Sam : 10h00 - 14h00
                                                </p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Right Column - Contact Form */}
                            <Card className="shadow-lg">
                                <CardHeader>
                                    <CardTitle>Envoyez-nous un message</CardTitle>
                                    <CardDescription>
                                        Remplissez le formulaire ci-dessous et nous vous répondrons rapidement.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <form onSubmit={submit} className="space-y-6">
                                        <div className="space-y-2">
                                            <Label htmlFor="name">
                                                Nom complet <span className="text-destructive">*</span>
                                            </Label>
                                            <div className="relative">
                                                <UserIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-muted-foreground" />
                                                <Input
                                                    id="name"
                                                    type="text"
                                                    value={data.name}
                                                    onChange={(e) => setData({ ...data, name: e.target.value })}
                                                    className="pl-10"
                                                    placeholder="Jean Dupont"
                                                    required
                                                />
                                            </div>
                                            {serverErrors.name && (
                                                <p className="text-sm text-destructive">{serverErrors.name}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="email">
                                                Email <span className="text-destructive">*</span>
                                            </Label>
                                            <div className="relative">
                                                <EnvelopeIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-muted-foreground" />
                                                <Input
                                                    id="email"
                                                    type="email"
                                                    value={data.email}
                                                    onChange={(e) => setData({ ...data, email: e.target.value })}
                                                    className="pl-10"
                                                    placeholder="jean.dupont@example.com"
                                                    required
                                                />
                                            </div>
                                            {serverErrors.email && (
                                                <p className="text-sm text-destructive">{serverErrors.email}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="phone">Téléphone</Label>
                                            <div className="relative">
                                                <PhoneIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-muted-foreground" />
                                                <Input
                                                    id="phone"
                                                    type="tel"
                                                    value={data.phone}
                                                    onChange={(e) => setData({ ...data, phone: e.target.value })}
                                                    className="pl-10"
                                                    placeholder="+33 6 12 34 56 78"
                                                />
                                            </div>
                                            {serverErrors.phone && (
                                                <p className="text-sm text-destructive">{serverErrors.phone}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="subject">
                                                Sujet <span className="text-destructive">*</span>
                                            </Label>
                                            <Input
                                                id="subject"
                                                type="text"
                                                value={data.subject}
                                                onChange={(e) => setData({ ...data, subject: e.target.value })}
                                                placeholder="Comment puis-je vous aider ?"
                                                required
                                            />
                                            {serverErrors.subject && (
                                                <p className="text-sm text-destructive">{serverErrors.subject}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="message">
                                                Message <span className="text-destructive">*</span>
                                            </Label>
                                            <Textarea
                                                id="message"
                                                value={data.message}
                                                onChange={(e) => setData({ ...data, message: e.target.value })}
                                                placeholder="Décrivez votre demande en détail..."
                                                className="min-h-[150px]"
                                                required
                                            />
                                            {serverErrors.message && (
                                                <p className="text-sm text-destructive">{serverErrors.message}</p>
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
                                                    Envoi en cours...
                                                </>
                                            ) : (
                                                <>
                                                    <EnvelopeIcon className="h-5 w-5 mr-2" />
                                                    Envoyer le message
                                                </>
                                            )}
                                        </Button>
                                    </form>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
