# TypeScript `any` Type Elimination Guide

## Overview

This document provides guidelines for eliminating `any` types from the AIG-App codebase to improve type safety and code quality.

## Why Eliminate `any` Types?

1. **Type Safety**: `any` bypasses TypeScript's type checking
2. **IDE Support**: Proper types enable better autocomplete and error detection
3. **Refactoring**: Strong types make refactoring safer
4. **Documentation**: Types serve as inline documentation
5. **Error Prevention**: Catch errors at compile time instead of runtime

## Current Status

As of the scan date, there are **32 files** containing `any` types that need attention.

## Common Patterns and Solutions

### 1. Event Handlers

❌ **Before:**
```typescript
const handleChange = (e: any) => {
    setValue(e.target.value);
};
```

✅ **After:**
```typescript
const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setValue(e.target.value);
};
```

### 2. API Responses

❌ **Before:**
```typescript
const fetchData = async (): Promise<any> => {
    const response = await fetch('/api/events');
    return response.json();
};
```

✅ **After:**
```typescript
interface EventResponse {
    data: Event[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

const fetchData = async (): Promise<EventResponse> => {
    const response = await fetch('/api/events');
    return response.json();
};
```

### 3. Callback Props

❌ **Before:**
```typescript
interface Props {
    onUploadComplete?: (files: any[]) => void;
    onUploadError?: (error: any) => void;
}
```

✅ **After:**
```typescript
interface UploadedFile {
    id: string;
    name: string;
    size: number;
    type: string;
    url: string;
}

interface UploadError {
    message: string;
    code: string;
    file?: string;
}

interface Props {
    onUploadComplete?: (files: UploadedFile[]) => void;
    onUploadError?: (error: UploadError) => void;
}
```

### 4. Form Data

❌ **Before:**
```typescript
const [formData, setFormData] = useState<any>({});
```

✅ **After:**
```typescript
interface EventFormData {
    title: string;
    description: string;
    start_date: string;
    end_date: string;
    location?: string;
    max_participants?: number;
}

const [formData, setFormData] = useState<EventFormData>({
    title: '',
    description: '',
    start_date: '',
    end_date: '',
});
```

### 5. Library Prop Types

❌ **Before:**
```typescript
// Video.js player
const player: any = useRef(null);
```

✅ **After:**
```typescript
import videojs from 'video.js';

const player = useRef<ReturnType<typeof videojs> | null>(null);
```

### 6. Third-Party Library Integrations

For third-party libraries without TypeScript definitions:

❌ **Before:**
```typescript
const uppyInstance: any = Uppy();
```

✅ **After:**
```typescript
// Install type definitions
// npm install --save-dev @types/uppy

import Uppy from '@uppy/core';

const uppyInstance: Uppy.Uppy = Uppy();
```

If types don't exist, create a declaration file:

```typescript
// types/uppy.d.ts
declare module '@uppy/core' {
    export interface UppyFile {
        id: string;
        name: string;
        type: string;
        size: number;
        source: string;
        data: File | Blob;
    }

    export default class Uppy {
        constructor(options?: UppyOptions);
        use(plugin: any, options?: any): Uppy;
        upload(): Promise<UppyUploadResult>;
        on(event: string, callback: (file: UppyFile) => void): void;
    }
}
```

### 7. Generic Types

❌ **Before:**
```typescript
const processData = (data: any): any => {
    return data.map((item: any) => item.value);
};
```

✅ **After:**
```typescript
interface DataItem {
    value: string;
    label: string;
}

const processData = <T extends DataItem>(data: T[]): string[] => {
    return data.map((item) => item.value);
};
```

### 8. Unknown Type (When Type is Truly Unknown)

For cases where the type is genuinely unknown at compile time, use `unknown` instead of `any`:

❌ **Before:**
```typescript
const parseJson = (json: string): any => {
    return JSON.parse(json);
};
```

✅ **After:**
```typescript
const parseJson = (json: string): unknown => {
    return JSON.parse(json);
};

// Force type narrowing when using
const data = parseJson(jsonString);
if (isEvent(data)) {
    // TypeScript now knows data is Event
    console.log(data.title);
}

// Type guard
function isEvent(obj: unknown): obj is Event {
    return (
        typeof obj === 'object' &&
        obj !== null &&
        'title' in obj &&
        'start_date' in obj
    );
}
```

### 9. Record Types

❌ **Before:**
```typescript
const config: any = {
    development: { apiUrl: 'http://localhost' },
    production: { apiUrl: 'https://api.example.com' },
};
```

✅ **After:**
```typescript
interface EnvironmentConfig {
    apiUrl: string;
}

const config: Record<string, EnvironmentConfig> = {
    development: { apiUrl: 'http://localhost' },
    production: { apiUrl: 'https://api.example.com' },
};
```

### 10. Utility Types

Use TypeScript utility types for common patterns:

```typescript
// Partial - Make all properties optional
type PartialUser = Partial<User>;

// Pick - Select specific properties
type UserName = Pick<User, 'first_name' | 'last_name'>;

// Omit - Exclude specific properties
type UserWithoutPassword = Omit<User, 'password'>;

// ReturnType - Extract return type of function
type FetchResult = ReturnType<typeof fetchData>;

// Parameters - Extract parameters of function
type FetchParams = Parameters<typeof fetchData>;
```

## Priority Levels

### High Priority (Fix Immediately)
1. **API Response Types** - Type safety for backend communication
2. **Form Data** - Prevent submission errors
3. **User/Auth Types** - Critical for security
4. **Database Model Types** - Core data structures

