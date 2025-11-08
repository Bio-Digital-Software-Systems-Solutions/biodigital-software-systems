# AIG-App Messaging & Pastoral Care Exploration Report

## Executive Summary

The AIG-App codebase has a robust implementation of messaging systems and pastoral care appointment management with email notifications. The system uses multiple messaging approaches (Chat Rooms, Direct Messages, System Messages) and integrates pastoral care appointments with email notifications.

---

## 1. MESSAGING SYSTEM ARCHITECTURE

### 1.1 Chat System (Real-time Chat)

**Models:**
- **ChatRoom** (`/app/Models/ChatRoom.php`)
  - Supports both `direct` and `group` chat types
  - Created by a user (`created_by`)
  - Participants are stored in `chat_room_user` pivot table
  - Has many messages and lastMessage relationship
  - Includes activity logging via Spatie Laravel Activity Log

- **ChatMessage** (`/app/Models/ChatMessage.php`)
  - Belongs to ChatRoom and User (sender)
  - Tracks read status with `is_read` boolean
  - Includes timestamps for message creation
  - Activity logging enabled

**Key Features:**
- Direct message rooms (1:1) and group chats
- Read status tracking (is_read flag)
- Message content with XSS sanitization (strip_tags)
- Room creation with automatic duplicate detection for direct messages
- Unread message count tracking
- Leave room functionality with auto-deletion when empty
- Room touch() to update last activity timestamp

**Controller:** `ChatController` (`/app/Http/Controllers/ChatController.php`)
- `index()` - Display chat interface with user's chat rooms
- `createRoom()` - Create new chat room (direct or group)
- `getMessages()` - Fetch and mark messages as read
- `sendMessage()` - Send message with XSS protection
- `getUnreadCount()` - Get total unread messages
- `leaveRoom()` - Leave room and cleanup
- Permission: `can:use chat` middleware

**Authorization:** `ChatRoomPolicy` (`/app/Policies/ChatRoomPolicy.php`)
- Checks user participation in chat rooms
- Cache management for participant checks

---

### 1.2 Direct Messages System (One-to-One Messaging)

**Model:** `Message` (`/app/Models/Message.php`)
- One-way message from sender to receiver
- Supports multiple types: `direct`, `broadcast`, `system`, `appointment`
- Can have multiple attachments
- Supports CC and BCC recipients
- Has department relationships (broadcast messaging)
- Read status with `read_at` timestamp
- Useful scopes: `unread()`, `read()`, `type()`, `betweenUsers()`

**Features:**
- Excerpt generation (first 100 chars)
- Type labels (French translations)
- Mark as read functionality
- Support for system messages and broadcasts

---

### 1.3 Message Attachments

**Model:** `MessageAttachment` (`/app/Models/MessageAttachment.php`)
- Separate table for file attachments on messages
- Related to Message model

---

## 2. PASTORAL CARE APPOINTMENT SYSTEM

### 2.1 PastoralCare Model

**Location:** `/app/Models/PastoralCare.php`

**Key Attributes:**
```php
- uuid (unique identifier)
- user_id (client/user booking)
- pastor_id (assigned pastor)
- appointment_date (date)
- appointment_time (datetime)
- duration_minutes (integer)
- status (enum: pending, confirmed, completed, cancelled, no_show)
- location_type (enum: in_person, zoom, hybrid)
- zoom_link (nullable URL)
- client_name, client_email, client_phone
- notes (nullable text)
- confirmation_sent_at (timestamp)
- reminder_sent_at (timestamp)
- cancelled_at (timestamp)
- cancellation_reason (text)
- timestamps + soft deletes
```

**Relationships:**
- `user()` - BelongsTo User (client/user)
- `pastor()` - BelongsTo User (pastor_id)

**Scopes:**
- `pending()`, `confirmed()`, `completed()`, `cancelled()`
- `upcoming()` - Future appointments with pending/confirmed status
- `forPastor($pastorId)` - Filter by pastor
- `onDate($date)`, `betweenDates($startDate, $endDate)`

