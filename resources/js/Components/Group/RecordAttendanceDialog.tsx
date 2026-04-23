import React, { useState } from 'react';
import axios from 'axios';
import { toast } from 'sonner';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import type { VisitorVisit } from '@/Types/visitor';

interface Props {
    groupUuid: string;
    visitors: VisitorVisit[];
    meetings: Array<{
        uuid: string;
        appointment: { title: string; id: number } | null;
    }>;
    activities: Array<{
        uuid: string;
        title: string;
        id?: number;
    }>;
    onClose: () => void;
    onRecorded: () => void;
}

type AttendanceStatus = 'present' | 'absent' | 'excused' | 'late';

const statusOptions: { value: AttendanceStatus; label: string }[] = [
    { value: 'present', label: 'Présent' },
    { value: 'absent', label: 'Absent' },
    { value: 'excused', label: 'Excusé' },
    { value: 'late', label: 'En retard' },
];

export default function RecordAttendanceDialog({
    groupUuid,
    visitors,
    meetings,
    activities,
    onClose,
    onRecorded,
}: Props) {
    const [processing, setProcessing] = useState(false);
    const [attendableType, setAttendableType] = useState('');
    const [attendableId, setAttendableId] = useState('');
    const [attendedAt, setAttendedAt] = useState(new Date().toISOString().split('T')[0]);
    const [attendances, setAttendances] = useState<
        Record<number, { status: AttendanceStatus }>
    >(() => {
        const initial: Record<number, { status: AttendanceStatus }> = {};
        visitors.forEach((v) => {
            if (v.integration_status !== 'integrated') {
                initial[v.visitor.id] = { status: 'present' };
            }
        });
        return initial;
    });

    const activeVisitors = visitors.filter((v) => v.integration_status !== 'integrated');

    const toggleStatus = (visitorId: number) => {
        setAttendances((prev) => {
            const current = prev[visitorId]?.status || 'present';
            const order: AttendanceStatus[] = ['present', 'absent', 'excused', 'late'];
            const nextIndex = (order.indexOf(current) + 1) % order.length;
            return { ...prev, [visitorId]: { status: order[nextIndex] } };
        });
    };

    const setAllStatus = (status: AttendanceStatus) => {
        setAttendances((prev) => {
            const updated = { ...prev };
            Object.keys(updated).forEach((key) => {
                updated[Number(key)] = { status };
            });
            return updated;
        });
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!attendableType || !attendableId) {
            toast.error('Veuillez sélectionner une réunion ou activité.');
            return;
        }

        setProcessing(true);

        try {
            await axios.post(`/groups/${groupUuid}/visitors/attendance`, {
                attendable_type: attendableType,
                attendable_id: Number(attendableId),
                attended_at: attendedAt,
                attendances: Object.entries(attendances).map(([visitorId, data]) => ({
                    visitor_id: Number(visitorId),
                    status: data.status,
                })),
            });
            toast.success('Présences enregistrées avec succès.');
            onRecorded();
        } catch {
            toast.error('Erreur lors de l\'enregistrement des présences.');
        } finally {
            setProcessing(false);
        }
    };

    const statusColorMap: Record<AttendanceStatus, string> = {
        present: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        absent: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        excused: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        late: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
    };

    return (
        <Dialog open onOpenChange={onClose}>
            <DialogContent className="max-w-lg max-h-[80vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Prendre les présences</DialogTitle>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4 px-6 py-4">
                    <div>
                        <Label>Type</Label>
                        <Select value={attendableType} onValueChange={(v) => { setAttendableType(v); setAttendableId(''); }}>
                            <SelectTrigger>
                                <SelectValue placeholder="Réunion ou Activité" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="App\Models\GroupMeeting">Réunion</SelectItem>
                                <SelectItem value="App\Models\GroupActivity">Activité</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    {attendableType === 'App\\Models\\GroupMeeting' && (
                        <div>
                            <Label>Réunion</Label>
                            <Select value={attendableId} onValueChange={setAttendableId}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Sélectionner une réunion" />
                                </SelectTrigger>
                                <SelectContent>
                                    {meetings.map((m) => (
                                        <SelectItem key={m.uuid} value={String(m.appointment?.id || 0)}>
                                            {m.appointment?.title || 'Réunion'}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}

                    {attendableType === 'App\\Models\\GroupActivity' && (
                        <div>
                            <Label>Activité</Label>
                            <Select value={attendableId} onValueChange={setAttendableId}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Sélectionner une activité" />
                                </SelectTrigger>
                                <SelectContent>
                                    {activities.map((a) => (
                                        <SelectItem key={a.uuid} value={String(a.id || 0)}>
                                            {a.title}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}

                    <div>
                        <Label>Date</Label>
                        <Input
                            type="date"
                            value={attendedAt}
                            onChange={(e) => setAttendedAt(e.target.value)}
                            required
                        />
                    </div>

                    {/* Quick actions */}
                    <div className="flex gap-2">
                        <Button type="button" variant="outline" size="sm" onClick={() => setAllStatus('present')}>
                            Tous présents
                        </Button>
                        <Button type="button" variant="outline" size="sm" onClick={() => setAllStatus('absent')}>
                            Tous absents
                        </Button>
                    </div>

                    {/* Visitor list */}
                    <div className="space-y-2 max-h-60 overflow-y-auto">
                        {activeVisitors.length === 0 ? (
                            <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                                Aucun visiteur actif.
                            </p>
                        ) : (
                            activeVisitors.map((visit) => (
                                <div
                                    key={visit.visitor.id}
                                    className="flex items-center justify-between p-2 rounded-lg border dark:border-gray-700"
                                >
                                    <div className="flex items-center gap-2">
                                        <div className="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center">
                                            <span className="text-xs font-medium text-purple-700 dark:text-purple-300">
                                                {visit.visitor.first_name[0]}{visit.visitor.last_name[0]}
                                            </span>
                                        </div>
                                        <span className="text-sm font-medium text-gray-900 dark:text-white">
                                            {visit.visitor.name}
                                        </span>
                                    </div>
                                    <button
                                        type="button"
                                        className={`px-3 py-1 text-xs font-medium rounded-full cursor-pointer transition-colors ${statusColorMap[attendances[visit.visitor.id]?.status || 'present']}`}
                                        onClick={() => toggleStatus(visit.visitor.id)}
                                    >
                                        {statusOptions.find((s) => s.value === (attendances[visit.visitor.id]?.status || 'present'))?.label}
                                    </button>
                                </div>
                            ))
                        )}
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose}>
                            Annuler
                        </Button>
                        <Button type="submit" disabled={processing || !attendableType || !attendableId}>
                            {processing ? 'Enregistrement...' : 'Enregistrer'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
