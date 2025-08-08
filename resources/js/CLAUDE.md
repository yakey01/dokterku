# JavaScript/React Guidelines

## Overview
Frontend code for React components, TypeScript utilities, and interactive features.

## Tech Stack
- React 18+ with TypeScript
- Vite for bundling
- TailwindCSS for styling
- Lucide React for icons
- Recharts for data visualization
- Axios for API calls

## Component Structure
```typescript
// Always use TypeScript with proper interfaces
interface ComponentProps {
  data: DataType;
  onAction?: (id: string) => void;
}

// Prefer functional components with hooks
const MyComponent: React.FC<ComponentProps> = ({ data, onAction }) => {
  // Component logic
};
```

## State Management
- Use React hooks (useState, useEffect, useContext)
- Custom hooks for reusable logic (e.g., `useAuth`, `useApi`)
- Avoid prop drilling - use Context for cross-component state

## API Integration Pattern
```typescript
// Always use the unified auth system
import { unifiedAuth } from '@/utils/UnifiedAuth';

// API calls should handle errors gracefully
try {
  const response = await apiClient.get('/endpoint');
  // Handle success
} catch (error) {
  console.error('API Error:', error);
  // Show user-friendly error message
}
```

## Performance Rules
- Lazy load heavy components
- Memoize expensive computations
- Use React.memo for pure components
- Implement virtual scrolling for long lists
- Optimize images with proper sizing

## Mobile-First Design
- All components must be responsive
- Touch-friendly interactions (min 44px touch targets)
- Test on various screen sizes
- Progressive enhancement approach

## Common Patterns

### Dashboard Components
- Use grid layouts for widgets
- Implement skeleton loaders
- Real-time data updates where needed
- Proper error boundaries

### Form Handling
- Use controlled components
- Validate on blur and submit
- Show inline validation errors
- Provide helpful placeholders

## File Naming
- Components: PascalCase (e.g., `DashboardWidget.tsx`)
- Utilities: camelCase (e.g., `formatCurrency.ts`)
- Hooks: camelCase with 'use' prefix (e.g., `useLocalStorage.ts`)
- Types: PascalCase with 'Type' suffix (e.g., `UserType.ts`)