**Business Logic Methods:**
- `confirm()` - Mark appointment as confirmed
- `cancel($reason)` - Cancel appointment with optional reason
- `complete()` - Mark as completed
- `markAsNoShow()` - Mark past appointment as no-show
- `sendReminderEmail()` - Send reminder email (queued, implementation pending)
- `isTimeSlotAvailable()` - Static method for availability checking with overlap detection
- `getAvailableTimeSlots()` - Generate available slots for date (9-5 with 30-min intervals)

**Database Schema:**
- Migration: `/database/migrations/2025_11_06_143425_create_pastoral_cares_table.php`
- Indexes on: pastor_id + appointment_date, appointment_date + appointment_time, status, client_email

---

### 2.2 Pastoral Care Controller

**Location:** `/app/Http/Controllers/PastoralCareController.php`

**Routes & Actions:**
```
GET    /pastoral-care/appointments              - index (list)
GET    /pastoral-care/appointments/create       - create (form)
POST   /pastoral-care/appointments              - store (create)
GET    /pastoral-care/appointments/{uuid}      - show (detail)
GET    /pastoral-care/appointments/{uuid}/edit - edit (form)
PUT    /pastoral-care/appointments/{uuid}      - update
DELETE /pastoral-care/appointments/{uuid}      - destroy
POST   /pastoral-care/appointments/{uuid}/confirm  - confirm
POST   /pastoral-care/appointments/{uuid}/cancel   - cancel
POST   /pastoral-care/appointments/{uuid}/complete - complete
POST   /pastoral-care/appointments/{uuid}/no-show  - noShow
GET    /pastoral-care/available-slots          - getAvailableSlots (AJAX)
```

**Key Features:**
- Permission-based access control:
  - `can:view pastoral care`
  - `can:create pastoral care`
  - `can:edit pastoral care`
  - `can:delete pastoral care`
  - `can:manage pastoral care` (admin override)
- Pastors see only their appointments unless admin
- Time slot availability validation
- Duplicate room detection for direct messages

**Permissions:** `/app/Policies/PastoralCarePolicy.php`

---

## 3. EMAIL NOTIFICATION SYSTEM

### 3.1 Pastoral Care Email Classes

All implement `Mailable` + `ShouldQueue` for async processing.

**PastoralCareAppointmentConfirmation** (`/app/Mail/PastoralCareAppointmentConfirmation.php`)
- Subject: "Confirmation de votre rendez-vous de soin pastoral - ICC Munich"
- Template: `emails.pastoral-care.appointment-confirmation`
- Context:
  - appointment, pastor
  - confirmUrl, cancelUrl (public routes)
  - churchName, churchWebsite, churchEmail, churchPhone

**PastoralCareAppointmentReminder** (`/app/Mail/PastoralCareAppointmentReminder.php`)
- Subject: "Rappel : Votre rendez-vous de soin pastoral demain - ICC Munich"
- Template: `emails.pastoral-care.appointment-reminder`
- Context: appointment, pastor, cancelUrl, churchName, churchEmail, churchPhone

**PastoralCareAppointmentCancellation** (`/app/Mail/PastoralCareAppointmentCancellation.php`)
- Subject: "Annulation de votre rendez-vous de soin pastoral - ICC Munich"
- Template: `emails.pastoral-care.appointment-cancellation`
- Context: appointment, pastor, bookingUrl, churchName, churchWebsite, churchEmail, churchPhone

**PastoralCareNewAppointmentNotification** (`/app/Mail/PastoralCareNewAppointmentNotification.php`)
- Subject: "Nouveau rendez-vous de soin pastoral planifié - ICC Munich"
- Template: `emails.pastoral-care.new-appointment-notification`
- Context: appointment, pastor, dashboardUrl, appointmentUrl, churchName, churchEmail
- Sent to pastor to notify of new appointment

