import React from 'react';

interface FeatureCardProps {
    icon: React.ReactNode;
    title: string;
    description: string;
    link?: string;
}

export default function FeatureCard({ icon, title, description, link }: FeatureCardProps) {
    const CardContent = () => (
        <>
            <div className="flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white mb-4">
                {icon}
            </div>
            <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-2">
                {title}
            </h3>
            <p className="text-base text-gray-500 dark:text-gray-300">
                {description}
            </p>
        </>
    );

    if (link) {
        return (
            <a
                href={link}
                className="relative block p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 group"
            >
                <CardContent />
                <div className="absolute inset-0 rounded-lg border-2 border-transparent group-hover:border-primary transition-colors duration-300"></div>
            </a>
        );
    }

    return (
        <div className="relative p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
            <CardContent />
        </div>
    );
}