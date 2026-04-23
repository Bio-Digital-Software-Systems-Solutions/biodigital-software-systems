import React, { useState } from 'react';
import axios from 'axios';
import { toast } from 'sonner';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';

interface Props {
    groupUuid: string;
    groupMembers: Array<{ id: number; uuid: string; name: string }>;
    onClose: () => void;
    onAdded: () => void;
}

export default function AddVisitorDialog({ groupUuid, groupMembers, onClose, onAdded }: Props) {
    const [mode, setMode] = useState<'new' | 'existing'>('new');
    const [processing, setProcessing] = useState(false);
    const [form, setForm] = useState({
        visitor_id: '',
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        source: '',
        first_visited_at: new Date().toISOString().split('T')[0],
        invited_by: '',
        notes: '',
    });
    const [errors, setErrors] = useState<Record<string, string[]>>({});

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        try {
            const data = mode === 'existing'
                ? { visitor_id: form.visitor_id, first_visited_at: form.first_visited_at, invited_by: form.invited_by || undefined, notes: form.notes || undefined }
                : { ...form, visitor_id: undefined, invited_by: form.invited_by || undefined, notes: form.notes || undefined };

            await axios.post(`/groups/${groupUuid}/visitors`, data);
            toast.success('Visiteur ajouté au groupe.');
            onAdded();
        } catch (error: unknown) {
            const axiosError = error as { response?: { data?: { errors?: Record<string, string[]>; message?: string } } };
            if (axiosError.response?.data?.errors) {
                setErrors(axiosError.response.data.errors);
            } else {
                toast.error(axiosError.response?.data?.message || 'Erreur lors de l\'ajout du visiteur.');
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <Dialog open onOpenChange={onClose}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>Ajouter un visiteur</DialogTitle>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4 px-6 py-4">
                    {/* Mode toggle */}
                    <div className="flex gap-2">
                        <Button
                            type="button"
                            variant={mode === 'new' ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setMode('new')}
                        >
                            Nouveau visiteur
                        </Button>
                        <Button
                            type="button"
                            variant={mode === 'existing' ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setMode('existing')}
                        >
                            Visiteur existant
                        </Button>
                    </div>

                    {mode === 'new' ? (
                        <>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label htmlFor="first_name">Prénom *</Label>
                                    <Input
                                        id="first_name"
                                        value={form.first_name}
                                        onChange={(e) => setForm({ ...form, first_name: e.target.value })}
                                        required
                                    />
                                    {errors.first_name && <p className="text-xs text-red-500 mt-1">{errors.first_name[0]}</p>}
                                </div>
                                <div>
                                    <Label htmlFor="last_name">Nom *</Label>
                                    <Input
                                        id="last_name"
                                        value={form.last_name}
                                        onChange={(e) => setForm({ ...form, last_name: e.target.value })}
                                        required
                                    />
                                    {errors.last_name && <p className="text-xs text-red-500 mt-1">{errors.last_name[0]}</p>}
                                </div>
                            </div>
                            <div>
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={form.email}
                                    onChange={(e) => setForm({ ...form, email: e.target.value })}
                                />
                                {errors.email && <p className="text-xs text-red-500 mt-1">{errors.email[0]}</p>}
                            </div>
                            <div>
                                <Label htmlFor="phone">Téléphone</Label>
                                <Input
                                    id="phone"
                                    value={form.phone}
                                    onChange={(e) => setForm({ ...form, phone: e.target.value })}
                                />
                            </div>
                            <div>
                                <Label htmlFor="source">Source</Label>
                                <Select value={form.source} onValueChange={(v) => setForm({ ...form, source: v })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Comment a-t-il connu le groupe ?" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="friend">Ami</SelectItem>
                                        <SelectItem value="online">En ligne</SelectItem>
                                        <SelectItem value="event">Événement</SelectItem>
                                        <SelectItem value="walk_in">Visite spontanée</SelectItem>
                                        <SelectItem value="other">Autre</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </>
                    ) : (
                        <div>
                            <Label htmlFor="visitor_id">Visiteur</Label>
                            <Input
                                id="visitor_id"
                                type="number"
                                value={form.visitor_id}
                                onChange={(e) => setForm({ ...form, visitor_id: e.target.value })}
                                placeholder="ID du visiteur"
                                required
                            />
                            {errors.visitor_id && <p className="text-xs text-red-500 mt-1">{errors.visitor_id[0]}</p>}
                        </div>
                    )}

                    <div>
                        <Label htmlFor="first_visited_at">Date de première visite *</Label>
                        <Input
                            id="first_visited_at"
                            type="date"
                            value={form.first_visited_at}
                            onChange={(e) => setForm({ ...form, first_visited_at: e.target.value })}
                            required
                        />
                    </div>

                    <div>
                        <Label htmlFor="invited_by">Invité par</Label>
                        <Select value={form.invited_by} onValueChange={(v) => setForm({ ...form, invited_by: v })}>
                            <SelectTrigger>
                                <SelectValue placeholder="Sélectionner un membre" />
                            </SelectTrigger>
                            <SelectContent>
                                {groupMembers.map((member) => (
                                    <SelectItem key={member.id} value={String(member.id)}>
                                        {member.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <Label htmlFor="notes">Notes</Label>
                        <Textarea
                            id="notes"
                            value={form.notes}
                            onChange={(e) => setForm({ ...form, notes: e.target.value })}
                            rows={2}
                        />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose}>
                            Annuler
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Ajout...' : 'Ajouter'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
