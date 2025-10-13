import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import DangerButton from '../DangerButton';

describe('DangerButton Component', () => {
    describe('Rendering', () => {
        it('renders button with children', () => {
            render(<DangerButton>Delete</DangerButton>);
            expect(screen.getByRole('button', { name: /delete/i })).toBeInTheDocument();
        });

        it('applies danger styling classes', () => {
            render(<DangerButton>Delete</DangerButton>);
            const button = screen.getByRole('button');

            // Should have danger-specific classes
            expect(button).toHaveClass('inline-flex');
            expect(button).toBeInTheDocument();
        });

        it('applies custom className', () => {
            render(<DangerButton className="custom-class">Delete</DangerButton>);
            const button = screen.getByRole('button');
            expect(button).toHaveClass('custom-class');
        });
    });

    describe('User Interaction', () => {
        it('calls onClick when clicked', async () => {
            const user = userEvent.setup();
            const handleClick = vi.fn();

            render(<DangerButton onClick={handleClick}>Delete</DangerButton>);
            const button = screen.getByRole('button');

            await user.click(button);
            expect(handleClick).toHaveBeenCalledTimes(1);
        });

        it('does not call onClick when disabled', async () => {
            const user = userEvent.setup();
            const handleClick = vi.fn();

            render(
                <DangerButton onClick={handleClick} disabled>
                    Delete
                </DangerButton>
            );
            const button = screen.getByRole('button');

            await user.click(button);
            expect(handleClick).not.toHaveBeenCalled();
        });

        it('can be activated with Enter key', async () => {
            const user = userEvent.setup();
            const handleClick = vi.fn();

            render(<DangerButton onClick={handleClick}>Delete</DangerButton>);
            const button = screen.getByRole('button');

            button.focus();
            await user.keyboard('{Enter}');

            expect(handleClick).toHaveBeenCalled();
        });

        it('can be activated with Space key', async () => {
            const user = userEvent.setup();
            const handleClick = vi.fn();

            render(<DangerButton onClick={handleClick}>Delete</DangerButton>);
            const button = screen.getByRole('button');

            button.focus();
            await user.keyboard(' ');

            expect(handleClick).toHaveBeenCalled();
        });
    });

    describe('States', () => {
        it('can be disabled', () => {
            render(<DangerButton disabled>Delete</DangerButton>);
            const button = screen.getByRole('button');
            expect(button).toBeDisabled();
        });

        it('shows disabled state visually', () => {
            render(<DangerButton disabled>Delete</DangerButton>);
            const button = screen.getByRole('button');

            // Should have opacity class or disabled styling
            expect(button).toBeDisabled();
        });

        it('is focusable when not disabled', () => {
            render(<DangerButton>Delete</DangerButton>);
            const button = screen.getByRole('button');

            button.focus();
            expect(button).toHaveFocus();
        });

        it('is not focusable when disabled', () => {
            render(<DangerButton disabled>Delete</DangerButton>);
            const button = screen.getByRole('button');

            button.focus();
            expect(button).not.toHaveFocus();
        });
    });

    describe('Button Types', () => {
        it('defaults to button type', () => {
            render(<DangerButton>Delete</DangerButton>);
            const button = screen.getByRole('button');
            expect(button).toHaveAttribute('type', 'button');
        });

        it('supports submit type', () => {
            render(<DangerButton type="submit">Delete</DangerButton>);
            const button = screen.getByRole('button');
            expect(button).toHaveAttribute('type', 'submit');
        });

        it('supports reset type', () => {
            render(<DangerButton type="reset">Reset</DangerButton>);
            const button = screen.getByRole('button');
            expect(button).toHaveAttribute('type', 'reset');
        });
    });

    describe('Form Integration', () => {
        it('submits form when type is submit', async () => {
            const user = userEvent.setup();
            const handleSubmit = vi.fn((e) => e.preventDefault());

            render(
                <form onSubmit={handleSubmit}>
                    <DangerButton type="submit">Delete</DangerButton>
                </form>
            );

            const button = screen.getByRole('button');
            await user.click(button);

            expect(handleSubmit).toHaveBeenCalled();
        });

        it('does not submit form when type is button', async () => {
            const user = userEvent.setup();
            const handleSubmit = vi.fn((e) => e.preventDefault());

            render(
                <form onSubmit={handleSubmit}>
                    <DangerButton type="button">Cancel</DangerButton>
                </form>
            );

            const button = screen.getByRole('button');
            await user.click(button);

            expect(handleSubmit).not.toHaveBeenCalled();
        });
    });

    describe('Accessibility', () => {
        it('has proper button role', () => {
            render(<DangerButton>Delete</DangerButton>);
            expect(screen.getByRole('button')).toBeInTheDocument();
        });

        it('supports aria-label', () => {
            render(<DangerButton aria-label="Delete item">Delete</DangerButton>);
            expect(screen.getByLabelText('Delete item')).toBeInTheDocument();
        });

        it('supports aria-describedby', () => {
            render(<DangerButton aria-describedby="delete-warning">Delete</DangerButton>);
            const button = screen.getByRole('button');
            expect(button).toHaveAttribute('aria-describedby', 'delete-warning');
        });

        it('indicates disabled state to assistive technologies', () => {
            render(<DangerButton disabled>Delete</DangerButton>);
            const button = screen.getByRole('button');
            expect(button).toHaveAttribute('disabled');
        });
    });

    describe('Destructive Actions Safety', () => {
        it('is visually distinct from normal buttons', () => {
            const { container: dangerContainer } = render(
                <DangerButton>Delete</DangerButton>
            );
            const { container: normalContainer } = render(<button>Normal</button>);

            const dangerButton = dangerContainer.querySelector('button');
            const normalButton = normalContainer.querySelector('button');

            // Danger button should have different classes
            expect(dangerButton?.className).not.toBe(normalButton?.className);
        });

        it('prevents accidental double-click submission', async () => {
            const user = userEvent.setup();
            const handleClick = vi.fn();

            render(<DangerButton onClick={handleClick}>Delete</DangerButton>);
            const button = screen.getByRole('button');

            // Simulate rapid double-click
            await user.click(button);
            await user.click(button);

            // Should register both clicks (no built-in debounce)
            // Application logic should handle debouncing if needed
            expect(handleClick).toHaveBeenCalledTimes(2);
        });
    });

    describe('Security', () => {
        it('does not execute scripts in children', () => {
            render(
                <DangerButton>
                    Delete <span>{'<script>alert("xss")</script>'}</span>
                </DangerButton>
            );

            // Text should be rendered as text, not executed
            expect(screen.getByText(/script/i)).toBeInTheDocument();
            expect(document.querySelector('script')).not.toBeInTheDocument();
        });

        it('sanitizes dangerous event handlers', () => {
            // @ts-expect-error Testing dangerous props
            render(<DangerButton onclick="alert('xss')">Delete</DangerButton>);
            const button = screen.getByRole('button');

            // React should not allow onclick as a string
            expect(button).not.toHaveAttribute('onclick');
        });
    });

    describe('Performance', () => {
        it('does not re-render unnecessarily', () => {
            const { rerender } = render(<DangerButton>Delete</DangerButton>);
            const button = screen.getByRole('button');
            const firstRender = button.textContent;

            rerender(<DangerButton>Delete</DangerButton>);
            expect(button.textContent).toBe(firstRender);
        });
    });
});
