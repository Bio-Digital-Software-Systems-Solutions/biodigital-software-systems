import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Checkbox from '../Checkbox';

describe('Checkbox Component', () => {
    describe('Rendering', () => {
        it('renders checkbox input', () => {
            render(<Checkbox />);
            const checkbox = screen.getByRole('checkbox');
            expect(checkbox).toBeInTheDocument();
        });

        it('applies className prop', () => {
            render(<Checkbox className="custom-checkbox" />);
            const checkbox = screen.getByRole('checkbox');
            expect(checkbox).toHaveClass('custom-checkbox');
        });

        it('renders unchecked by default', () => {
            render(<Checkbox />);
            const checkbox = screen.getByRole('checkbox') as HTMLInputElement;
            expect(checkbox.checked).toBe(false);
        });

        it('renders checked when defaultChecked is true', () => {
            render(<Checkbox defaultChecked />);
            const checkbox = screen.getByRole('checkbox') as HTMLInputElement;
            expect(checkbox.checked).toBe(true);
        });
    });

    describe('User Interaction', () => {
        it('can be checked by clicking', async () => {
            const user = userEvent.setup();
            render(<Checkbox />);
            const checkbox = screen.getByRole('checkbox') as HTMLInputElement;

            expect(checkbox.checked).toBe(false);

            await user.click(checkbox);
            expect(checkbox.checked).toBe(true);

            await user.click(checkbox);
            expect(checkbox.checked).toBe(false);
        });

        it('triggers onChange when clicked', async () => {
            const user = userEvent.setup();
            let checked = false;
            const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
                checked = e.target.checked;
            };

            render(<Checkbox onChange={handleChange} />);
            const checkbox = screen.getByRole('checkbox');

            await user.click(checkbox);
            expect(checked).toBe(true);
        });

        it('can be checked via keyboard (Space key)', async () => {
            const user = userEvent.setup();
            render(<Checkbox />);
            const checkbox = screen.getByRole('checkbox') as HTMLInputElement;

            checkbox.focus();
            await user.keyboard(' ');

            expect(checkbox.checked).toBe(true);
        });
    });

    describe('States', () => {
        it('can be disabled', () => {
            render(<Checkbox disabled />);
            const checkbox = screen.getByRole('checkbox');
            expect(checkbox).toBeDisabled();
        });

        it('cannot be clicked when disabled', async () => {
            const user = userEvent.setup();
            render(<Checkbox disabled />);
            const checkbox = screen.getByRole('checkbox') as HTMLInputElement;

            await user.click(checkbox);
            expect(checkbox.checked).toBe(false);
        });

        it('can be required', () => {
            render(<Checkbox required />);
            const checkbox = screen.getByRole('checkbox');
            expect(checkbox).toBeRequired();
        });
    });

    describe('Form Integration', () => {
        it('works with form submission', async () => {
            const user = userEvent.setup();
            let submittedValue = '';

            const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
                e.preventDefault();
                const formData = new FormData(e.currentTarget);
                submittedValue = formData.get('terms') as string;
            };

            render(
                <form onSubmit={handleSubmit}>
                    <Checkbox name="terms" value="accepted" />
                    <button type="submit">Submit</button>
                </form>
            );

            const checkbox = screen.getByRole('checkbox');
            const button = screen.getByText('Submit');

            await user.click(checkbox);
            await user.click(button);

            expect(submittedValue).toBe('accepted');
        });

        it('submits nothing when unchecked', async () => {
            const user = userEvent.setup();
            let submittedValue: string | null = 'initial';

            const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
                e.preventDefault();
                const formData = new FormData(e.currentTarget);
                submittedValue = formData.get('terms') as string | null;
            };

            render(
                <form onSubmit={handleSubmit}>
                    <Checkbox name="terms" value="accepted" />
                    <button type="submit">Submit</button>
                </form>
            );

            const button = screen.getByText('Submit');
            await user.click(button);

            expect(submittedValue).toBeNull();
        });

        it('supports name attribute', () => {
            render(<Checkbox name="remember-me" />);
            const checkbox = screen.getByRole('checkbox');
            expect(checkbox).toHaveAttribute('name', 'remember-me');
        });

        it('supports value attribute', () => {
            render(<Checkbox value="yes" />);
            const checkbox = screen.getByRole('checkbox');
            expect(checkbox).toHaveAttribute('value', 'yes');
        });

        it('supports id attribute', () => {
            render(<Checkbox id="terms-checkbox" />);
            const checkbox = screen.getByRole('checkbox');
            expect(checkbox).toHaveAttribute('id', 'terms-checkbox');
        });
    });

    describe('Accessibility', () => {
        it('supports aria-label', () => {
            render(<Checkbox aria-label="Accept terms and conditions" />);
            expect(
                screen.getByLabelText('Accept terms and conditions')
            ).toBeInTheDocument();
        });

        it('supports aria-describedby', () => {
            render(<Checkbox aria-describedby="terms-description" />);
            const checkbox = screen.getByRole('checkbox');
            expect(checkbox).toHaveAttribute('aria-describedby', 'terms-description');
        });

        it('can be associated with a label', () => {
            render(
                <div>
                    <label htmlFor="my-checkbox">My Checkbox</label>
                    <Checkbox id="my-checkbox" />
                </div>
            );

            const checkbox = screen.getByLabelText('My Checkbox');
            expect(checkbox).toBeInTheDocument();
        });

        it('is keyboard accessible', () => {
            render(<Checkbox />);
            const checkbox = screen.getByRole('checkbox');

            // Should be focusable
            checkbox.focus();
            expect(checkbox).toHaveFocus();
        });
    });

    describe('Controlled Component', () => {
        it('works as a controlled component', async () => {
            const user = userEvent.setup();
            let checked = false;
            const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
                checked = e.target.checked;
            };

            const { rerender } = render(
                <Checkbox checked={checked} onChange={handleChange} />
            );

            const checkbox = screen.getByRole('checkbox') as HTMLInputElement;
            expect(checkbox.checked).toBe(false);

            await user.click(checkbox);
            rerender(<Checkbox checked={checked} onChange={handleChange} />);

            expect(checkbox.checked).toBe(true);
        });
    });

    describe('Security', () => {
        it('does not execute scripts in value attribute', () => {
            render(<Checkbox value="<script>alert('xss')</script>" />);
            const checkbox = screen.getByRole('checkbox');

            // Value should be treated as string, not executed
            expect(checkbox).toHaveAttribute('value', "<script>alert('xss')</script>");
            expect(document.querySelector('script')).not.toBeInTheDocument();
        });

        it('sanitizes dangerous event handlers', () => {
            // @ts-expect-error Testing dangerous props
            render(<Checkbox onclick="alert('xss')" />);
            const checkbox = screen.getByRole('checkbox');

            // React should not allow onclick as a prop
            expect(checkbox).not.toHaveAttribute('onclick');
        });
    });
});