### 3.2 Other Mail Classes

**AppointmentCreated** - For general appointment system
**NewMessageMail** - For message notifications
**TrainingEnrollmentApproved/Rejected** - For training system
**ContactSubmitted** - For contact form
**WelcomeMail** - Welcome emails

---

## 4. NOTIFICATION SYSTEM

### 4.1 Laravel Notifications

**AppointmentInvitation** - Appointment invitation notifications

**AppointmentConfirmation & AppointmentCancellation** - Status change notifications

**NewDirectMessageNotification** (`/app/Notifications/NewDirectMessageNotification.php`)
- Channels: mail, database
- Sends email with message preview
- Database notification with metadata:
  - type, title, message
  - sender_id, sender_name
  - chat_message_id, room_id
  - action_url (link to chat)

**QuizDeadlineReminder & QuizPublishedNotification** - Quiz notifications

**Database:** Notifications table (UUID primary key)
- Migration: `/database/migrations/2025_10_13_104003_create_notifications_table.php`
- Schema: id, type, notifiable_type, notifiable_id, data (JSON), read_at, timestamps

---

## 5. APPOINTMENT NOTIFICATION SERVICE

**Location:** `/app/Services/AppointmentNotificationService.php`

This service implements a **multi-channel notification strategy** for appointments:

### 5.1 Invitation Notification Flow

`sendInvitationNotification(Appointment $appointment, User $participant, string $confirmationToken)`

1. **Standard Notification Channel**
   - Uses `AppointmentInvitation` notification class
   - Sends via email + database notification

2. **System Message in Messages Inbox**
   - Creates `Message` with type='system'
   - Subject: "🗓️ Invitation au rendez-vous : {title}"
   - HTML content with appointment details
   - Includes confirm/decline action links

3. **Chat Room Backup**
   - Gets or creates direct chat room between organizer and participant
   - Sends `ChatMessage` with formatted invitation content
   - Updates room's updated_at timestamp

4. **Email Notification**
   - Sends `NewDirectMessageNotification` to participant
   - Alerts them to the new direct message

### 5.2 Update Notification Flow

`sendUpdateNotification(Appointment $appointment, User $participant, string $action)`

Actions: updated, confirmed, cancelled, completed

1. Creates system `Message` with emoji action labels
2. Gets/creates direct chat room
3. Sends formatted chat message
4. Sends email notification about the message

### 5.3 Organizer Confirmation

`sendOrganizerConfirmation(Appointment $appointment)`

1. Creates system message in organizer's inbox
2. Gets/creates personal "Mes rendez-vous" notification room
3. Sends confirmation message in chat room

### 5.4 Helper Methods

**getOrCreateDirectRoom()** - Ensures consistent direct message pairing
**getOrCreateOrganizerRoom()** - Creates personal notification room for organizer
**generateDirectRoomName()** - Names direct rooms descriptively
**createInvitationMessageContent()** - Generates HTML-formatted invitation
**createUpdateMessageContent()** - Generates HTML-formatted update message

---

## 6. PASTORAL CARE CONFIGURATION

**Location:** `/config/pastoral_care.php`

### 6.1 General Settings
- Enabled status
- Church name, email, phone, website

### 6.2 Appointment Settings
- Default duration: 60 minutes
- Duration options: [30, 45, 60, 90, 120]
- Time slot interval: 30 minutes
- Max appointments per user: 4/month, 1/week
- Max days in advance: 60
- Min hours in advance: 24
- Auto-cancel pending: 48 hours

### 6.3 Business Hours
- Monday-Friday: 9:00-17:00 with 12:00-13:00 lunch
- Saturday: 10:00-16:00
- Sunday: Closed

