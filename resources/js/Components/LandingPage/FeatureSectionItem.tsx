import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { ForwardRefExoticComponent, SVGProps } from 'react';

interface FeatureSectionItemProps {
    icon: ForwardRefExoticComponent<SVGProps<SVGSVGElement>>;
    iconColor: string;
    title: string;
    description: string;
}

export default function FeatureSectionItem({ icon: Icon, iconColor, title, description }: FeatureSectionItemProps) {
    return (
        <Card className="group hover:shadow-lg transition-all duration-300 hover:scale-105">
            <CardHeader>
                <div className={`mb-3 flex h-12 w-12 items-center justify-center rounded-lg ${iconColor}`}>
                    <Icon className="h-6 w-6" />
                </div>
                <CardTitle>{title}</CardTitle>
            </CardHeader>
            <CardContent>
                <CardDescription>{description}</CardDescription>
            </CardContent>
        </Card>
    );
}
