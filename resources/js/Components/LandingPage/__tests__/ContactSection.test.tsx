import { render, screen, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import i18n from '@/i18n';
import ContactSection from '@/Components/LandingPage/ContactSection';

const mockPost = vi.fn();
const mockToastSuccess = vi.fn();
const mockToastError = vi.fn();

vi.mock('@inertiajs/react', () => ({
    router: {
        post: (...args: unknown[]) => mockPost(...args),
    },
    Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
}));

vi.mock('sonner', () => ({
    toast: {
        success: (...args: unknown[]) => mockToastSuccess(...args),
        error: (...args: unknown[]) => mockToastError(...args),
    },
}));

(global as unknown as { route: (name: string) => string }).route = (name: string) => `/${name}`;

beforeEach(async () => {
    vi.clearAllMocks();
    await act(async () => {
        await i18n.changeLanguage('fr');
    });
});

const fillForm = async (user: ReturnType<typeof userEvent.setup>) => {
    await user.type(screen.getByLabelText(/Nom complet/), 'Jean Dupont');
    await user.type(screen.getByLabelText(/Email/), 'jean.dupont@example.com');
    await user.type(screen.getByLabelText(/Sujet/), 'Demande de renseignements');
    await user.type(screen.getByLabelText(/Message/), 'Bonjour, je souhaite plus d’informations.');
};

describe('ContactSection', () => {
    it('renders the contact form labels translated, and re-translates on language change', async () => {
        render(<ContactSection isAuthenticated={false} />);

        expect(screen.getByText('Envoyez-nous un message')).toBeInTheDocument();
        expect(screen.getByText('Contactez-nous')).toBeInTheDocument();

        await act(async () => {
            await i18n.changeLanguage('de');
        });

        expect(screen.getByText('Senden Sie uns eine Nachricht')).toBeInTheDocument();
        expect(screen.getByText('Kontaktieren Sie uns')).toBeInTheDocument();
    });

    it('submits the form through Inertia with the contacts.store route and entered data', async () => {
        const user = userEvent.setup();
        render(<ContactSection isAuthenticated={false} />);

        await fillForm(user);
        await user.click(screen.getByRole('button', { name: /Envoyer le message/ }));

        expect(mockPost).toHaveBeenCalledTimes(1);
        const [url, data] = mockPost.mock.calls[0];
        expect(url).toBe('/contacts.store');
        expect(data).toMatchObject({
            name: 'Jean Dupont',
            email: 'jean.dupont@example.com',
            subject: 'Demande de renseignements',
        });
    });

    it('shows a translated success toast and resets the form on success', async () => {
        mockPost.mockImplementation((_url, _data, options) => {
            options.onSuccess?.();
            options.onFinish?.();
        });

        const user = userEvent.setup();
        render(<ContactSection isAuthenticated={false} />);

        await fillForm(user);
        await user.click(screen.getByRole('button', { name: /Envoyer le message/ }));

        expect(mockToastSuccess).toHaveBeenCalledWith(
            'Message envoyé avec succès !',
            expect.objectContaining({ description: 'Nous vous répondrons dans les plus brefs délais.' }),
        );

        // Form has been reset.
        expect(screen.getByLabelText(/Nom complet/)).toHaveValue('');
        expect(screen.getByLabelText(/Sujet/)).toHaveValue('');
    });

    it('shows a translated error toast and renders server validation errors on failure', async () => {
        mockPost.mockImplementation((_url, _data, options) => {
            options.onError?.({ email: 'Email invalide' });
            options.onFinish?.();
        });

        const user = userEvent.setup();
        render(<ContactSection isAuthenticated={false} />);

        await fillForm(user);
        await user.click(screen.getByRole('button', { name: /Envoyer le message/ }));

        expect(mockToastError).toHaveBeenCalledWith(
            'Erreur lors de l\'envoi du message',
            expect.objectContaining({ description: 'Veuillez vérifier les champs et réessayer.' }),
        );
        expect(screen.getByText('Email invalide')).toBeInTheDocument();
    });

    it('uses the translated success toast in German', async () => {
        await act(async () => {
            await i18n.changeLanguage('de');
        });
        mockPost.mockImplementation((_url, _data, options) => {
            options.onSuccess?.();
            options.onFinish?.();
        });

        const user = userEvent.setup();
        render(<ContactSection isAuthenticated={false} />);

        await user.type(screen.getByLabelText(/Vollständiger Name/), 'Max Mustermann');
        await user.type(screen.getByLabelText(/E-Mail/), 'max@example.com');
        await user.type(screen.getByLabelText(/Betreff/), 'Anfrage');
        await user.type(screen.getByLabelText(/Nachricht/), 'Hallo, ich hätte gerne mehr Informationen.');
        await user.click(screen.getByRole('button', { name: /Nachricht senden/ }));

        expect(mockToastSuccess).toHaveBeenCalledWith(
            'Nachricht erfolgreich gesendet!',
            expect.objectContaining({ description: 'Wir melden uns schnellstmöglich bei Ihnen.' }),
        );
    });
});
