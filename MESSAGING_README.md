# Messaging & Pastoral Care System - Documentation Index

This directory contains comprehensive documentation about the messaging and pastoral care appointment system in the AIG-App codebase.

## Quick Navigation

### For a Quick Overview (5 minutes)
Start with: **MESSAGING_QUICK_SUMMARY.txt**
- High-level overview of all systems
- Status indicators (implemented ✓ / partial ⚠ / not done ✗)
- Quick reference for developers
- Recommended next steps

### For Complete Details (20-30 minutes)
Read: **MESSAGING_EXPLORATION_REPORT.md**
- Detailed architectural overview
- All models, controllers, and services explained
- Configuration system documentation
- Complete list of key files
- Implementation status for each feature
- Detailed recommendations

### For Code Examples (10-15 minutes)
Reference: **MESSAGING_CODE_EXAMPLES.md**
- Real code snippets from the codebase
- Practical examples for each system
- Copy-paste ready for implementation
- 10 categories covering all major features

### For Strategic Overview (10 minutes)
View: **MESSAGING_FINAL_SUMMARY.txt**
- Key findings summary
- Current implementation status
- Prioritized action items with time estimates
- Technology stack overview
- Conclusion and next steps

---

## System Overview

### Three Messaging Systems

1. **Chat Rooms** (Real-time, many-to-many)
   - Direct messages (1:1) and group chats
   - Read status tracking
   - XSS protection
   - Activity logging

2. **Direct Messages** (One-to-one, one-way)
   - Multiple message types (direct, broadcast, system, appointment)
   - CC/BCC support
   - File attachments
   - Advanced filtering with scopes

3. **System Messages**
   - Used for notifications
   - Multi-channel delivery via AppointmentNotificationService
   - HTML-formatted content

### Pastoral Care Appointment System

- **Status Flow**: pending → confirmed → completed/cancelled/no_show
- **Key Features**: 
  - Time slot availability checking with overlap detection
  - Automatic available time slot generation
  - Comprehensive appointment tracking
  - Soft deletes for audit trail

### Email Notifications

4 mail classes ready to use:
- PastoralCareNewAppointmentNotification (to pastor)
- PastoralCareAppointmentConfirmation (to client)
- PastoralCareAppointmentReminder (24h before)
- PastoralCareAppointmentCancellation (on cancel)

---

## Implementation Status

### Fully Implemented ✓
- Chat system (rooms, messages, read tracking)
- Pastoral care model & CRUD operations
- Email infrastructure (Mail classes, templates)
- Configuration system (400+ lines)
- Permission framework
- Time slot availability checking

### Partially Implemented ⚠
- Reminder emails (configured but no scheduler)
- Email notifications on actions (classes exist, not wired)
- Chat integration (infrastructure ready, not connected)

### Not Implemented ✗
- Automated reminder job (24h before)
- Client confirmation processing (routes exist, no logic)
- Auto-create chat rooms for appointments
- Dashboard notification UI display

---

## Priority Roadmap

### Priority 1: Email Notifications (1-2 hours)
Wire email sending in PastoralCareController:
- store() → send notification to pastor
- confirm() → send confirmation to client
- cancel() → send cancellation to client

### Priority 2: Client Confirmation Flow (2-3 hours)
Implement public route handlers for email links:
- Process confirmation/cancellation
- Update appointment status
- Notify pastor of change

### Priority 3: Chat Integration (1-2 hours)
Auto-create chat rooms for appointments:
- Create ChatRoom when appointment created
- Add pastor and client as participants
- Send initial appointment message

### Priority 4: Automated Reminders (2-3 hours)
Create scheduled reminder system:
- Build Artisan command: SendPastoralCareReminders
- Find appointments 24 hours before
- Dispatch reminder emails
- Schedule in kernel

### Priority 5: Multi-Channel Service (2-3 hours)
Replicate AppointmentNotificationService pattern:
- Create PastoralCareNotificationService
- Send via: notification + message + chat + email
- Maintain notification trail

---

## Key Files Reference

### Models
- `/app/Models/ChatRoom.php` - Chat room (direct/group)
- `/app/Models/ChatMessage.php` - Chat messages
- `/app/Models/Message.php` - Direct messages
- `/app/Models/MessageAttachment.php` - Message attachments
- `/app/Models/PastoralCare.php` - Appointments

### Controllers
- `/app/Http/Controllers/ChatController.php` - Chat operations
- `/app/Http/Controllers/PastoralCareController.php` - Appointment CRUD

