import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { SunIcon, MoonIcon, ComputerDesktopIcon, ChevronDownIcon } from '@heroicons/react/24/outline';

type Theme = 'light' | 'dark' | 'system';

const themes = [
    { value: 'light' as Theme, icon: SunIcon, labelKey: 'theme.light' },
    { value: 'dark' as Theme, icon: MoonIcon, labelKey: 'theme.dark' },
    { value: 'system' as Theme, icon: ComputerDesktopIcon, labelKey: 'theme.system' },
];

export default function ThemeSwitcher() {
    const { t } = useTranslation();
    const [currentTheme, setCurrentTheme] = useState<Theme>('system');
    const [isOpen, setIsOpen] = useState(false);

    useEffect(() => {
        // Get theme from localStorage or default to system
        const savedTheme = localStorage.getItem('aig-app-theme') as Theme;
        if (savedTheme && themes.find(theme => theme.value === savedTheme)) {
            setCurrentTheme(savedTheme);
        } else {
            setCurrentTheme('system');
        }
    }, []);

    useEffect(() => {
        const applyTheme = (theme: Theme) => {
            const root = document.documentElement;
            
            if (theme === 'system') {
                // Use system preference
                const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                root.classList.toggle('dark', systemTheme === 'dark');
            } else {
                root.classList.toggle('dark', theme === 'dark');
            }
        };

        applyTheme(currentTheme);

        // Listen for system theme changes if current theme is system
        if (currentTheme === 'system') {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            const handleChange = () => applyTheme('system');
            
            mediaQuery.addEventListener('change', handleChange);
            return () => mediaQuery.removeEventListener('change', handleChange);
        }
    }, [currentTheme]);

    const handleThemeChange = (theme: Theme) => {
        setCurrentTheme(theme);
        setIsOpen(false);
        localStorage.setItem('aig-app-theme', theme);
    };

    const currentThemeConfig = themes.find(theme => theme.value === currentTheme) || themes[0];
    const IconComponent = currentThemeConfig.icon;

    return (
        <div className="relative">
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="flex items-center space-x-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 transition duration-200"
                aria-label={t(currentThemeConfig.labelKey)}
            >
                <IconComponent className="h-5 w-5" />
                <span className="hidden sm:block">{t(currentThemeConfig.labelKey)}</span>
                <ChevronDownIcon className={`h-4 w-4 transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`} />
            </button>

            {isOpen && (
                <>
                    {/* Backdrop */}
                    <div
                        className="fixed inset-0 z-10"
                        onClick={() => setIsOpen(false)}
                    />
                    
                    {/* Dropdown */}
                    <div className="absolute right-0 mt-2 w-40 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-20">
                        <div className="py-1">
                            {themes.map((theme) => {
                                const IconComponent = theme.icon;
                                return (
                                    <button
                                        key={theme.value}
                                        onClick={() => handleThemeChange(theme.value)}
                                        className={`flex items-center space-x-3 w-full px-4 py-2 text-sm text-left transition duration-200 ${
                                            currentTheme === theme.value
                                                ? 'bg-blue-50 dark:bg-blue-900 text-primary dark:text-primary'
                                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                                        }`}
                                    >
                                        <IconComponent className="h-4 w-4" />
                                        <span className="flex-1">{t(theme.labelKey)}</span>
                                        {currentTheme === theme.value && (
                                            <div className="w-2 h-2 bg-primary rounded-full" />
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}