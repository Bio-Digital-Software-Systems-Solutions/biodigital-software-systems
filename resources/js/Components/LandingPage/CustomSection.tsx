import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { resolveDesign, type DesignSettings } from '@/lib/sectionDesign';

export interface CustomSubsection {
    id: number;
    uuid: string;
    block_type: 'heading' | 'paragraph' | 'image' | 'button' | 'card';
    content: Record<string, unknown>;
    design_settings: DesignSettings | null;
    order: number;
    is_active: boolean;
}

export interface CustomSectionData {
    id: number;
    uuid: string;
    key: string;
    type: 'custom';
    title: string | null;
    content: {
        badge?: string;
        heading?: string;
        subtitle?: string;
    } | null;
    design_settings: DesignSettings | null;
    subsections?: CustomSubsection[];
}

interface CustomSectionProps {
    section: CustomSectionData;
}

function Block({ block }: { block: CustomSubsection }) {
    const design = resolveDesign(block.design_settings);
    const c = block.content as Record<string, unknown>;

    switch (block.block_type) {
        case 'heading': {
            const text = typeof c.text === 'string' ? c.text : '';
            const level = (typeof c.level === 'number' ? c.level : 2) as 1 | 2 | 3;
            const className = `font-bold text-gray-900 dark:text-white ${design.headingClass || 'text-3xl'} ${design.alignmentClass}`;
            if (level === 1) {
                return <h1 className={className} style={design.sectionStyle}>{text}</h1>;
            }
            if (level === 3) {
                return <h3 className={className} style={design.sectionStyle}>{text}</h3>;
            }
            return <h2 className={className} style={design.sectionStyle}>{text}</h2>;
        }
        case 'paragraph': {
            const text = typeof c.text === 'string' ? c.text : '';
            return (
                <p
                    className={`text-gray-600 dark:text-gray-400 leading-relaxed ${design.paragraphClass || 'text-base'} ${design.alignmentClass}`}
                    style={design.sectionStyle}
                >
                    {text}
                </p>
            );
        }
        case 'image': {
            const url = typeof c.url === 'string' ? c.url : '';
            const alt = typeof c.alt === 'string' ? c.alt : '';
            const caption = typeof c.caption === 'string' ? c.caption : '';
            return (
                <figure className={design.alignmentClass || 'text-center'}>
                    <img src={url} alt={alt} className="max-w-full h-auto rounded-lg mx-auto" style={design.sectionStyle} />
                    {caption ? <figcaption className="text-sm text-muted-foreground mt-2">{caption}</figcaption> : null}
                </figure>
            );
        }
        case 'button': {
            const label = typeof c.label === 'string' ? c.label : '';
            const href = typeof c.href === 'string' ? c.href : '#';
            const variant = (typeof c.variant === 'string' ? c.variant : 'default') as
                | 'default'
                | 'outline'
                | 'ghost'
                | 'secondary';
            return (
                <div className={design.alignmentClass || 'text-center'}>
                    <Button asChild variant={variant} style={design.sectionStyle}>
                        <a href={href}>{label}</a>
                    </Button>
                </div>
            );
        }
        case 'card': {
            const title = typeof c.title === 'string' ? c.title : '';
            const body = typeof c.body === 'string' ? c.body : '';
            return (
                <Card style={design.sectionStyle}>
                    <CardHeader>
                        <CardTitle>{title}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-muted-foreground">{body}</p>
                    </CardContent>
                </Card>
            );
        }
        default:
            return null;
    }
}

export default function CustomSection({ section }: CustomSectionProps) {
    const design = resolveDesign(section.design_settings);
    const badge = section.content?.badge;
    const heading = section.content?.heading;
    const subtitle = section.content?.subtitle;
    const subsections = (section.subsections ?? []).filter((b) => b.is_active);

    return (
        <section
            id={section.key}
            className={`px-6 md:px-12 ${design.sectionClass} ${design.hasPadding ? '' : 'py-16'}`}
            style={design.sectionStyle}
        >
            <div className="max-w-7xl mx-auto">
                {(badge || heading || subtitle) && (
                    <div className={`space-y-4 mb-12 ${design.alignmentClass || 'text-center'}`}>
                        {badge && (
                            <Badge variant="secondary" className="mb-2">
                                {badge}
                            </Badge>
                        )}
                        {heading && (
                            <h2 className={`font-bold tracking-tight ${design.headingClass || 'text-3xl sm:text-4xl md:text-5xl'}`}>
                                {heading}
                            </h2>
                        )}
                        {subtitle && (
                            <p className={`mx-auto max-w-2xl text-muted-foreground ${design.paragraphClass || 'text-lg'}`}>
                                {subtitle}
                            </p>
                        )}
                    </div>
                )}

                <div className={design.layoutClass || 'space-y-6'}>
                    {subsections.map((block) => (
                        <Block key={block.id} block={block} />
                    ))}
                </div>
            </div>
        </section>
    );
}