### Services
- `/app/Services/AppointmentNotificationService.php` - Multi-channel notifications (template for pastoral care)

### Mail Classes
- `/app/Mail/PastoralCareAppointmentConfirmation.php`
- `/app/Mail/PastoralCareAppointmentReminder.php`
- `/app/Mail/PastoralCareAppointmentCancellation.php`
- `/app/Mail/PastoralCareNewAppointmentNotification.php`

### Configuration
- `/config/pastoral_care.php` - Complete system configuration

### Database
- `/database/migrations/2025_11_06_143425_create_pastoral_cares_table.php`
- `/database/migrations/2025_10_13_104003_create_notifications_table.php`
- `/database/migrations/2025_08_22_073142_create_chat_messages_table.php`

### Routes
- `/routes/web.php` - Authenticated and public pastoral care routes
- `/routes/api.php` - API endpoints

### Email Templates
- `/resources/views/emails/pastoral-care/*.blade.php` - All email templates

---

## Technology Stack

**Backend:**
- Laravel 12
- Spatie Laravel Activity Log
- Laravel Queue system
- Laravel Policies

**Database:**
- MySQL
- UUID-based identifiers
- Soft deletes
- Optimized indexes

**Frontend:**
- Inertia.js + React
- TailwindCSS
- Sonner (toast notifications)

**Security:**
- XSS protection
- Rate limiting
- Spam protection
- Data encryption
- Permission-based access control

---

## Configuration Highlights

**Appointment Settings:**
- Default duration: 60 minutes
- Duration options: 30-120 minutes
- Booking window: 24-60 days in advance
- Time slots: 30-minute intervals

**Business Hours:**
- Monday-Friday: 9:00-17:00
- Saturday: 10:00-16:00
- Sunday: Closed
- Lunch break: 12:00-13:00

**Email Settings:**
- Reminders: 24 hours before (configurable)
- Client confirmation required: 48 hours
- Notifications enabled: Yes

**Security:**
- Rate limiting: 5 bookings/hour
- Spam protection: Honeypot + form submission time
- Data encryption: Yes
- Data retention: 1-3 years

**Optional Integrations:**
- Zoom (auto-generate links)
- Calendar (Google, Outlook, iCal)
- SMS (Twilio)

---

## Database Schema Overview

### pastoral_cares table
- id, uuid (unique)
- user_id (client), pastor_id (assigned pastor)
- appointment_date, appointment_time, duration_minutes
- status (enum), location_type (enum), zoom_link
- client_name, client_email, client_phone
- notes, confirmation_sent_at, reminder_sent_at
- cancelled_at, cancellation_reason
- timestamps, soft deletes
- Indexes: pastor_id+date, date+time, status, client_email

### chat_rooms table
- id, uuid, name, type (direct/group)
- created_by, timestamps
- Pivot: chat_room_user (user_id, room_id)

### chat_messages table
- id, uuid, room_id, sender_id
- content, is_read, timestamps

### messages table
- id, uuid, sender_id, receiver_id
- subject, content, type (direct/broadcast/system/appointment)
- read_at, department_id, cc_recipients, bcc_recipients
- timestamps
- Relationship: MessageAttachment (one-to-many)

### notifications table
- id (uuid), type, notifiable_type, notifiable_id
- data (json), read_at, timestamps

---

## How to Use This Documentation

1. **Getting Started**: Read MESSAGING_QUICK_SUMMARY.txt
2. **Understanding Details**: Read MESSAGING_EXPLORATION_REPORT.md
3. **Implementing Features**: Reference MESSAGING_CODE_EXAMPLES.md
4. **Planning Work**: Review MESSAGING_FINAL_SUMMARY.txt
5. **Finding Files**: Use the Key Files Reference above

---

## Next Steps

1. Choose a priority from the roadmap
2. Reference the code examples for implementation patterns
3. Check the full report for detailed specifications
4. Review the AppointmentNotificationService for multi-channel pattern
5. Refer to the config file for all customizable settings

---

## Questions & Notes

For questions about specific components, refer to:
- Model details in MESSAGING_EXPLORATION_REPORT.md (Section 2)
- Controller details in MESSAGING_EXPLORATION_REPORT.md (Section 2.2)
- Service details in MESSAGING_EXPLORATION_REPORT.md (Section 5)
- Code examples in MESSAGING_CODE_EXAMPLES.md

For configuration questions, see:
- /config/pastoral_care.php (complete with comments)
- MESSAGING_EXPLORATION_REPORT.md (Section 6)

---

Generated: November 6, 2025
Total Documentation: 1,374 lines across 4 documents
