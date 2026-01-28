import React, { useState, useEffect, useRef, useCallback } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import {
    QrCodeIcon,
    MagnifyingGlassIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    UserIcon,
    TicketIcon,
    ArrowPathIcon,
    PlayIcon,
    StopIcon,
    HashtagIcon,
    ExclamationTriangleIcon,
    ArrowUturnLeftIcon,
} from '@heroicons/react/24/outline';
import { useEventCheckIn } from '@/Hooks/useEventCheckIn';
import { EventRegistration, CheckInStats, EventCheckin } from '@/Types/event';

interface CheckInScannerProps {
    eventId: number | string;
    sessionId?: number;
    onCheckIn?: (checkin: EventCheckin) => void;
}

type CheckInMode = 'qr' | 'search' | 'number';

interface CheckInFeedback {
    success: boolean;
    message: string;
    registration?: EventRegistration;
}

export const CheckInScanner: React.FC<CheckInScannerProps> = ({
    eventId,
    sessionId,
    onCheckIn,
}) => {
    const { t } = useTranslation();
    const [mode, setMode] = useState<CheckInMode>('search');
    const [qrInput, setQrInput] = useState('');
    const [numberInput, setNumberInput] = useState('');
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState<EventRegistration[]>([]);
    const [isSearching, setIsSearching] = useState(false);
    const [feedback, setFeedback] = useState<CheckInFeedback | null>(null);
    const [isPolling, setIsPolling] = useState(false);

    const qrInputRef = useRef<HTMLInputElement>(null);
    const searchInputRef = useRef<HTMLInputElement>(null);
    const numberInputRef = useRef<HTMLInputElement>(null);

    const {
        stats,
        recentCheckins,
        loading,
        checkInByQR,
        checkInByNumber,
        checkInManual,
        undoCheckIn,
        searchAttendees,
        startPolling,
        stopPolling,
        refetch,
    } = useEventCheckIn({
        eventId,
        autoFetch: true,
        pollInterval: isPolling ? 3000 : undefined,
    });

    // Focus input based on mode
    useEffect(() => {
        const timeout = setTimeout(() => {
            if (mode === 'qr' && qrInputRef.current) {
                qrInputRef.current.focus();
            } else if (mode === 'search' && searchInputRef.current) {
                searchInputRef.current.focus();
            } else if (mode === 'number' && numberInputRef.current) {
                numberInputRef.current.focus();
            }
        }, 100);
        return () => clearTimeout(timeout);
    }, [mode]);

    // Clear feedback after delay
    useEffect(() => {
        if (feedback) {
            const timeout = setTimeout(() => setFeedback(null), 5000);
            return () => clearTimeout(timeout);
        }
    }, [feedback]);

    // Handle QR code scan (from barcode scanner)
    const handleQRSubmit = useCallback(async (e: React.FormEvent) => {
        e.preventDefault();
        if (!qrInput.trim()) return;

        const result = await checkInByQR(qrInput.trim(), sessionId);
        setFeedback({
            success: result.success,
            message: result.message,
            registration: result.registration,
        });

        if (result.success) {
            toast.success(result.message);
            if (result.checkin && onCheckIn) {
                onCheckIn(result.checkin);
            }
        } else {
            toast.error(result.message);
        }

        setQrInput('');
        qrInputRef.current?.focus();
    }, [qrInput, sessionId, checkInByQR, onCheckIn]);

    // Handle registration number check-in
    const handleNumberSubmit = useCallback(async (e: React.FormEvent) => {
        e.preventDefault();
        if (!numberInput.trim()) return;

        const result = await checkInByNumber(numberInput.trim(), sessionId);
        setFeedback({
            success: result.success,
            message: result.message,
            registration: result.registration,
        });

        if (result.success) {
            toast.success(result.message);
            if (result.checkin && onCheckIn) {
                onCheckIn(result.checkin);
            }
        } else {
            toast.error(result.message);
        }

        setNumberInput('');
        numberInputRef.current?.focus();
    }, [numberInput, sessionId, checkInByNumber, onCheckIn]);

    // Handle search
    const handleSearch = useCallback(async (query: string) => {
        setSearchQuery(query);
        if (query.length < 2) {
            setSearchResults([]);
            return;
        }

        setIsSearching(true);
        try {
            const results = await searchAttendees(query);
            setSearchResults(results);
        } finally {
            setIsSearching(false);
        }
    }, [searchAttendees]);

    // Handle manual check-in from search results
    const handleManualCheckIn = useCallback(async (registration: EventRegistration) => {
        const result = await checkInManual(registration.id, sessionId);
        setFeedback({
            success: result.success,
            message: result.message,
            registration: result.registration,
        });

        if (result.success) {
            toast.success(result.message);
            if (result.checkin && onCheckIn) {
                onCheckIn(result.checkin);
            }
            // Remove from search results
            setSearchResults(prev => prev.filter(r => r.id !== registration.id));
        } else {
            toast.error(result.message);
        }
    }, [sessionId, checkInManual, onCheckIn]);

    // Handle undo check-in
    const handleUndoCheckIn = useCallback(async (checkinId: number) => {
        try {
            await undoCheckIn(checkinId);
            toast.success(t('events.checkin.undo_success', 'Check-in annulé'));
        } catch {
            toast.error(t('events.checkin.undo_error', 'Erreur lors de l\'annulation'));
        }
    }, [undoCheckIn, t]);

    // Toggle live polling
    const togglePolling = useCallback(() => {
        if (isPolling) {
            stopPolling();
            setIsPolling(false);
        } else {
            startPolling();
            setIsPolling(true);
        }
    }, [isPolling, startPolling, stopPolling]);

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'confirmed':
                return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
            case 'pending':
                return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
            case 'checked_in':
                return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
            case 'cancelled':
                return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
            default:
                return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
        }
    };

    return (
        <div className="space-y-6">
            {/* Stats Cards */}
            {stats && (
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                <UserIcon className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    {t('events.checkin.expected', 'Attendus')}
                                </p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">
                                    {stats.total_expected}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                <CheckCircleIcon className="h-5 w-5 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    {t('events.checkin.checked_in', 'Enregistrés')}
                                </p>
                                <p className="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {stats.checked_in}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                                <ClockIcon className="h-5 w-5 text-orange-600 dark:text-orange-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    {t('events.checkin.remaining', 'Restants')}
                                </p>
                                <p className="text-2xl font-bold text-orange-600 dark:text-orange-400">
                                    {stats.not_checked_in}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                                <TicketIcon className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    {t('events.checkin.rate', 'Taux')}
                                </p>
                                <p className="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                    {(stats.attendance_rate ?? 0).toFixed(1)}%
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Feedback Alert */}
            {feedback && (
                <div
                    className={`p-4 rounded-lg flex items-center gap-3 ${
                        feedback.success
                            ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200'
                            : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200'
                    }`}
                >
                    {feedback.success ? (
                        <CheckCircleIcon className="h-6 w-6 flex-shrink-0" />
                    ) : (
                        <XCircleIcon className="h-6 w-6 flex-shrink-0" />
                    )}
                    <div className="flex-1">
                        <p className="font-medium">{feedback.message}</p>
                        {feedback.registration && (
                            <p className="text-sm opacity-75">
                                {feedback.registration.full_name || `${feedback.registration.first_name} ${feedback.registration.last_name}`}
                                {feedback.registration.company && ` - ${feedback.registration.company}`}
                            </p>
                        )}
                    </div>
                </div>
            )}

            {/* Mode Selector & Actions */}
            <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                <div className="flex bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                    <button
                        onClick={() => setMode('search')}
                        className={`px-4 py-2 rounded-md text-sm font-medium transition-colors flex items-center gap-2 ${
                            mode === 'search'
                                ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                        }`}
                    >
                        <MagnifyingGlassIcon className="h-4 w-4" />
                        {t('events.checkin.search', 'Recherche')}
                    </button>
                    <button
                        onClick={() => setMode('qr')}
                        className={`px-4 py-2 rounded-md text-sm font-medium transition-colors flex items-center gap-2 ${
                            mode === 'qr'
                                ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                        }`}
                    >
                        <QrCodeIcon className="h-4 w-4" />
                        {t('events.checkin.qr_code', 'QR Code')}
                    </button>
                    <button
                        onClick={() => setMode('number')}
                        className={`px-4 py-2 rounded-md text-sm font-medium transition-colors flex items-center gap-2 ${
                            mode === 'number'
                                ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                        }`}
                    >
                        <HashtagIcon className="h-4 w-4" />
                        {t('events.checkin.number', 'Numéro')}
                    </button>
                </div>

                <div className="flex gap-2">
                    <button
                        onClick={togglePolling}
                        className={`px-3 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors ${
                            isPolling
                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'
                        }`}
                    >
                        {isPolling ? (
                            <>
                                <StopIcon className="h-4 w-4" />
                                {t('events.checkin.stop_live', 'Arrêter live')}
                            </>
                        ) : (
                            <>
                                <PlayIcon className="h-4 w-4" />
                                {t('events.checkin.start_live', 'Démarrer live')}
                            </>
                        )}
                    </button>
                    <button
                        onClick={refetch}
                        disabled={loading}
                        className="px-3 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium flex items-center gap-2 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
                    >
                        <ArrowPathIcon className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
                        {t('common.refresh', 'Actualiser')}
                    </button>
                </div>
            </div>

            {/* Input Modes */}
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                {/* QR Code Mode */}
                {mode === 'qr' && (
                    <div className="space-y-4">
                        <div className="text-center mb-4">
                            <QrCodeIcon className="h-12 w-12 text-gray-400 mx-auto mb-2" />
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                {t('events.checkin.qr_instruction', 'Scannez le QR code ou saisissez-le manuellement')}
                            </p>
                        </div>
                        <form onSubmit={handleQRSubmit}>
                            <div className="flex gap-2">
                                <input
                                    ref={qrInputRef}
                                    type="text"
                                    value={qrInput}
                                    onChange={(e) => setQrInput(e.target.value)}
                                    placeholder={t('events.checkin.qr_placeholder', 'Code QR...')}
                                    className="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    autoComplete="off"
                                    autoFocus
                                />
                                <button
                                    type="submit"
                                    disabled={!qrInput.trim()}
                                    className="px-6 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 disabled:bg-gray-300 dark:disabled:bg-gray-600 disabled:cursor-not-allowed transition-colors"
                                >
                                    <CheckCircleIcon className="h-6 w-6" />
                                </button>
                            </div>
                        </form>
                    </div>
                )}

                {/* Registration Number Mode */}
                {mode === 'number' && (
                    <div className="space-y-4">
                        <div className="text-center mb-4">
                            <HashtagIcon className="h-12 w-12 text-gray-400 mx-auto mb-2" />
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                {t('events.checkin.number_instruction', 'Entrez le numéro d\'inscription')}
                            </p>
                        </div>
                        <form onSubmit={handleNumberSubmit}>
                            <div className="flex gap-2">
                                <input
                                    ref={numberInputRef}
                                    type="text"
                                    value={numberInput}
                                    onChange={(e) => setNumberInput(e.target.value.toUpperCase())}
                                    placeholder={t('events.checkin.number_placeholder', 'REG-XXXXXX')}
                                    className="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-lg font-mono bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 uppercase"
                                    autoComplete="off"
                                    autoFocus
                                />
                                <button
                                    type="submit"
                                    disabled={!numberInput.trim()}
                                    className="px-6 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 disabled:bg-gray-300 dark:disabled:bg-gray-600 disabled:cursor-not-allowed transition-colors"
                                >
                                    <CheckCircleIcon className="h-6 w-6" />
                                </button>
                            </div>
                        </form>
                    </div>
                )}

                {/* Search Mode */}
                {mode === 'search' && (
                    <div className="space-y-4">
                        <div className="relative">
                            <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <input
                                ref={searchInputRef}
                                type="text"
                                value={searchQuery}
                                onChange={(e) => handleSearch(e.target.value)}
                                placeholder={t('events.checkin.search_placeholder', 'Rechercher par nom, email ou entreprise...')}
                                className="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                autoComplete="off"
                                autoFocus
                            />
                            {isSearching && (
                                <ArrowPathIcon className="absolute right-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 animate-spin" />
                            )}
                        </div>

                        {/* Search Results */}
                        {searchResults.length > 0 && (
                            <div className="border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
                                {searchResults.map((registration) => (
                                    <div
                                        key={registration.id}
                                        className="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                    >
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2">
                                                <span className="font-medium text-gray-900 dark:text-white">
                                                    {registration.full_name || `${registration.first_name} ${registration.last_name}`}
                                                </span>
                                                <span className={`px-2 py-0.5 text-xs font-medium rounded-full ${getStatusColor(registration.status)}`}>
                                                    {registration.status}
                                                </span>
                                            </div>
                                            <div className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                <span>{registration.email}</span>
                                                {registration.company && (
                                                    <span className="ml-2">• {registration.company}</span>
                                                )}
                                            </div>
                                            <div className="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                                {registration.registration_number}
                                                {registration.ticket && (
                                                    <span className="ml-2">• {registration.ticket.name}</span>
                                                )}
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            {registration.is_checked_in ? (
                                                <span className="flex items-center gap-1 text-green-600 dark:text-green-400 text-sm">
                                                    <CheckCircleIcon className="h-5 w-5" />
                                                    {t('events.checkin.already_checked_in', 'Déjà enregistré')}
                                                </span>
                                            ) : registration.status === 'confirmed' ? (
                                                <button
                                                    onClick={() => handleManualCheckIn(registration)}
                                                    className="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors flex items-center gap-2"
                                                >
                                                    <CheckCircleIcon className="h-4 w-4" />
                                                    {t('events.checkin.check_in', 'Check-in')}
                                                </button>
                                            ) : (
                                                <span className="flex items-center gap-1 text-yellow-600 dark:text-yellow-400 text-sm">
                                                    <ExclamationTriangleIcon className="h-5 w-5" />
                                                    {t('events.checkin.not_confirmed', 'Non confirmé')}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {searchQuery.length >= 2 && searchResults.length === 0 && !isSearching && (
                            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                <UserIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                                <p>{t('events.checkin.no_results', 'Aucun résultat trouvé')}</p>
                            </div>
                        )}
                    </div>
                )}
            </div>

            {/* Recent Check-ins */}
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <ClockIcon className="h-5 w-5 text-gray-500" />
                        {t('events.checkin.recent', 'Check-ins récents')}
                    </h3>
                    {isPolling && (
                        <span className="flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                            <span className="relative flex h-2 w-2">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span className="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                            Live
                        </span>
                    )}
                </div>
                <div className="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
                    {recentCheckins.length === 0 ? (
                        <div className="p-8 text-center text-gray-500 dark:text-gray-400">
                            <CheckCircleIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                            <p>{t('events.checkin.no_recent', 'Aucun check-in récent')}</p>
                        </div>
                    ) : (
                        recentCheckins.map((checkin) => (
                            <div
                                key={checkin.id}
                                className="px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            >
                                <div className="flex items-center gap-3">
                                    <div className="p-2 bg-green-100 dark:bg-green-900/30 rounded-full">
                                        <CheckCircleIcon className="h-5 w-5 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div>
                                        <p className="font-medium text-gray-900 dark:text-white">
                                            {checkin.registration?.full_name ||
                                             `${checkin.registration?.first_name} ${checkin.registration?.last_name}`}
                                        </p>
                                        <div className="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                            <span>{checkin.registration?.registration_number}</span>
                                            {checkin.registration?.company && (
                                                <span>• {checkin.registration.company}</span>
                                            )}
                                        </div>
                                    </div>
                                </div>
                                <div className="flex items-center gap-3">
                                    <div className="text-right">
                                        <p className="text-sm text-gray-500 dark:text-gray-400">
                                            {new Date(checkin.checked_in_at).toLocaleTimeString('fr-FR', {
                                                hour: '2-digit',
                                                minute: '2-digit',
                                            })}
                                        </p>
                                        <p className="text-xs text-gray-400 dark:text-gray-500">
                                            {checkin.method}
                                        </p>
                                    </div>
                                    <button
                                        onClick={() => handleUndoCheckIn(checkin.id)}
                                        className="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                                        title={t('events.checkin.undo', 'Annuler')}
                                    >
                                        <ArrowUturnLeftIcon className="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </div>
        </div>
    );
};

export default CheckInScanner;
