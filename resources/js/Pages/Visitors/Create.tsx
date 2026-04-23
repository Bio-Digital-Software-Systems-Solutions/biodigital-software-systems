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

export default function VisitorCreate() {
    const { data, setData, post, processing, errors } = useForm({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        gender: '',
        date_of_birth: '',
        address: '',
        city: '',
        country: '',
        source: '',
        first_visit_date: new Date().toISOString().split('T')[0],
        notes: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/visitors');
    };

    return (
        <DashboardLayout>
            <Head title="Nouveau visiteur" />
            <div className="max-w-2xl mx-auto px-4 sm:px-6 py-4 sm:py-6">
                <div className="flex items-center gap-4 mb-6">
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/visitors">
                            <ArrowLeftIcon className="h-4 w-4 mr-2" />
                            Retour
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Nouveau visiteur</h1>
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
                                <Label htmlFor="gender">Genre</Label>
                                <Select value={data.gender} onValueChange={(v) => setData('gender', v)}>
                                    <SelectTrigger><SelectValue placeholder="Sélectionner" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="male">Homme</SelectItem>
                                        <SelectItem value="female">Femme</SelectItem>
                                        <SelectItem value="other">Autre</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="date_of_birth">Date de naissance</Label>
                                <Input id="date_of_birth" type="date" value={data.date_of_birth} onChange={(e) => setData('date_of_birth', e.target.value)} />
                            </div>
                            <div>
                                <Label htmlFor="source">Source</Label>
                                <Select value={data.source} onValueChange={(v) => setData('source', v)}>
                                    <SelectTrigger><SelectValue placeholder="Comment a-t-il connu ?" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="friend">Ami</SelectItem>
                                        <SelectItem value="online">En ligne</SelectItem>
                                        <SelectItem value="event">Événement</SelectItem>
                                        <SelectItem value="walk_in">Visite spontanée</SelectItem>
                                        <SelectItem value="other">Autre</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="first_visit_date">Date de première visite *</Label>
                                <Input id="first_visit_date" type="date" value={data.first_visit_date} onChange={(e) => setData('first_visit_date', e.target.value)} required />
                                {errors.first_visit_date && <p className="text-xs text-red-500 mt-1">{errors.first_visit_date}</p>}
                            </div>
                            <div>
                                <Label htmlFor="notes">Notes</Label>
                                <Textarea id="notes" value={data.notes} onChange={(e) => setData('notes', e.target.value)} rows={3} />
                            </div>
                            <div className="flex justify-end gap-3">
                                <Button variant="outline" asChild><Link href="/visitors">Annuler</Link></Button>
                                <Button type="submit" disabled={processing}>{processing ? 'Création...' : 'Créer'}</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </DashboardLayout>
    );
}
