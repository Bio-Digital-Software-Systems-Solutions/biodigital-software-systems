# AIG-App Development Notes

## Project Overview
AIG-App is a comprehensive Laravel + Inertia + React + TypeScript application for organizational management with the following key features:

- **Event Management**: Create, manage, and participate in organizational events
- **Book Rental System**: Library management with book lending capabilities
- **Article System**: Content creation and sharing
- **Chat Functionality**: Real-time messaging between users
- **User Management**: Role-based permissions using Spatie Laravel Permission
- **Internationalization**: Multi-language support (FR/EN/DE)
- **Theme Switching**: Dark/Light mode with system preference support

## Technology Stack

### Backend
- **Laravel 12**: PHP framework
- **Inertia.js**: Server-side rendering with SPA-like experience
- **Spatie Laravel Permission**: Role and permission management
- **Spatie Laravel Activity Log**: Activity tracking for all models
- **Sentry**: Error tracking and performance monitoring
- **MySQL**: Database

### Frontend
- **React 18**: UI framework
- **TypeScript**: Type safety
- **TailwindCSS**: Styling framework with dark mode support
- **Heroicons**: Icon library
- **React i18next**: Internationalization
- **Vite**: Build tool

## Key Commands

### Development
```bash
# Start development server
php artisan serve
npm run dev

# Database operations
php artisan migrate
php artisan db:seed

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Testing
```bash
# Run all tests
php artisan test

# Run specific test files
php artisan test --filter=EventControllerTest
php artisan test --filter=BookControllerTest

# Run with coverage
php artisan test --coverage
```

### Build
```bash
# Production build
npm run build
php artisan optimize
```

## Architecture

### Database Schema
- **Users**: Extended with first_name, last_name, phone, address
- **Events**: With polymorphic address relationship and participant many-to-many
- **Books**: With categories, libraries relationship, and rental tracking
- **Articles**: With categories, author relationship, and publication status
- **Chat**: Rooms and messages for real-time communication
- **Permissions**: Granular role-based access control

### Key Models
- `User`: Extended with roles, permissions, and relationships
- `Event`: Event management with participants and addresses
- `Book`/`BookRental`: Library system with availability tracking
- `Article`: Content management with publication workflow
- `ChatRoom`/`ChatMessage`: Messaging system

### Controllers
- Resource controllers following RESTful patterns
- Proper authorization middleware
- Comprehensive validation
- Inertia responses with proper data loading

## Permissions System

### Roles
- **admin**: Full access to all features
- **project-manager**: Event and project management
- **event-manager**: Event management only
- **writer**: Article creation and management
- **member**: Basic access (view, participate, rent)

### Key Permissions
- **Events**: view, create, edit, delete events
- **Books**: view books, manage library, rent books
- **Articles**: view, create, edit, delete articles
- **Chat**: use chat functionality
- **General**: view departments, programs, stocks

## Frontend Structure

### Components
- **Layouts**: DashboardLayout with sidebar navigation
- **Pages**: Organized by feature (Events, Books, Articles, Chat)
- **Components**: Reusable UI components (Carousel, FeatureCard, etc.)
- **Utilities**: Theme switcher, language switcher, internationalization

### Routing
- All routes protected by authentication
- Permission-based access control
- RESTful resource routes with additional custom endpoints

## Features Implemented

### ✅ Completed Features
1. **Project Setup**: Laravel + Inertia + React + TypeScript configuration
2. **Authentication**: Laravel Breeze integration
3. **Database**: All migrations and model relationships
4. **Permissions**: Roles and permissions seeder
5. **Landing Page**: Carousel and feature showcase
6. **Dashboard**: Statistics, quick actions, recent activity
7. **Event Management**: Full CRUD with participation system
8. **Book System**: Library management with rental functionality
9. **Article System**: Content creation with categories and status
10. **Chat System**: Real-time messaging with rooms
11. **Internationalization**: Multi-language support (FR/EN/DE)
12. **Theme System**: Dark/Light mode with system preference
13. **Testing**: Comprehensive feature and unit tests

### 🧪 Testing Coverage
- **Feature Tests**: EventController, BookController with permissions
- **Unit Tests**: User model, Event model relationships
- **Authorization**: Permission-based access testing
- **Validation**: Form validation and business logic testing

### 🔍 Monitoring & Debugging
1. **Sentry Integration**: Real-time error tracking and performance monitoring
   - Automatic exception capturing
   - User context tracking
   - Performance traces
   - Breadcrumbs for debugging
   - See `SENTRY.md` for complete documentation
2. **Activity Log**: Track all model changes with Spatie Activity Log
   - All 52 models configured with logging
   - Tracks create, update, and delete operations
   - Only logs changed attributes
   - Complete audit trail available

## Development Guidelines

### Code Style
- Follow Laravel and React best practices
- Use TypeScript interfaces for type safety
- Implement proper error handling
- Use consistent naming conventions
- Comment complex business logic

### UI/UX Guidelines
- **NEVER use native `confirm()` or `window.confirm()` for delete confirmations**
- Always use the `DeleteConfirmationDialog` component from `/resources/js/Components/ui/delete-confirmation-dialog.tsx`
- **NEVER use native `alert()` for notifications**
- Always use `toast` from `sonner` library for success/error/warning/info messages
- Toasts are automatically styled for dark mode and positioned at top-right
- This provides a consistent, accessible, and visually appealing user experience
- See `/UI_GUIDELINES.md` for detailed implementation examples

### Security
- All routes protected by authentication middleware
- Permission-based authorization on controllers
- Input validation on all forms
- CSRF protection enabled
- No secrets in version control

### Performance
- Eager loading relationships to avoid N+1 queries
- Proper database indexing
- Image optimization for uploads
- Lazy loading for large datasets
- Caching strategies for static content

## Deployment Notes

### Environment Setup
- Configure database connection
- Set up file storage (public disk)
- Configure mail settings for notifications
- Set proper APP_ENV and APP_DEBUG values

### Production Checklist
- Run `php artisan optimize`
- Run `npm run build`
- Set up proper file permissions
- Configure web server (Nginx/Apache)
- Set up SSL certificates
- Configure backup strategies

## Troubleshooting

### Common Issues
- **NPM dependency conflicts**: Use `--legacy-peer-deps` flag
- **Permission errors**: Check file permissions and ownership
- **Database issues**: Run migrations and seeders
- **Cache issues**: Clear all Laravel caches

### Debug Commands
```bash
# View routes
php artisan route:list

# View permissions
php artisan permission:show

# Check model relationships
php artisan tinker
```

This application provides a solid foundation for organizational management with proper security, scalability, and maintainability considerations.