### 6.4 Email Notifications Config
```php
'notifications' => [
    'enabled' => true,
    'templates' => [
        'appointment_confirmation' => 'emails.pastoral-care.appointment-confirmation',
        'appointment_reminder' => 'emails.pastoral-care.appointment-reminder',
        'appointment_cancellation' => 'emails.pastoral-care.appointment-cancellation',
        'new_appointment_notification' => 'emails.pastoral-care.new-appointment-notification',
    ],
    'reminders' => [
        'enabled' => true,
        'send_at_hours_before' => 24,
        'send_pastor_reminder' => true,
        'send_client_reminder' => true,
    ],
    'confirmations' => [
        'require_client_confirmation' => true,
        'confirmation_expires_hours' => 48,
        'send_pastor_notification' => true,
    ],
    'from' => [
        'address' => env('PASTORAL_CARE_FROM_EMAIL'),
        'name' => env('PASTORAL_CARE_FROM_NAME'),
    ],
]
```

### 6.5 Integration Settings
- Zoom integration (optional)
- Calendar integration (optional)
- SMS integration (optional)
- Security: rate limiting, spam protection, encryption
- Data retention policies

---

## 7. EMAIL TEMPLATES

**Pastoral Care Templates:**
- `/resources/views/emails/pastoral-care/appointment-confirmation.blade.php`
- `/resources/views/emails/pastoral-care/appointment-reminder.blade.php`
- `/resources/views/emails/pastoral-care/appointment-cancellation.blade.php`
- `/resources/views/emails/pastoral-care/new-appointment-notification.blade.php`

**Other Message Templates:**
- `/resources/views/emails/appointment-invitation.blade.php`
- `/resources/views/emails/appointment-created.blade.php`
- `/resources/views/emails/new-message.blade.php`

---

## 8. PUBLIC PASTORAL CARE ROUTES

**Location:** `/routes/web.php`

```php
GET     /pastoral-care/book                              - PublicBook page
GET     /pastoral-care/appointments/{uuid}/confirm       - Confirm form
POST    /pastoral-care/appointments/{uuid}/confirm       - Process confirmation
GET     /pastoral-care/appointments/{uuid}/cancel        - Cancel form
POST    /pastoral-care/appointments/{uuid}/cancel        - Process cancellation
GET     /pastoral-care/appointments/{uuid}/success       - Success page
```

---

## 9. CURRENT STATE: WHAT'S IMPLEMENTED

### 9.1 Fully Implemented

1. **Chat System**
   - Direct message rooms with participant management
   - Group chat functionality
   - Message read tracking
   - Unread count functionality
   - XSS protection with content sanitization
   - Activity logging

2. **Pastoral Care Core**
   - Model with relationships, scopes, and validation
   - CRUD operations
   - Status management (pending → confirmed → completed)
   - Cancellation with reason tracking
   - No-show marking
   - Time slot availability checking
   - Auto-generate available time slots
   - Soft deletes

3. **Email Infrastructure**
   - Mail classes set up for all pastoral care scenarios
   - Queued email processing (ShouldQueue)
   - Email templates with Blade markdown
   - Template context properly configured

4. **Configuration System**
   - Comprehensive config file with all settings
   - Environment variable support
   - Business hours configuration
   - Validation rules
   - Integration settings

### 9.2 Partially Implemented

1. **Reminder Emails**
   - Configuration exists: `reminder_sent_at` tracking
   - Model method exists: `sendReminderEmail()` (marked as TODO in code)
   - No scheduler or job yet to trigger reminders 24 hours before

2. **Notification Sending on Appointment Actions**
   - Mail classes created but not wired to controller
   - No automatic email sending on:
     - Appointment creation (new appointment notification to pastor)
     - Appointment confirmation (confirmation email to client)
     - Appointment cancellation (cancellation email to client)

3. **Integration with Chat/Direct Messages**
   - No chat room creation when pastoral care appointment created
   - No direct message link between pastor and client
   - `AppointmentNotificationService` exists but only used for general appointments, not pastoral care

### 9.3 Not Implemented

1. **Automated Reminders**
   - No scheduled job to send reminder emails 24 hours before
   - No cron job or queue job configured