### Medium Priority (Fix Soon)
1. **Component Props** - Improve component interfaces
2. **Event Handlers** - Better developer experience
3. **State Management** - Clearer data flow

### Low Priority (Fix When Convenient)
1. **Third-Party Library Wrappers** - If types not available
2. **Utility Functions** - Internal helpers
3. **Test Files** - Less critical for type safety

## Files Requiring Attention

### Critical Files
- `/resources/js/Types/index.ts` ✅ **FIXED**
- `/resources/js/Pages/Events/Index.tsx`
- `/resources/js/Pages/Books/Index.tsx`
- `/resources/js/Pages/Articles/Index.tsx`

### Component Files
- `/resources/js/Components/UppyFileUploader.tsx`
- `/resources/js/Components/VideoJSPlayer.tsx`
- `/resources/js/Components/LoadingBar.tsx`

### Page Files
- `/resources/js/Pages/Profile/Partials/UpdateProfileInformationForm.tsx`
- `/resources/js/Pages/Messages/Index.tsx`
- `/resources/js/Pages/UserManagement/Index.tsx`

## Best Practices

### 1. Create Type Definitions Early
Define interfaces before implementing components:

```typescript
// Define types first
interface EventListProps {
    events: PaginatedData<Event>;
    onEventClick: (event: Event) => void;
}

// Then implement component
const EventList: React.FC<EventListProps> = ({ events, onEventClick }) => {
    // Implementation
};
```

### 2. Use Type Guards
```typescript
function isUser(obj: unknown): obj is User {
    return (
        typeof obj === 'object' &&
        obj !== null &&
        'id' in obj &&
        'email' in obj
    );
}
```

### 3. Leverage Type Inference
```typescript
// Let TypeScript infer when obvious
const count = 42; // TypeScript infers number
const name = 'John'; // TypeScript infers string

// Be explicit when not obvious
const parseValue = (input: string): number | null => {
    const parsed = parseInt(input);
    return isNaN(parsed) ? null : parsed;
};
```

### 4. Use Discriminated Unions
```typescript
type ApiResponse<T> =
    | { status: 'success'; data: T }
    | { status: 'error'; error: string }
    | { status: 'loading' };

const handleResponse = (response: ApiResponse<User>) => {
    switch (response.status) {
        case 'success':
            // TypeScript knows response.data exists
            console.log(response.data.name);
            break;
        case 'error':
            // TypeScript knows response.error exists
            console.error(response.error);
            break;
        case 'loading':
            // TypeScript knows no data or error
            break;
    }
};
```

### 5. Document Type Decisions
If you must use `any`, document why:

```typescript
interface Props {
    // Using any because third-party library doesn't provide types
    // and creating our own would be too complex
    // TODO: Replace when @types/library is available
    config: any; // eslint-disable-line @typescript-eslint/no-explicit-any
}
```

## Tools and Configuration

### ESLint Rules

Add to `.eslintrc.js`:

```javascript
module.exports = {
    rules: {
        '@typescript-eslint/no-explicit-any': 'error',
        '@typescript-eslint/no-unsafe-assignment': 'warn',
        '@typescript-eslint/no-unsafe-member-access': 'warn',
        '@typescript-eslint/no-unsafe-call': 'warn',
        '@typescript-eslint/no-unsafe-return': 'warn',
    },
};
```

### TypeScript Strict Mode

Enable in `tsconfig.json`:

```json
{
    "compilerOptions": {
        "strict": true,
        "noImplicitAny": true,
        "strictNullChecks": true,
        "strictFunctionTypes": true,
        "strictPropertyInitialization": true
    }
}
```

## Migration Strategy

### Phase 1: Stop the Bleeding (Week 1)
1. Fix all `any` types in `/resources/js/Types/` directory
2. Add ESLint rule to prevent new `any` types
3. Create type definitions for all models

### Phase 2: Core Functionality (Week 2-3)
1. Fix `any` types in:
   - Event-related components
   - Book-related components
   - Article-related components
   - User management components

### Phase 3: Supporting Features (Week 4-5)
1. Fix `any` types in:
   - Form components
   - Chat components
   - Utility components
   - Third-party integrations

### Phase 4: Polish (Week 6)
1. Enable strict mode in TypeScript
2. Fix any remaining warnings
3. Document any intentional `any` usage
4. Add automated checks to CI/CD

## Measuring Progress

Track progress with:

```bash
# Count remaining any types
grep -r ": any" resources/js --include="*.tsx" --include="*.ts" | wc -l

# Generate report
grep -rn ": any" resources/js --include="*.tsx" --include="*.ts" > any-types-report.txt
```

## Success Metrics

- **Current**: 32 files with `any` types
- **Target**: < 5 files with `any` types (only for third-party libraries)
- **Deadline**: 6 weeks from start date

## Resources

- [TypeScript Handbook](https://www.typescriptlang.org/docs/handbook/intro.html)
- [Type Challenges](https://github.com/type-challenges/type-challenges)
- [DefinitelyTyped](https://github.com/DefinitelyTyped/DefinitelyTyped)
- [React TypeScript Cheatsheet](https://react-typescript-cheatsheet.netlify.app/)

## Conclusion

Eliminating `any` types is a gradual process that significantly improves code quality and developer experience. By following this guide and implementing changes incrementally, the codebase will become more maintainable, type-safe, and easier to work with.
