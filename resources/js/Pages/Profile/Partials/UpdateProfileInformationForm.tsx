import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Transition } from '@headlessui/react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useState, useEffect } from 'react';
import { PhotoIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { toast } from 'sonner';

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    className = '',
}: {
    mustVerifyEmail: boolean;
    status?: string;
    className?: string;
}) {
    type User = {
        first_name?: string;
        last_name?: string;
        email: string;
        avatar?: string;
        birth_date?: string;
        email_verified_at?: string | null;
    };

    const user = usePage().props.auth.user as User;
    const [avatarPreview, setAvatarPreview] = useState<string | null>(null);

    // Format birth_date to YYYY-MM-DD if it exists
    const formatBirthDate = (date: any) => {
        if (!date) return '';

        // If it's already a Date object
        if (date instanceof Date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Convert to string
        const dateStr = String(date);

        // If date is in DD.MM.YYYY format, convert to YYYY-MM-DD
        if (dateStr.includes('.')) {
            const parts = dateStr.split('.');
            if (parts.length === 3) {
                const [day, month, year] = parts;
                return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
            }
        }

        // If it's an ISO date string or contains 'T', extract YYYY-MM-DD
        if (dateStr.includes('T')) {
            return dateStr.split('T')[0];
        }

        // If already in YYYY-MM-DD format
        if (dateStr.match(/^\d{4}-\d{2}-\d{2}$/)) {
            return dateStr;
        }

        // Try to parse as a date
        try {
            const parsed = new Date(dateStr);
            if (!isNaN(parsed.getTime())) {
                const year = parsed.getFullYear();
                const month = String(parsed.getMonth() + 1).padStart(2, '0');
                const day = String(parsed.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }
        } catch (e) {
            // Silently fail for invalid date formats
        }

        return '';
    };

    const { data, setData, post, errors, processing, recentlySuccessful } =
        useForm({
            first_name: user.first_name || '',
            last_name: user.last_name || '',
            email: user.email,
            birth_date: formatBirthDate(user.birth_date) || '',
            avatar: null as File | null,
            _method: 'PATCH',
        });

    // Update form data when user object reference changes (after successful save)
    // Using useMemo to create a stable reference for comparison
    const userSignature = `${user.first_name}-${user.last_name}-${user.email}-${user.birth_date}`;

    useEffect(() => {
        // Only update if form data is stale (different from current user)
        if (data.first_name !== user.first_name ||
            data.last_name !== user.last_name ||
            data.email !== user.email ||
            data.birth_date !== formatBirthDate(user.birth_date)) {
            setData({
                first_name: user.first_name || '',
                last_name: user.last_name || '',
                email: user.email,
                birth_date: formatBirthDate(user.birth_date) || '',
                avatar: null,
                _method: 'PATCH',
            });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [userSignature]); // Only trigger when user data actually changes

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('profile.update'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                // Clear avatar preview to show the uploaded image from server
                setAvatarPreview(null);
                // Reset the avatar field in the form
                setData('avatar', null);
                // Clear the file input
                const input = document.getElementById('avatar') as HTMLInputElement;
                if (input) {
                    input.value = '';
                }

                toast.success('Profil mis à jour avec succès', {
                    description: 'Vos informations ont été enregistrées.',
                });
            },
            onError: (errors) => {
                const errorMessages = Object.values(errors).join(', ');
                toast.error('Erreur lors de la mise à jour', {
                    description: errorMessages || 'Une erreur est survenue lors de la mise à jour de votre profil.',
                });
            },
        });
    };

    const handleAvatarChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setData('avatar', file);
            const reader = new FileReader();
            reader.onload = (e) => {
                setAvatarPreview(e.target?.result as string);
            };
            reader.readAsDataURL(file);
        }
    };

    const removeAvatar = () => {
        setData('avatar', null);
        setAvatarPreview(null);
        const input = document.getElementById('avatar') as HTMLInputElement;
        if (input) {
            input.value = '';
        }
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900 dark:text-white">
                    Informations du profil
                </h2>

                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Mettez à jour les informations de votre profil et votre adresse e-mail.
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                {/* Avatar Section */}
                <div>
                    <InputLabel htmlFor="avatar" value="Photo de profil (Optionnel)" />
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Image de profil (max 10 MB, formats: JPG, PNG, GIF)
                    </p>

                    <div className="mt-4 flex items-start gap-6">
                        {/* Current Avatar Display */}
                        <div className="flex-shrink-0">
                            {user.avatar && !avatarPreview ? (
                                <img
                                    src={`/storage/${user.avatar}`}
                                    alt="Current avatar"
                                    className="h-32 w-32 rounded-full object-cover border-4 border-primary shadow-lg"
                                />
                            ) : !avatarPreview ? (
                                <div className="h-32 w-32 rounded-full bg-primary flex items-center justify-center text-white text-4xl font-bold shadow-lg">
                                    {user.first_name?.[0]}{user.last_name?.[0]}
                                </div>
                            ) : null}
                        </div>

                        {/* Upload Area */}
                        <div className="flex-1">
                            <div className="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-md">
                                {avatarPreview ? (
                                    <div className="relative">
                                        <img
                                            src={avatarPreview}
                                            alt="Avatar preview"
                                            className="h-48 w-48 rounded-full object-cover border-4 border-primary shadow-xl"
                                        />
                                        <button
                                            type="button"
                                            onClick={removeAvatar}
                                            className="absolute top-2 right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600 shadow-lg"
                                        >
                                            <XMarkIcon className="w-5 h-5" />
                                        </button>
                                    </div>
                                ) : (
                                    <div className="space-y-1 text-center">
                                        <PhotoIcon className="mx-auto h-12 w-12 text-gray-400" />
                                        <div className="flex text-sm text-gray-600 dark:text-gray-400">
                                            <label
                                                htmlFor="avatar"
                                                className="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-primary hover:text-primary focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary"
                                            >
                                                <span>Téléverser une nouvelle photo</span>
                                                <input
                                                    id="avatar"
                                                    name="avatar"
                                                    type="file"
                                                    className="sr-only"
                                                    accept="image/*"
                                                    onChange={handleAvatarChange}
                                                />
                                            </label>
                                            <p className="pl-1">ou glisser-déposer</p>
                                        </div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            PNG, JPG, GIF jusqu'à 10MB
                                        </p>
                                    </div>
                                )}
                            </div>
                            <InputError className="mt-2" message={errors.avatar} />
                        </div>
                    </div>
                </div>

                {/* Form Fields */}
                <div className="space-y-6">
                <div>
                    <InputLabel htmlFor="first_name" value="First Name" />

                    <TextInput
                        id="first_name"
                        className="mt-1 block w-full"
                        value={data.first_name}
                        onChange={(e) => setData('first_name', e.target.value)}
                        required
                        isFocused
                        autoComplete="given-name"
                    />

                    <InputError className="mt-2" message={errors.first_name} />
                </div>

                <div>
                    <InputLabel htmlFor="last_name" value="Last Name" />

                    <TextInput
                        id="last_name"
                        className="mt-1 block w-full"
                        value={data.last_name}
                        onChange={(e) => setData('last_name', e.target.value)}
                        required
                        autoComplete="family-name"
                    />

                    <InputError className="mt-2" message={errors.last_name} />
                </div>

                <div>
                    <InputLabel htmlFor="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        className="mt-1 block w-full"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                        autoComplete="username"
                    />

                    <InputError className="mt-2" message={errors.email} />
                </div>

                <div>
                    <InputLabel htmlFor="birth_date" value="Birth Date" />

                    <TextInput
                        id="birth_date"
                        type="date"
                        className="mt-1 block w-full"
                        value={data.birth_date}
                        onChange={(e) => setData('birth_date', e.target.value)}
                        required
                    />

                    <InputError className="mt-2" message={errors.birth_date} />
                </div>


                {mustVerifyEmail && user.email_verified_at === null && (
                    <div>
                        <p className="mt-2 text-sm text-gray-800">
                            Your email address is unverified.
                            <Link
                                href={route('verification.send')}
                                method="post"
                                as="button"
                                className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                            >
                                Click here to re-send the verification email.
                            </Link>
                        </p>

                        {status === 'verification-link-sent' && (
                            <div className="mt-2 text-sm font-medium text-green-600">
                                A new verification link has been sent to your
                                email address.
                            </div>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-4 pt-4">
                    <PrimaryButton disabled={processing}>Save</PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-gray-600">
                            Saved.
                        </p>
                    </Transition>
                </div>
                </div>
            </form>
        </section>
    );
}
