<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Display the settings page
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get user settings from user preferences or use defaults
        $settings = [
            'email_notifications' => $user->email_notifications ?? true,
            'sms_notifications' => $user->sms_notifications ?? false,
            'push_notifications' => $user->push_notifications ?? true,
            'newsletter' => $user->newsletter ?? false,
            'event_reminders' => $user->event_reminders ?? true,
            'training_updates' => $user->training_updates ?? true,
            'message_notifications' => $user->message_notifications ?? true,
        ];

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update user settings
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'newsletter' => 'boolean',
            'event_reminders' => 'boolean',
            'training_updates' => 'boolean',
            'message_notifications' => 'boolean',
        ]);

        $user = $request->user();

        // Update user settings
        $user->update($validated);

        return back()->with('message', 'Settings updated successfully.');
    }
}
