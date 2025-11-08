<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pastoral Care System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the pastoral care
    | appointment booking system. These settings control various aspects
    | of the booking system, notifications, and business rules.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | General Settings
    |--------------------------------------------------------------------------
    */

    'enabled' => env('PASTORAL_CARE_ENABLED', true),

    'church_name' => env('CHURCH_NAME', 'ICC Munich'),
    'church_email' => env('CHURCH_EMAIL', 'info@icc-munich.de'),
    'church_phone' => env('CHURCH_PHONE', '+49 89 123456789'),
    'church_website' => env('CHURCH_WEBSITE', 'https://icc-munich.de'),

    /*
    |--------------------------------------------------------------------------
    | Appointment Settings
    |--------------------------------------------------------------------------
    */

    'appointments' => [
        // Default appointment duration in minutes
        'default_duration' => 60,

        // Available duration options in minutes
        'duration_options' => [30, 45, 60, 90, 120],

        // Maximum duration allowed in minutes
        'max_duration' => 180,

        // Minimum duration allowed in minutes
        'min_duration' => 30,

        // Time slot interval in minutes (how often slots are available)
        'time_slot_interval' => 30,

        // Maximum appointments per user per month
        'max_appointments_per_user_per_month' => 4,

        // Maximum appointments per user per week
        'max_appointments_per_user_per_week' => 1,

        // Days in advance appointments can be booked
        'max_days_in_advance' => 60,

        // Minimum hours in advance appointments can be booked
        'min_hours_in_advance' => 24,

        // Auto-cancel pending appointments after X hours
        'auto_cancel_pending_after_hours' => 48,

        // Default location type for new appointments
        'default_location_type' => 'in_person', // in_person, zoom, hybrid
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Hours
    |--------------------------------------------------------------------------
    |
    | Define when pastoral care appointments are available.
    | Format: 'H:i' (24-hour format)
    | Days: monday, tuesday, wednesday, thursday, friday, saturday, sunday
    |
    */

    'business_hours' => [
        'monday' => [
            'enabled' => true,
            'start' => '09:00',
            'end' => '17:00',
            'lunch_break' => [
                'enabled' => true,
                'start' => '12:00',
                'end' => '13:00',
            ],
        ],
        'tuesday' => [
            'enabled' => true,
            'start' => '09:00',
            'end' => '17:00',
            'lunch_break' => [
                'enabled' => true,
                'start' => '12:00',
                'end' => '13:00',
            ],
        ],
        'wednesday' => [
            'enabled' => true,
            'start' => '09:00',
            'end' => '17:00',
            'lunch_break' => [
                'enabled' => true,
                'start' => '12:00',
                'end' => '13:00',
            ],
        ],
        'thursday' => [
            'enabled' => true,
            'start' => '09:00',
            'end' => '17:00',
            'lunch_break' => [
                'enabled' => true,
                'start' => '12:00',
                'end' => '13:00',
            ],
        ],
        'friday' => [
            'enabled' => true,
            'start' => '09:00',
            'end' => '17:00',
            'lunch_break' => [
                'enabled' => true,
                'start' => '12:00',
                'end' => '13:00',
            ],
        ],
        'saturday' => [
            'enabled' => true,
            'start' => '10:00',
            'end' => '16:00',
            'lunch_break' => [
                'enabled' => false,
            ],
        ],
        'sunday' => [
            'enabled' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Holidays and Blocked Dates
    |--------------------------------------------------------------------------
    |
    | Dates when appointments are not available.
    | Format: 'Y-m-d' or recurring formats
    |
    */

    'blocked_dates' => [
        // Fixed dates (Y-m-d format)
        'fixed' => [
            '2025-12-25', // Christmas
            '2025-12-26', // Boxing Day
            '2025-01-01', // New Year's Day
            '2025-12-31', // New Year's Eve
        ],

        // Recurring dates (m-d format for annual recurrence)
        'recurring' => [
            '12-25', // Christmas
            '12-26', // Boxing Day
            '01-01', // New Year's Day
            '12-31', // New Year's Eve
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Notifications
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'enabled' => env('PASTORAL_CARE_NOTIFICATIONS_ENABLED', true),

        // Email templates
        'templates' => [
            'appointment_confirmation' => 'emails.pastoral-care.appointment-confirmation',
            'appointment_reminder' => 'emails.pastoral-care.appointment-reminder',
            'appointment_cancellation' => 'emails.pastoral-care.appointment-cancellation',
            'new_appointment_notification' => 'emails.pastoral-care.new-appointment-notification',
        ],

        // Reminder settings
        'reminders' => [
            'enabled' => true,
            'send_at_hours_before' => 24, // Send reminder 24 hours before appointment
            'send_pastor_reminder' => true,
            'send_client_reminder' => true,
        ],

        // Confirmation settings
        'confirmations' => [
            'require_client_confirmation' => true,
            'confirmation_expires_hours' => 48,
            'send_pastor_notification' => true,
        ],

        // From email for pastoral care notifications
        'from' => [
            'address' => env('PASTORAL_CARE_FROM_EMAIL', env('MAIL_FROM_ADDRESS', 'noreply@icc-munich.de')),
            'name' => env('PASTORAL_CARE_FROM_NAME', env('MAIL_FROM_NAME', 'ICC Munich - Soin Pastoral')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Zoom Integration
    |--------------------------------------------------------------------------
    */

    'zoom' => [
        'enabled' => env('ZOOM_INTEGRATION_ENABLED', true),
        'default_meeting_duration' => 60,
        'auto_generate_links' => env('ZOOM_AUTO_GENERATE_LINKS', false),
        'default_meeting_settings' => [
            'join_before_host' => false,
            'mute_upon_entry' => true,
            'waiting_room' => true,
            'auto_recording' => 'none', // none, local, cloud
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */

    'validation' => [
        'client_name' => [
            'required',
            'string',
            'min:2',
            'max:255',
        ],
        'client_email' => [
            'required',
            'email',
            'max:255',
        ],
        'client_phone' => [
            'nullable',
            'string',
            'regex:/^(\+|00)?[1-9]\d{1,14}$/',
        ],
        'notes' => [
            'nullable',
            'string',
            'max:2000',
        ],
        'zoom_link' => [
            'nullable',
            'url',
            'max:500',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Settings
    |--------------------------------------------------------------------------
    */

    'ui' => [
        // How many days to show in the calendar picker
        'calendar_days_ahead' => 60,

        // How many time slots to show per day
        'max_time_slots_per_day' => 16,

        // Show pastor names in public booking
        'show_pastor_names_publicly' => true,

        // Show pastor photos in public booking
        'show_pastor_photos_publicly' => false,

        // Allow clients to choose specific pastors
        'allow_pastor_selection' => true,

        // Default language for public booking
        'default_language' => 'fr',

        // Available languages
        'available_languages' => ['fr', 'en', 'de'],

        // Theme settings for public booking
        'theme' => [
            'primary_color' => '#3B82F6',
            'secondary_color' => '#6B7280',
            'success_color' => '#10B981',
            'error_color' => '#EF4444',
            'warning_color' => '#F59E0B',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pastor Assignment
    |--------------------------------------------------------------------------
    */

    'pastor_assignment' => [
        // How to assign pastors to appointments
        'method' => 'manual', // manual, auto_round_robin, auto_least_busy

        // Auto-assignment settings
        'auto_assignment' => [
            'enabled' => false,
            'consider_availability' => true,
            'consider_workload' => true,
            'prefer_previous_pastor' => true, // For returning clients
        ],

        // Pastor availability settings
        'availability' => [
            'default_hours_per_week' => 10, // Hours available for pastoral care per week
            'max_appointments_per_day' => 6,
            'break_between_appointments_minutes' => 15,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Emergency Contact
    |--------------------------------------------------------------------------
    */

    'emergency' => [
        'enabled' => true,
        'contact_info' => [
            'phone' => env('EMERGENCY_PASTORAL_PHONE', '+49 89 123456789'),
            'email' => env('EMERGENCY_PASTORAL_EMAIL', 'urgence@icc-munich.de'),
            'message' => 'En cas d\'urgence spirituelle, contactez-nous immédiatement.',
        ],
        'show_on_booking_page' => true,
        'show_on_emails' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics and Reporting
    |--------------------------------------------------------------------------
    */

    'analytics' => [
        'enabled' => true,
        'track_booking_sources' => true,
        'track_cancellation_reasons' => true,
        'generate_monthly_reports' => true,
        'retention_days' => 365, // How long to keep analytics data
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    */

    'integrations' => [
        'calendar' => [
            'enabled' => env('CALENDAR_INTEGRATION_ENABLED', false),
            'type' => env('CALENDAR_TYPE', 'google'), // google, outlook, ical
        ],
        'sms' => [
            'enabled' => env('SMS_INTEGRATION_ENABLED', false),
            'provider' => env('SMS_PROVIDER', 'twilio'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */

    'security' => [
        'rate_limiting' => [
            'enabled' => true,
            'max_bookings_per_hour' => 5,
            'max_bookings_per_day' => 10,
        ],
        'spam_protection' => [
            'enabled' => true,
            'honeypot_field' => 'website', // Hidden field name for spam detection
            'min_form_submission_time' => 3, // Minimum seconds to fill form
        ],
        'encryption' => [
            'encrypt_personal_data' => env('ENCRYPT_PASTORAL_CARE_DATA', true),
            'encryption_key' => env('PASTORAL_CARE_ENCRYPTION_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup and Data Retention
    |--------------------------------------------------------------------------
    */

    'data_retention' => [
        'completed_appointments_days' => 1095, // 3 years
        'cancelled_appointments_days' => 365,  // 1 year
        'pending_appointments_days' => 90,     // 3 months
        'email_logs_days' => 180,              // 6 months
        'auto_cleanup_enabled' => env('PASTORAL_CARE_AUTO_CLEANUP', true),
    ],

];