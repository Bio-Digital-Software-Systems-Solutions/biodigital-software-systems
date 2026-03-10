<?php

use App\Models\Appointment;
use App\Models\User;
use App\Services\TelegramNotificationService;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    // Set up Telegram config for tests
    config([
        'services.telegram.enabled' => true,
        'services.telegram.bot_token' => 'test_bot_token_123',
        'services.telegram.bot_username' => 'TestBot',
    ]);
});

describe('TelegramNotificationService', function (): void {
    describe('isEnabled', function (): void {
        it('returns true when properly configured', function (): void {
            $service = new TelegramNotificationService;

            expect($service->isEnabled())->toBeTrue();
        });

        it('returns false when disabled', function (): void {
            config(['services.telegram.enabled' => false]);
            $service = new TelegramNotificationService;

            expect($service->isEnabled())->toBeFalse();
        });

        it('returns false when bot token is missing', function (): void {
            config(['services.telegram.bot_token' => null]);
            $service = new TelegramNotificationService;

            expect($service->isEnabled())->toBeFalse();
        });

        it('returns false when bot token is empty', function (): void {
            config(['services.telegram.bot_token' => '']);
            $service = new TelegramNotificationService;

            expect($service->isEnabled())->toBeFalse();
        });
    });

    describe('getBotUsername', function (): void {
        it('returns the configured bot username', function (): void {
            $service = new TelegramNotificationService;

            expect($service->getBotUsername())->toBe('TestBot');
        });
    });

    describe('getBotLink', function (): void {
        it('returns the telegram bot link', function (): void {
            $service = new TelegramNotificationService;

            expect($service->getBotLink())->toBe('https://t.me/TestBot');
        });

        it('returns null when bot username is not configured', function (): void {
            config(['services.telegram.bot_username' => null]);
            $service = new TelegramNotificationService;

            expect($service->getBotLink())->toBeNull();
        });
    });

    describe('sendMessage', function (): void {
        it('sends a message successfully', function (): void {
            Http::fake([
                'api.telegram.org/*' => Http::response([
                    'ok' => true,
                    'result' => [
                        'message_id' => 123,
                        'chat' => ['id' => '12345'],
                    ],
                ], 200),
            ]);

            $service = new TelegramNotificationService;
            $result = $service->sendMessage('12345', 'Test message');

            expect($result)->toBeTrue();

            Http::assertSent(fn($request): bool => str_contains((string) $request->url(), '/sendMessage')
                && $request['chat_id'] === '12345'
                && $request['text'] === 'Test message'
                && $request['parse_mode'] === 'HTML');
        });

        it('returns false when API returns error', function (): void {
            Http::fake([
                'api.telegram.org/*' => Http::response([
                    'ok' => false,
                    'error_code' => 400,
                    'description' => 'Bad Request',
                ], 400),
            ]);

            $service = new TelegramNotificationService;
            $result = $service->sendMessage('12345', 'Test message');

            expect($result)->toBeFalse();
        });

        it('returns false when service is disabled', function (): void {
            config(['services.telegram.enabled' => false]);

            $service = new TelegramNotificationService;
            $result = $service->sendMessage('12345', 'Test message');

            expect($result)->toBeFalse();
        });

        it('handles exceptions gracefully', function (): void {
            Http::fake([
                'api.telegram.org/*' => Http::response(null, 500),
            ]);

            $service = new TelegramNotificationService;
            $result = $service->sendMessage('12345', 'Test message');

            expect($result)->toBeFalse();
        });
    });

    describe('sendReminder', function (): void {
        it('sends reminder to participant with telegram configured', function (): void {
            Http::fake([
                'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
            ]);

            $organizer = User::factory()->create([
                'first_name' => 'John',
                'last_name' => 'Doe',
            ]);

            $participant = User::factory()->create([
                'first_name' => 'Jane',
                'telegram_chat_id' => '12345',
                'telegram_notifications' => true,
            ]);

            $appointment = Appointment::factory()->create([
                'title' => 'Test Appointment',
                'user_id' => $organizer->id,
                'start_datetime' => now()->addDay(),
                'end_datetime' => now()->addDay()->addHour(),
                'meeting_mode' => 'in_person',
            ]);

            $service = new TelegramNotificationService;
            $result = $service->sendReminder($appointment, $participant);

            expect($result)->toBeTrue();

            Http::assertSent(fn($request): bool => str_contains((string) $request->url(), '/sendMessage')
                && $request['chat_id'] === '12345'
                && str_contains((string) $request['text'], 'Test Appointment')
                && str_contains((string) $request['text'], 'Jane'));
        });

        it('returns false when participant has no telegram_chat_id', function (): void {
            $organizer = User::factory()->create();
            $participant = User::factory()->create([
                'telegram_chat_id' => null,
                'telegram_notifications' => true,
            ]);

            $appointment = Appointment::factory()->create([
                'user_id' => $organizer->id,
            ]);

            $service = new TelegramNotificationService;
            $result = $service->sendReminder($appointment, $participant);

            expect($result)->toBeFalse();
        });

        it('returns false when participant has telegram_notifications disabled', function (): void {
            $organizer = User::factory()->create();
            $participant = User::factory()->create([
                'telegram_chat_id' => '12345',
                'telegram_notifications' => false,
            ]);

            $appointment = Appointment::factory()->create([
                'user_id' => $organizer->id,
            ]);

            $service = new TelegramNotificationService;
            $result = $service->sendReminder($appointment, $participant);

            expect($result)->toBeFalse();
        });
    });

    describe('sendOrganizerReminder', function (): void {
        it('sends reminder to organizer with telegram configured', function (): void {
            Http::fake([
                'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
            ]);

            $organizer = User::factory()->create([
                'telegram_chat_id' => '67890',
                'telegram_notifications' => true,
            ]);

            $appointment = Appointment::factory()->create([
                'title' => 'Organizer Test',
                'user_id' => $organizer->id,
                'start_datetime' => now()->addDay(),
                'end_datetime' => now()->addDay()->addHour(),
            ]);

            $service = new TelegramNotificationService;
            $result = $service->sendOrganizerReminder($appointment);

            expect($result)->toBeTrue();

            Http::assertSent(fn($request): bool => $request['chat_id'] === '67890'
                && str_contains((string) $request['text'], 'Organizer Test'));
        });

        it('returns false when organizer has no telegram_chat_id', function (): void {
            $organizer = User::factory()->create([
                'telegram_chat_id' => null,
            ]);

            $appointment = Appointment::factory()->create([
                'user_id' => $organizer->id,
            ]);

            $service = new TelegramNotificationService;
            $result = $service->sendOrganizerReminder($appointment);

            expect($result)->toBeFalse();
        });
    });

    describe('sendConfirmation', function (): void {
        it('sends confirmation message', function (): void {
            Http::fake([
                'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
            ]);

            $organizer = User::factory()->create([
                'first_name' => 'John',
                'last_name' => 'Doe',
            ]);

            $participant = User::factory()->create([
                'first_name' => 'Jane',
                'telegram_chat_id' => '12345',
                'telegram_notifications' => true,
            ]);

            $appointment = Appointment::factory()->create([
                'title' => 'Confirmed Appointment',
                'user_id' => $organizer->id,
            ]);

            $service = new TelegramNotificationService;
            $result = $service->sendConfirmation($appointment, $participant);

            expect($result)->toBeTrue();

            Http::assertSent(fn($request): bool => str_contains((string) $request['text'], 'Confirmation')
                && str_contains((string) $request['text'], 'Confirmed Appointment'));
        });
    });

    describe('sendCancellation', function (): void {
        it('sends cancellation message', function (): void {
            Http::fake([
                'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
            ]);

            $organizer = User::factory()->create();
            $participant = User::factory()->create([
                'first_name' => 'Jane',
                'telegram_chat_id' => '12345',
                'telegram_notifications' => true,
            ]);

            $appointment = Appointment::factory()->create([
                'title' => 'Cancelled Appointment',
                'user_id' => $organizer->id,
            ]);

            $service = new TelegramNotificationService;
            $result = $service->sendCancellation($appointment, $participant);

            expect($result)->toBeTrue();

            Http::assertSent(fn($request): bool => str_contains((string) $request['text'], 'Annulation')
                && str_contains((string) $request['text'], 'Cancelled Appointment'));
        });
    });

    describe('sendInvitation', function (): void {
        it('sends invitation message with action links', function (): void {
            Http::fake([
                'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
            ]);

            $organizer = User::factory()->create([
                'first_name' => 'John',
                'last_name' => 'Doe',
            ]);

            $participant = User::factory()->create([
                'first_name' => 'Jane',
                'telegram_chat_id' => '12345',
                'telegram_notifications' => true,
            ]);

            $appointment = Appointment::factory()->create([
                'title' => 'Invitation Test',
                'user_id' => $organizer->id,
                'start_datetime' => now()->addDay(),
                'end_datetime' => now()->addDay()->addHour(),
            ]);

            $service = new TelegramNotificationService;
            $result = $service->sendInvitation(
                $appointment,
                $participant,
                'https://example.com/confirm',
                'https://example.com/decline'
            );

            expect($result)->toBeTrue();

            Http::assertSent(fn($request): bool => str_contains((string) $request['text'], 'Invitation')
                && str_contains((string) $request['text'], 'Invitation Test')
                && str_contains((string) $request['text'], 'https://example.com/confirm')
                && str_contains((string) $request['text'], 'https://example.com/decline'));
        });
    });

    describe('sendUpdate', function (): void {
        it('sends update message with changes', function (): void {
            Http::fake([
                'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
            ]);

            $organizer = User::factory()->create();
            $participant = User::factory()->create([
                'first_name' => 'Jane',
                'telegram_chat_id' => '12345',
                'telegram_notifications' => true,
            ]);

            $appointment = Appointment::factory()->create([
                'title' => 'Updated Appointment',
                'user_id' => $organizer->id,
            ]);

            $changes = [
                'title' => ['old' => 'Old Title', 'new' => 'Updated Appointment'],
                'location' => ['old' => 'Room A', 'new' => 'Room B'],
            ];

            $service = new TelegramNotificationService;
            $result = $service->sendUpdate($appointment, $participant, $changes);

            expect($result)->toBeTrue();

            Http::assertSent(fn($request): bool => str_contains((string) $request['text'], 'Mise a jour')
                && str_contains((string) $request['text'], 'Titre')
                && str_contains((string) $request['text'], 'Lieu'));
        });
    });

    describe('sendRemindersToAllParticipants', function (): void {
        it('sends reminders to all participants with telegram enabled', function (): void {
            Http::fake([
                'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
            ]);

            $organizer = User::factory()->create();

            $participant1 = User::factory()->create([
                'telegram_chat_id' => '11111',
                'telegram_notifications' => true,
            ]);

            $participant2 = User::factory()->create([
                'telegram_chat_id' => '22222',
                'telegram_notifications' => true,
            ]);

            $participant3 = User::factory()->create([
                'telegram_chat_id' => null,
                'telegram_notifications' => true,
            ]);

            $appointment = Appointment::factory()->create([
                'user_id' => $organizer->id,
            ]);

            $appointment->participants()->attach([
                $participant1->id => ['status' => 'accepted', 'invited_at' => now()],
                $participant2->id => ['status' => 'accepted', 'invited_at' => now()],
                $participant3->id => ['status' => 'accepted', 'invited_at' => now()],
            ]);

            $service = new TelegramNotificationService;
            $results = $service->sendRemindersToAllParticipants($appointment);

            expect($results['sent'])->toBe(2);
            expect($results['errors'])->toBe(0);
        });
    });

    describe('getBotInfo', function (): void {
        it('returns bot info when successful', function (): void {
            Http::fake([
                'api.telegram.org/*' => Http::response([
                    'ok' => true,
                    'result' => [
                        'id' => 123456789,
                        'is_bot' => true,
                        'first_name' => 'Test Bot',
                        'username' => 'test_bot',
                    ],
                ], 200),
            ]);

            $service = new TelegramNotificationService;
            $info = $service->getBotInfo();

            expect($info)->toBeArray();
            expect($info['is_bot'])->toBeTrue();
            expect($info['username'])->toBe('test_bot');
        });

        it('returns null when disabled', function (): void {
            config(['services.telegram.enabled' => false]);

            $service = new TelegramNotificationService;
            $info = $service->getBotInfo();

            expect($info)->toBeNull();
        });

        it('returns null on API error', function (): void {
            Http::fake([
                'api.telegram.org/*' => Http::response(['ok' => false], 401),
            ]);

            $service = new TelegramNotificationService;
            $info = $service->getBotInfo();

            expect($info)->toBeNull();
        });
    });

    describe('getUpdates', function (): void {
        it('returns updates when successful', function (): void {
            Http::fake([
                'api.telegram.org/*' => Http::response([
                    'ok' => true,
                    'result' => [
                        ['update_id' => 1, 'message' => ['text' => '/start']],
                        ['update_id' => 2, 'message' => ['text' => 'Hello']],
                    ],
                ], 200),
            ]);

            $service = new TelegramNotificationService;
            $updates = $service->getUpdates();

            expect($updates)->toBeArray();
            expect($updates)->toHaveCount(2);
        });

        it('returns empty array when disabled', function (): void {
            config(['services.telegram.enabled' => false]);

            $service = new TelegramNotificationService;
            $updates = $service->getUpdates();

            expect($updates)->toBeArray();
            expect($updates)->toBeEmpty();
        });
    });
});
