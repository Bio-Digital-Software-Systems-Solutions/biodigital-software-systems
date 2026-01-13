import InputError from '@/Components/InputError';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { Button } from '@/Components/ui/button';
import { Shield, Smartphone } from 'lucide-react';

export default function TwoFactorChallenge() {
    const [recovery, setRecovery] = useState(false);

    const form = useForm({
        code: '',
        recovery_code: '',
    });

    const toggleRecovery: FormEventHandler = (e) => {
        e.preventDefault();
        const isRecovery = !recovery;
        setRecovery(isRecovery);
        form.setData({
            code: '',
            recovery_code: '',
        });
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route('two-factor.login'));
    };

    return (
