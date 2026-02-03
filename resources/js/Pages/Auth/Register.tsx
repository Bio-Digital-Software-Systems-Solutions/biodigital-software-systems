import InputError from '@/Components/InputError';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { Button } from '@/Components/ui/button';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm<{
        first_name: string;
        last_name: string;
        email: string;
        birth_date: string;
        avatar: File | null;
        password: string;
        password_confirmation: string;
    }>({
        first_name: '',
        last_name: '',
        email: '',
        birth_date: '',
        avatar: null,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('register'), {
            forceFormData: true,
            onFinish: () => reset('password', 'password_confirmation'),
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