2. **Client Confirmation Flow**
   - Public routes exist but no confirmation/cancellation logic
   - No feedback to pastor when client confirms/cancels via email link

3. **Chat Room Auto-Creation**
   - No automatic direct message room between pastor and client for pastoral care

4. **Notification Bell/Dashboard Integration**
   - Database notifications created but not displayed in UI

---

## 10. KEY FILES SUMMARY

### Models
- `/app/Models/ChatRoom.php`
- `/app/Models/ChatMessage.php`
- `/app/Models/Message.php`
- `/app/Models/MessageAttachment.php`
- `/app/Models/PastoralCare.php`

### Controllers
- `/app/Http/Controllers/ChatController.php`
- `/app/Http/Controllers/PastoralCareController.php`

### Services
- `/app/Services/AppointmentNotificationService.php` (general appointments, not pastoral care)

### Mail Classes
- `/app/Mail/PastoralCareAppointmentConfirmation.php`
- `/app/Mail/PastoralCareAppointmentReminder.php`
- `/app/Mail/PastoralCareAppointmentCancellation.php`
- `/app/Mail/PastoralCareNewAppointmentNotification.php`

### Notifications
- `/app/Notifications/NewDirectMessageNotification.php`
- `/app/Notifications/AppointmentInvitation.php`
- `/app/Notifications/AppointmentConfirmation.php`
- `/app/Notifications/AppointmentCancellation.php`

### Configuration
- `/config/pastoral_care.php`

### Migrations
- `/database/migrations/2025_11_06_143425_create_pastoral_cares_table.php`
- `/database/migrations/2025_10_13_104003_create_notifications_table.php`
- `/database/migrations/2025_08_22_073142_create_chat_messages_table.php`
- `/database/migrations/2025_08_21_064448_create_messages_table.php`

### Routes
- `/routes/web.php` (pastoral care + public booking)
- `/routes/api.php` (API pastoral care routes)

### Views
- `/resources/views/emails/pastoral-care/*.blade.php`

---

## 11. PERMISSION STRUCTURE

**Pastoral Care Permissions:**
- `view pastoral care` - View appointments
- `create pastoral care` - Create appointments
- `edit pastoral care` - Edit appointments
- `delete pastoral care` - Delete appointments
- `manage pastoral care` - Override access (admin)

**Chat Permissions:**
- `use chat` - Access chat functionality

**Seeder:** `/database/seeders/PastoralCarePermissionsSeeder.php`

---

## 12. RECOMMENDATIONS FOR INTEGRATION

### For Chat Integration
1. Add `ChatRoom` creation when pastoral care appointment is created
2. Add pastor and client as participants to the room
3. Store `chat_room_id` in `PastoralCare` model (optional, for quick linking)

### For Email Notification Sending
1. Dispatch mail in `PastoralCareController`:
   - On `store()`: Send `PastoralCareNewAppointmentNotification` to pastor
   - On `confirm()`: Send `PastoralCareAppointmentConfirmation` to client
   - On `cancel()`: Send `PastoralCareAppointmentCancellation` to client

2. Create appointment confirmation events:
   - Public route handlers to process confirmations
   - Update appointment status when client confirms
   - Notify pastor of confirmation status

### For Reminder Emails
1. Create a scheduled job/command to find appointments within 24 hours
2. Send `PastoralCareAppointmentReminder` to client
3. Update `reminder_sent_at` timestamp

### For Multi-Channel Notifications (Like Appointments System)
1. Consider using `AppointmentNotificationService` pattern for pastoral care
2. Create messages in both chat and direct message systems
3. Maintain notification trail across channels

---

## 13. TECHNOLOGY STACK CONFIRMED

- Laravel 12 (Mailable, Notifications)
- Spatie Laravel Activity Log (audit trails)
- Blade markdown for email templates
- Laravel Queue system (ShouldQueue)
- Laravel Policies for authorization
- UUID-based routing for pastoral care
- Soft deletes for appointment history

