import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import TextInput from '../TextInput';

describe('TextInput Component', () => {
    describe('Rendering', () => {
        it('renders input with correct type', () => {
            render(<TextInput type="email" />);
            const input = screen.getByRole('textbox');
            expect(input).toBeInTheDocument();
            expect(input).toHaveAttribute('type', 'email');
        });

        it('renders with placeholder', () => {
            render(<TextInput placeholder="Enter your email" />);
            expect(screen.getByPlaceholderText('Enter your email')).toBeInTheDocument();
        });

        it('applies className prop', () => {
            render(<TextInput className="custom-class" />);
            const input = screen.getByRole('textbox');
            expect(input).toHaveClass('custom-class');
        });

        it('renders with default value', () => {
            render(<TextInput defaultValue="test@example.com" />);
            const input = screen.getByRole('textbox') as HTMLInputElement;
            expect(input.value).toBe('test@example.com');
        });
    });

    describe('User Interaction', () => {
        it('handles onChange events', async () => {
            const user = userEvent.setup();
            let value = '';
            const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
                value = e.target.value;
            };

            render(<TextInput onChange={handleChange} />);
            const input = screen.getByRole('textbox');

            await user.type(input, 'test input');
            expect(value).toBe('test input');
        });

        it('can be focused', async () => {
            const user = userEvent.setup();
            render(<TextInput />);
            const input = screen.getByRole('textbox');

            await user.click(input);
            expect(input).toHaveFocus();
        });

        it('handles onFocus and onBlur events', async () => {
            const user = userEvent.setup();
            let focused = false;
            let blurred = false;

            render(
                <TextInput
                    onFocus={() => (focused = true)}
                    onBlur={() => (blurred = true)}
                />
            );

            const input = screen.getByRole('textbox');

            await user.click(input);
            expect(focused).toBe(true);

            await user.tab();
            expect(blurred).toBe(true);
        });
    });

    describe('Accessibility', () => {
        it('supports aria-label', () => {
            render(<TextInput aria-label="Email address" />);
            expect(screen.getByLabelText('Email address')).toBeInTheDocument();
        });

        it('supports aria-describedby', () => {
            render(<TextInput aria-describedby="email-help" />);
            const input = screen.getByRole('textbox');
            expect(input).toHaveAttribute('aria-describedby', 'email-help');
        });

        it('can be marked as required', () => {
            render(<TextInput required />);
            const input = screen.getByRole('textbox');
            expect(input).toBeRequired();
        });

        it('can be disabled', () => {
            render(<TextInput disabled />);
            const input = screen.getByRole('textbox');
            expect(input).toBeDisabled();
        });
    });

    describe('Input Types', () => {
        it('supports password type', () => {
            render(<TextInput type="password" />);
            const input = screen.getByRole('textbox', { hidden: true });
            expect(input).toHaveAttribute('type', 'password');
        });

        it('supports text type by default', () => {
            render(<TextInput />);
            const input = screen.getByRole('textbox');
            expect(input).toHaveAttribute('type', 'text');
        });

        it('supports number type', () => {
            render(<TextInput type="number" />);
            const input = screen.getByRole('spinbutton');
            expect(input).toHaveAttribute('type', 'number');
        });
    });

    describe('Form Integration', () => {
        it('works with form submission', async () => {
            const user = userEvent.setup();
            let submittedValue = '';

            const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
                e.preventDefault();
                const formData = new FormData(e.currentTarget);
                submittedValue = formData.get('email') as string;
            };

            render(
                <form onSubmit={handleSubmit}>
                    <TextInput name="email" />
                    <button type="submit">Submit</button>
                </form>
            );

            const input = screen.getByRole('textbox');
            const button = screen.getByText('Submit');

            await user.type(input, 'test@example.com');
            await user.click(button);

            expect(submittedValue).toBe('test@example.com');
        });

        it('supports name attribute', () => {
            render(<TextInput name="username" />);
            const input = screen.getByRole('textbox');
            expect(input).toHaveAttribute('name', 'username');
        });

        it('supports id attribute', () => {
            render(<TextInput id="email-input" />);
            const input = screen.getByRole('textbox');
            expect(input).toHaveAttribute('id', 'email-input');
        });
    });

    describe('Security', () => {
        it('does not allow script injection in value', async () => {
            const user = userEvent.setup();
            render(<TextInput />);
            const input = screen.getByRole('textbox') as HTMLInputElement;

            await user.type(input, '<script>alert("xss")</script>');

            // Value should be treated as plain text, not executed
            expect(input.value).toBe('<script>alert("xss")</script>');
            // Ensure no script was injected into DOM
            expect(document.querySelector('script')).not.toBeInTheDocument();
        });

        it('sanitizes dangerous attributes', () => {
            // @ts-expect-error Testing dangerous props
            render(<TextInput onclick="alert('xss')" />);
            const input = screen.getByRole('textbox');
            // React should not allow onclick as a prop
            expect(input).not.toHaveAttribute('onclick');
        });
    });
});
