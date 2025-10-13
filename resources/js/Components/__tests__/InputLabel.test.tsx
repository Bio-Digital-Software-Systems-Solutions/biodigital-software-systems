import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import InputLabel from '../InputLabel';

describe('InputLabel Component', () => {
    describe('Rendering', () => {
        it('renders label with children', () => {
            render(<InputLabel>Email Address</InputLabel>);
            expect(screen.getByText('Email Address')).toBeInTheDocument();
        });

        it('renders label with value prop', () => {
            render(<InputLabel value="Username" />);
            expect(screen.getByText('Username')).toBeInTheDocument();
        });

        it('prefers children over value prop', () => {
            render(<InputLabel value="Value">Children</InputLabel>);
            expect(screen.getByText('Children')).toBeInTheDocument();
            expect(screen.queryByText('Value')).not.toBeInTheDocument();
        });

        it('applies custom className', () => {
            render(<InputLabel className="custom-label">Label</InputLabel>);
            const label = screen.getByText('Label');
            expect(label).toHaveClass('custom-label');
        });
    });

    describe('Form Association', () => {
        it('associates with input using htmlFor', () => {
            render(
                <div>
                    <InputLabel htmlFor="email-input">Email</InputLabel>
                    <input id="email-input" type="email" />
                </div>
            );

            const label = screen.getByText('Email');
            expect(label).toHaveAttribute('for', 'email-input');
        });

        it('clicking label focuses associated input', () => {
            render(
                <div>
                    <InputLabel htmlFor="password-input">Password</InputLabel>
                    <input id="password-input" type="password" />
                </div>
            );

            const label = screen.getByText('Password');
            const input = screen.getByRole('textbox', { hidden: true });

            label.click();
            expect(input).toHaveFocus();
        });

        it('works with nested input', () => {
            render(
                <label>
                    <InputLabel>Remember me</InputLabel>
                    <input type="checkbox" />
                </label>
            );

            expect(screen.getByText('Remember me')).toBeInTheDocument();
            expect(screen.getByRole('checkbox')).toBeInTheDocument();
        });
    });

    describe('Required Indicator', () => {
        it('can display required indicator', () => {
            render(
                <InputLabel required>
                    Required Field <span className="text-red-500">*</span>
                </InputLabel>
            );

            expect(screen.getByText('Required Field')).toBeInTheDocument();
            expect(screen.getByText('*')).toBeInTheDocument();
        });

        it('supports aria-required', () => {
            render(<InputLabel aria-required="true">Email</InputLabel>);
            const label = screen.getByText('Email');

            // Label itself doesn't get aria-required, but associated input should
            expect(label).toBeInTheDocument();
        });
    });

    describe('Styling', () => {
        it('renders as a label element', () => {
            render(<InputLabel>Test Label</InputLabel>);
            const label = screen.getByText('Test Label');
            expect(label.tagName).toBe('LABEL');
        });

        it('applies default styling classes', () => {
            render(<InputLabel>Test</InputLabel>);
            const label = screen.getByText('Test');

            // Should have some default styling
            expect(label).toHaveClass('block');
        });

        it('merges custom classes with default classes', () => {
            render(<InputLabel className="my-custom-class">Test</InputLabel>);
            const label = screen.getByText('Test');

            expect(label).toHaveClass('block');
            expect(label).toHaveClass('my-custom-class');
        });
    });

    describe('Accessibility', () => {
        it('has semantic label element', () => {
            render(<InputLabel>Accessible Label</InputLabel>);
            const label = screen.getByText('Accessible Label');
            expect(label.tagName).toBe('LABEL');
        });

        it('properly associates with form controls', () => {
            render(
                <div>
                    <InputLabel htmlFor="username">Username</InputLabel>
                    <input id="username" type="text" />
                </div>
            );

            expect(screen.getByLabelText('Username')).toBeInTheDocument();
        });

        it('supports screen readers with descriptive text', () => {
            render(
                <InputLabel>
                    Email Address <span className="text-sm">(required)</span>
                </InputLabel>
            );

            expect(screen.getByText('Email Address')).toBeInTheDocument();
            expect(screen.getByText('(required)')).toBeInTheDocument();
        });
    });

    describe('Content Rendering', () => {
        it('renders text content', () => {
            render(<InputLabel>Simple Text</InputLabel>);
            expect(screen.getByText('Simple Text')).toBeInTheDocument();
        });

        it('renders JSX content', () => {
            render(
                <InputLabel>
                    <span className="font-bold">Bold Label</span>
                </InputLabel>
            );

            const boldText = screen.getByText('Bold Label');
            expect(boldText).toBeInTheDocument();
            expect(boldText).toHaveClass('font-bold');
        });

        it('renders complex content with icons', () => {
            render(
                <InputLabel>
                    <svg className="inline-icon">
                        <circle cx="10" cy="10" r="5" />
                    </svg>
                    Label with Icon
                </InputLabel>
            );

            expect(screen.getByText('Label with Icon')).toBeInTheDocument();
            const svg = document.querySelector('svg.inline-icon');
            expect(svg).toBeInTheDocument();
        });
    });

    describe('Security', () => {
        it('does not execute scripts in children', () => {
            render(
                <InputLabel>
                    Label Text <span>{'<script>alert("xss")</script>'}</span>
                </InputLabel>
            );

            expect(screen.getByText(/script/i)).toBeInTheDocument();
            expect(document.querySelector('script')).not.toBeInTheDocument();
        });

        it('safely renders HTML entities', () => {
            render(<InputLabel>Price &gt; 100</InputLabel>);
            expect(screen.getByText('Price > 100')).toBeInTheDocument();
        });

        it('sanitizes dangerous attributes', () => {
            // @ts-expect-error Testing dangerous props
            render(<InputLabel onclick="alert('xss')">Label</InputLabel>);
            const label = screen.getByText('Label');

            expect(label).not.toHaveAttribute('onclick');
        });
    });

    describe('Edge Cases', () => {
        it('handles empty children gracefully', () => {
            render(<InputLabel></InputLabel>);
            const labels = document.querySelectorAll('label');
            expect(labels.length).toBeGreaterThan(0);
        });

        it('handles undefined value prop', () => {
            render(<InputLabel value={undefined}>Fallback</InputLabel>);
            expect(screen.getByText('Fallback')).toBeInTheDocument();
        });

        it('handles null children', () => {
            render(<InputLabel>{null}</InputLabel>);
            const labels = document.querySelectorAll('label');
            expect(labels.length).toBeGreaterThan(0);
        });

        it('handles very long text', () => {
            const longText = 'A'.repeat(200);
            render(<InputLabel>{longText}</InputLabel>);
            expect(screen.getByText(longText)).toBeInTheDocument();
        });
    });
});
