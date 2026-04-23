import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';

interface Visitor {
    uuid: string;
    first_name: string;
    last_name: string;
    email: string | null;
    phone: string | null;
    gender: string | null;
    date_of_birth: string | null;
    address: string | null;
    city: string | null;
    country: string | null;
    source: string | null;
    first_visit_date: string;
    status: string;
    notes: string | null;
}

interface Props {
    visitor: Visitor;
}

export default function VisitorEdit({ visitor }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        first_name: visitor.first_name,
        last_name: visitor.last_name,
        email: visitor.email || '',
        phone: visitor.phone || '',
        gender: visitor.gender || '',
        date_of_birth: visitor.date_of_birth || '',
        address: visitor.address || '',
        city: visitor.city || '',
        country: visitor.country || '',
        source: visitor.source || '',
        first_visit_date: visitor.first_visit_date,
        status: visitor.status,
        notes: visitor.notes || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/visitors/${visitor.uuid}`);
    };

    return (
        <DashboardLayout>
            <Head title={`Modifier ${visitor.first_name} ${visitor.last_name}`} />
            <div className="max-w-2xl mx-auto px-4 sm:px-6 py-4 sm:py-6">
                <div className="flex items-center gap-4 mb-6">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={`/visitors/${visitor.uuid}`}>
                            <ArrowLeftIcon className="h-4 w-4 mr-2" />
                            Retour
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                        Modifier {visitor.first_name} {visitor.last_name}
                    </h1>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Informations du visiteur</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="first_name">Prénom *</Label>
                                    <Input id="first_name" value={data.first_name} onChange={(e) => setData('first_name', e.target.value)} required />
                                    {errors.first_name && <p className="text-xs text-red-500 mt-1">{errors.first_name}</p>}
                                </div>
                                <div>
                                    <Label htmlFor="last_name">Nom *</Label>
                                    <Input id="last_name" value={data.last_name} onChange={(e) => setData('last_name', e.target.value)} required />
                                    {errors.last_name && <p className="text-xs text-red-500 mt-1">{errors.last_name}</p>}
                                </div>
                            </div>
                            <div>
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                                {errors.email && <p className="text-xs text-red-500 mt-1">{errors.email}</p>}
                            </div>
                            <div>
                                <Label htmlFor="phone">Téléphone</Label>
                                <Input id="phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                            </div>
                            <div>
                                <Label htmlFor="status">Statut</Label>
                                <Select value={data.status} onValueChange={(v) => setData('status', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">Actif</SelectItem>
                                        <SelectItem value="inactive">Inactif</SelectItem>
                                        <SelectItem value="integrated">Intégré</SelectItem>
                                        <SelectItem value="archived">Archivé</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="first_visit_date">Date de première visite *</Label>
                                <Input id="first_visit_date" type="date" value={data.first_visit_date} onChange={(e) => setData('first_visit_date', e.target.value)} required />
                            </div>
                            <div>
                                <Label htmlFor="notes">Notes</Label>
                                <Textarea id="notes" value={data.notes} onChange={(e) => setData('notes', e.target.value)} rows={3} />
                            </div>
                            <div className="flex justify-end gap-3">
                                <Button variant="outline" asChild><Link href={`/visitors/${visitor.uuid}`}>Annuler</Link></Button>
                                <Button type="submit" disabled={processing}>{processing ? 'Enregistrement...' : 'Enregistrer'}</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </DashboardLayout>
    );
}
