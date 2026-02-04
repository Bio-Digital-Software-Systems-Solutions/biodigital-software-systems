import InputError from '@/Components/InputError';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Label } from '@/Components/ui/label';
import { RefreshCw } from 'lucide-react';

interface CaptchaData {
    image: string;
    token: string;
}

interface RegisterProps {
    captcha: CaptchaData;
}

export default function Register({ captcha: initialCaptcha }: RegisterProps) {
    const [captcha, setCaptcha] = useState<CaptchaData>(initialCaptcha);
    const [isRefreshingCaptcha, setIsRefreshingCaptcha] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm<{
        first_name: string;
        last_name: string;
        email: string;
        birth_date: string;
        avatar: File | null;
        password: string;
        password_confirmation: string;
        terms_accepted: boolean;
        newsletter: boolean;
        captcha_answer: string;
        captcha_token: string;
    }>({
        first_name: '',
        last_name: '',
        email: '',
        birth_date: '',
        avatar: null,
        password: '',
        password_confirmation: '',
        terms_accepted: false,
        newsletter: false,
        captcha_answer: '',
        captcha_token: initialCaptcha.token,
    });

    const refreshCaptcha = async () => {
        setIsRefreshingCaptcha(true);
        try {
            const response = await fetch(route('captcha.generate'));
            const newCaptcha = await response.json();
            setCaptcha(newCaptcha);
            setData('captcha_token', newCaptcha.token);
            setData('captcha_answer', '');
        } catch (error) {
            console.error('Failed to refresh captcha:', error);
        } finally {
            setIsRefreshingCaptcha(false);
        }
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('register'), {
            forceFormData: true,
            onFinish: () => reset('password', 'password_confirmation'),
            onError: () => {
                // Refresh captcha on error
                refreshCaptcha();
            },
        });
    };

    return (
        <GuestLayout title="Inscription">
            <Head title="Inscription" />

            <div className="container mx-auto px-4 py-6 sm:py-16 sm:px-6 lg:px-8">
                <div className="flex min-h-[calc(100vh-12rem)] sm:min-h-[calc(100vh-20rem)] items-center justify-center">
                    <div className="w-full max-w-2xl">
                        <div className="rounded-2xl border border-border bg-card p-4 sm:p-8 shadow-lg">
                            <div className="mb-4 sm:mb-8 text-center">
                                <h2 className="text-2xl sm:text-3xl font-bold text-foreground">
                                    Créer un compte
                                </h2>
                                <p className="mt-2 text-xs sm:text-sm text-muted-foreground">
                                    Rejoignez-nous et commencez votre parcours
                                </p>
                            </div>

                            <form onSubmit={submit} encType="multipart/form-data" className="space-y-4 sm:space-y-6">
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                    <div>
                                        <label htmlFor="first_name" className="block text-xs sm:text-sm font-medium text-foreground mb-1 sm:mb-2">
                                            Prénom
                                        </label>
                                        <input
                                            id="first_name"
                                            name="first_name"
                                            value={data.first_name}
                                            className="w-full px-3 sm:px-4 py-2 sm:py-3 rounded-lg border border-input bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent transition-all text-sm sm:text-base"
                                            autoComplete="given-name"
                                            autoFocus
                                            onChange={(e) => setData('first_name', e.target.value)}
                                            required
                                            placeholder="Jean"
                                        />
                                        <InputError message={errors.first_name} className="mt-1 sm:mt-2" />
                                    </div>

                                    <div>
                                        <label htmlFor="last_name" className="block text-xs sm:text-sm font-medium text-foreground mb-1 sm:mb-2">
                                            Nom
                                        </label>
                                        <input
                                            id="last_name"
                                            name="last_name"
                                            value={data.last_name}
                                            className="w-full px-3 sm:px-4 py-2 sm:py-3 rounded-lg border border-input bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent transition-all text-sm sm:text-base"
                                            autoComplete="family-name"
                                            onChange={(e) => setData('last_name', e.target.value)}
                                            required
                                            placeholder="Dupont"
                                        />
                                        <InputError message={errors.last_name} className="mt-1 sm:mt-2" />
                                    </div>
                                </div>

                                <div>
                                    <label htmlFor="email" className="block text-xs sm:text-sm font-medium text-foreground mb-1 sm:mb-2">
                                        Email
                                    </label>
                                    <input
                                        id="email"
                                        type="email"
                                        name="email"
                                        value={data.email}
                                        className="w-full px-3 sm:px-4 py-2 sm:py-3 rounded-lg border border-input bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent transition-all text-sm sm:text-base"
                                        autoComplete="username"
                                        onChange={(e) => setData('email', e.target.value)}
                                        required
                                        placeholder="jean.dupont@example.com"
                                    />
                                    <InputError message={errors.email} className="mt-1 sm:mt-2" />
                                </div>

                                <div>
                                    <label htmlFor="birth_date" className="block text-xs sm:text-sm font-medium text-foreground mb-1 sm:mb-2">
                                        Date de naissance
                                    </label>
                                    <input
                                        id="birth_date"
                                        type="date"
                                        name="birth_date"
                                        value={data.birth_date}
                                        className="w-full px-3 sm:px-4 py-2 sm:py-3 rounded-lg border border-input bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent transition-all text-sm sm:text-base"
                                        onChange={(e) => setData('birth_date', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.birth_date} className="mt-1 sm:mt-2" />
                                </div>

                                <div>
                                    <label htmlFor="avatar" className="block text-xs sm:text-sm font-medium text-foreground mb-1 sm:mb-2">
                                        Photo de profil (optionnel)
                                    </label>
                                    <input
                                        id="avatar"
                                        type="file"
                                        name="avatar"
                                        accept="image/*"
                                        className="w-full px-3 sm:px-4 py-2 sm:py-3 rounded-lg border border-input bg-background text-foreground file:mr-2 sm:file:mr-4 file:py-1.5 sm:file:py-2 file:px-3 sm:file:px-4 file:rounded-lg file:border-0 file:text-xs sm:file:text-sm file:font-semibold file:bg-primary file:text-primary-foreground hover:file:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring transition-all text-sm sm:text-base"
                                        onChange={(e) => setData('avatar', e.target.files?.[0] || null)}
                                    />
                                    <InputError message={errors.avatar} className="mt-1 sm:mt-2" />
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                    <div>
                                        <label htmlFor="password" className="block text-xs sm:text-sm font-medium text-foreground mb-1 sm:mb-2">
                                            Mot de passe
                                        </label>
                                        <input
                                            id="password"
                                            type="password"
                                            name="password"
                                            value={data.password}
                                            className="w-full px-3 sm:px-4 py-2 sm:py-3 rounded-lg border border-input bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent transition-all text-sm sm:text-base"
                                            autoComplete="new-password"
                                            onChange={(e) => setData('password', e.target.value)}
                                            required
                                            placeholder="••••••••"
                                        />
                                        <InputError message={errors.password} className="mt-1 sm:mt-2" />
                                    </div>

                                    <div>
                                        <label htmlFor="password_confirmation" className="block text-xs sm:text-sm font-medium text-foreground mb-1 sm:mb-2">
                                            Confirmer le mot de passe
                                        </label>
                                        <input
                                            id="password_confirmation"
                                            type="password"
                                            name="password_confirmation"
                                            value={data.password_confirmation}
                                            className="w-full px-3 sm:px-4 py-2 sm:py-3 rounded-lg border border-input bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent transition-all text-sm sm:text-base"
                                            autoComplete="new-password"
                                            onChange={(e) => setData('password_confirmation', e.target.value)}
                                            required
                                            placeholder="••••••••"
                                        />
                                        <InputError message={errors.password_confirmation} className="mt-1 sm:mt-2" />
                                    </div>
                                </div>

                                <div className="space-y-3 sm:space-y-4">
                                    <div className="flex items-start space-x-3">
                                        <Checkbox
                                            id="terms_accepted"
                                            checked={data.terms_accepted}
                                            onCheckedChange={(checked) => setData('terms_accepted', checked === true)}
                                            className="mt-0.5"
                                        />
                                        <div className="flex-1">
                                            <Label
                                                htmlFor="terms_accepted"
                                                className="text-xs sm:text-sm text-foreground cursor-pointer leading-relaxed"
                                            >
                                                J'accepte les{' '}
                                                <Link
                                                    href={route('terms-of-service')}
                                                    className="text-primary hover:underline font-medium"
                                                    target="_blank"
                                                >
                                                    conditions générales d'utilisation
                                                </Link>
                                                {' '}et la{' '}
                                                <Link
                                                    href={route('privacy-policy')}
                                                    className="text-primary hover:underline font-medium"
                                                    target="_blank"
                                                >
                                                    politique de confidentialité
                                                </Link>
                                                {' '}<span className="text-destructive">*</span>
                                            </Label>
                                            <InputError message={errors.terms_accepted} className="mt-1" />
                                        </div>
                                    </div>

                                    <div className="flex items-start space-x-3">
                                        <Checkbox
                                            id="newsletter"
                                            checked={data.newsletter}
                                            onCheckedChange={(checked) => setData('newsletter', checked === true)}
                                            className="mt-0.5"
                                        />
                                        <Label
                                            htmlFor="newsletter"
                                            className="text-xs sm:text-sm text-muted-foreground cursor-pointer leading-relaxed"
                                        >
                                            J'accepte de recevoir des emails et messages de la plateforme
                                            (actualités, événements, newsletters)
                                        </Label>
                                    </div>
                                </div>

                                <div className="rounded-lg border border-input bg-muted/50 p-4">
                                    <label htmlFor="captcha_answer" className="block text-xs sm:text-sm font-medium text-foreground mb-2">
                                        Code de vérification <span className="text-destructive">*</span>
                                    </label>
                                    <div className="flex items-center gap-3">
                                        <img
                                            src={captcha.image}
                                            alt="CAPTCHA"
                                            className="h-12 rounded border border-input bg-white"
                                        />
                                        <button
                                            type="button"
                                            onClick={refreshCaptcha}
                                            disabled={isRefreshingCaptcha}
                                            className="p-2 rounded-lg border border-input bg-background hover:bg-muted transition-colors disabled:opacity-50"
                                            title="Nouveau code"
                                        >
                                            <RefreshCw className={`h-4 w-4 ${isRefreshingCaptcha ? 'animate-spin' : ''}`} />
                                        </button>
                                    </div>
                                    <input
                                        id="captcha_answer"
                                        type="text"
                                        autoComplete="off"
                                        value={data.captcha_answer}
                                        className="mt-2 w-full max-w-[200px] px-3 sm:px-4 py-2 sm:py-3 rounded-lg border border-input bg-background text-foreground uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent transition-all text-sm sm:text-base"
                                        onChange={(e) => setData('captcha_answer', e.target.value.toUpperCase())}
                                        placeholder="Entrez le code ci-dessus"
                                        maxLength={5}
                                        required
                                    />
                                    <InputError message={errors.captcha_answer} className="mt-1" />
                                </div>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full py-3"
                                >
                                    {processing ? 'Création en cours...' : 'Créer mon compte'}
                                </Button>

                                <p className="text-center text-sm text-muted-foreground">
                                    Vous avez déjà un compte ?{' '}
                                    <Link
                                        href={route('login')}
                                        className="text-primary hover:underline font-medium"
                                    >
                                        Se connecter
                                    </Link>
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </GuestLayout>
    );
}
