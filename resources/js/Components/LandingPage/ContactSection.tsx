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

interface ContactSectionProps {
    isAuthenticated: boolean;
}

export default function ContactSection({ isAuthenticated }: ContactSectionProps) {
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
                toast.success('Message envoyé avec succès !', {
                    description: 'Nous vous répondrons dans les plus brefs délais.',
                    duration: 5000,
                });
                setContactData({ name: '', email: '', phone: '', subject: '', message: '' });
            },
            onError: (serverErrors) => {
                setErrors(serverErrors as Record<string, string>);
                toast.error('Erreur lors de l\'envoi du message', {
                    description: 'Veuillez vérifier les champs et réessayer.',
                    duration: 5000,
                });
            },
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <section id="contact" className="py-20 bg-gradient-to-br from-icc-blue/5 via-icc-purple/5 to-icc-red/5">
            <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                <div className="max-w-6xl mx-auto">
                    <div className="text-center space-y-4 mb-12">
                        <Badge variant="secondary" className="mb-2">
                            Contact
                        </Badge>
                        <h2 className="text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl">
                            Contactez-nous
                        </h2>
                        <p className="mx-auto max-w-2xl text-lg text-muted-foreground">
                            Une question, une suggestion ou besoin d'aide ? N'hésitez pas à nous contacter.
                        </p>
                    </div>

                    <div className="grid gap-8 lg:grid-cols-2">
                        {/* Left Column - Contact Info & CTA */}
                        <div className="space-y-6">
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
                                            <a href="mailto:icc-munich@impactcentrechretien.eu" className="text-sm text-icc-blue hover:underline">
                                                icc-munich@impactcentrechretien.eu
                                            </a>
                                        </div>
                                    </div>
                                    <div className="flex items-start gap-3">
                                        <PhoneIcon className="h-5 w-5 text-muted-foreground mt-0.5" />
                                        <div>
                                            <p className="font-medium">Téléphone</p>
                                            <a href="tel:+4917673200275" className="text-sm text-icc-blue hover:underline">
                                                +49 (0) 17673200275
                                            </a>
                                        </div>
                                    </div>
                                    <div className="flex items-start gap-3">
                                        <ChatBubbleLeftRightIcon className="h-5 w-5 text-muted-foreground mt-0.5" />
                                        <div>
                                            <p className="font-medium">Horaires</p>
                                            <p className="text-sm text-muted-foreground">
                                                Lun - Jeu :  - <br />
                                                Ven : 18h00 - 21h30<br />
                                                Sam : 10h00 - 14h00<br />
                                                Dim: 10h00 - 16h00
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-start gap-3">
                                        <MapPinIcon className="h-5 w-5 text-muted-foreground mt-0.5" />
                                        <div>
                                            <p className="font-medium">Adresse</p>
                                            <p className="text-sm text-muted-foreground">
                                                Kapellenstraße 22<br />
                                                82008 Unterhaching<br />
                                                Allemagne
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {!isAuthenticated && (
                                <Card className="bg-gradient-to-br from-icc-blue/10 to-icc-purple/10 border-icc-blue/20">
                                    <CardHeader>
                                        <CardTitle>Rejoignez-nous</CardTitle>
                                        <CardDescription>
                                            Découvrez tous les outils dont vous avez besoin
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="flex flex-col gap-3">
                                        <Button size="lg" asChild className="w-full">
                                            <Link href={route('register')}>
                                                S'inscrire Gratuitement
                                            </Link>
                                        </Button>
                                        <Button size="lg" variant="outline" asChild className="w-full">
                                            <Link href={route('login')}>
                                                Se Connecter
                                            </Link>
                                        </Button>
                                    </CardContent>
                                </Card>
                            )}

                            {isAuthenticated && (
                                <Card className="bg-gradient-to-br from-icc-blue/10 to-icc-purple/10 border-icc-blue/20">
                                    <CardHeader>
                                        <CardTitle>Accéder au Dashboard</CardTitle>
                                        <CardDescription>
                                            Gérez vos événements, articles et bien plus
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <Button size="lg" asChild className="w-full">
                                            <Link href={route('dashboard')}>
                                                Accéder au Dashboard
                                            </Link>
                                        </Button>
                                    </CardContent>
                                </Card>
                            )}
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
                                <form onSubmit={handleContactSubmit} className="space-y-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">
                                            Nom complet <span className="text-destructive">*</span>
                                        </Label>
                                        <div className="relative">
                                            <UserIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-muted-foreground" />
                                            <Input
                                                id="name"
                                                type="text"
                                                value={contactData.name}
                                                onChange={(e) => setContactData({ ...contactData, name: e.target.value })}
                                                className="pl-10"
                                                placeholder="Jean Dupont"
                                                required
                                            />
                                        </div>
                                        {errors.name && (
                                            <p className="text-sm text-destructive">{errors.name}</p>
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
                                                value={contactData.email}
                                                onChange={(e) => setContactData({ ...contactData, email: e.target.value })}
                                                className="pl-10"
                                                placeholder="jean.dupont@example.com"
                                                required
                                            />
                                        </div>
                                        {errors.email && (
                                            <p className="text-sm text-destructive">{errors.email}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="phone">Téléphone</Label>
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
                                            Sujet <span className="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id="subject"
                                            type="text"
                                            value={contactData.subject}
                                            onChange={(e) => setContactData({ ...contactData, subject: e.target.value })}
                                            placeholder="Comment puis-je vous aider ?"
                                            required
                                        />
                                        {errors.subject && (
                                            <p className="text-sm text-destructive">{errors.subject}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="message">
                                            Message <span className="text-destructive">*</span>
                                        </Label>
                                        <Textarea
                                            id="message"
                                            value={contactData.message}
                                            onChange={(e) => setContactData({ ...contactData, message: e.target.value })}
                                            placeholder="Décrivez votre demande en détail..."
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
        </section>
    );
}
