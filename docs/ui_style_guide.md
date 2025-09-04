# 🎨 Dokterku UI Style Guide

## Design System Overview

This comprehensive style guide documents the Dokterku clinic application's unified design system, implementing consistent visual standards across all 5 Filament panels with mobile-first responsive design.

## 🎯 Design Principles

### 1. **Mobile-First Approach**
- Start with mobile design and progressively enhance for larger screens
- Touch-friendly interface with minimum 44px touch targets
- Optimized performance for mobile healthcare workers

### 2. **Accessibility Priority**
- WCAG 2.1 AA compliance
- High contrast mode support
- Reduced motion preferences respected
- Screen reader optimized

### 3. **Consistency Across Panels**
- Unified color palette across all 5 panels
- Standardized navigation patterns
- Consistent component behavior

### 4. **Healthcare-Focused UX**
- Medical emoji and icons for intuitive navigation
- Quick actions for frequent tasks
- Clear visual hierarchy for critical information

## 🎨 Color System

### Primary Colors
```css
--primary: #667eea        /* Dokterku Primary Blue */
--primary-light: #8b94f0  /* Light variant */
--primary-dark: #4d5bc7   /* Dark variant */
--primary-hover: #5a6fd8  /* Hover state */
```

### Panel-Specific Colors
- **🏥 Admin Panel**: Primary Blue (#667eea)
- **📋 Petugas Panel**: Primary Blue (#667eea) - Consistent
- **👨‍⚕️ Paramedis Panel**: Accent Green (#10b981) - Mobile-optimized
- **💰 Bendahara Panel**: Warning Orange (#fbbd23) - Financial focus
- **📊 Manajer Panel**: Secondary Purple (#764ba2) - Analytics focus

### Semantic Colors
```css
--success: #10b981    /* Green for success states */
--warning: #fbbd23    /* Orange for warnings */
--error: #ef4444      /* Red for errors */
--info: #3abff8       /* Blue for information */
```

### Neutral Colors
```css
--neutral: #3d4451         /* Primary text */
--neutral-light: #6b7280   /* Secondary text */
--neutral-dark: #1f2937    /* Headings */
--surface: #ffffff         /* Background */
--surface-light: #f9fafb   /* Light backgrounds */
--surface-dark: #f3f4f6    /* Subtle backgrounds */
```

## 🔤 Typography

### Font System
- **Primary Font**: Figtree (Google Fonts)
- **Fallback**: System font stack for performance

### Type Scale
```css
--font-size-xs: 0.75rem     /* 12px - Small labels */
--font-size-sm: 0.875rem    /* 14px - Secondary text */
--font-size-base: 1rem      /* 16px - Body text */
--font-size-lg: 1.125rem    /* 18px - Subheadings */
--font-size-xl: 1.25rem     /* 20px - Headings */
--font-size-2xl: 1.5rem     /* 24px - Large headings */
--font-size-3xl: 1.875rem   /* 30px - Page titles */
--font-size-4xl: 2.25rem    /* 36px - Hero text */
```

### Font Weights
```css
--font-weight-light: 300    /* Light text */
--font-weight-normal: 400   /* Body text */
--font-weight-medium: 500   /* Emphasis */
--font-weight-semibold: 600 /* Subheadings */
--font-weight-bold: 700     /* Headings */
```

### Mobile Typography
```css
--mobile-font-xs: 0.75rem   /* Mobile small */
--mobile-font-sm: 0.875rem  /* Mobile secondary */
--mobile-font-base: 1rem    /* Mobile body */
--mobile-font-lg: 1.125rem  /* Mobile subheadings */
--mobile-font-xl: 1.25rem   /* Mobile headings */
```

## 📏 Spacing System

### Base Spacing Scale
```css
--spacing-xs: 0.25rem   /* 4px - Tight spacing */
--spacing-sm: 0.5rem    /* 8px - Small spacing */
--spacing-md: 1rem      /* 16px - Default spacing */
--spacing-lg: 1.5rem    /* 24px - Large spacing */
--spacing-xl: 2rem      /* 32px - Extra large */
--spacing-2xl: 3rem     /* 48px - Section spacing */
--spacing-3xl: 4rem     /* 64px - Page spacing */
--spacing-4xl: 6rem     /* 96px - Hero spacing */
```

### Mobile Spacing
```css
--mobile-padding: 1rem      /* Mobile container padding */
--mobile-margin: 0.5rem     /* Mobile element margins */
--mobile-gap: 0.75rem       /* Mobile grid/flex gaps */
--touch-target-min: 44px    /* Minimum touch target */
--touch-spacing: 8px        /* Touch target spacing */
```

## 🔘 Component System

### Button Variants

#### Primary Button
```css
.btn-primary {
  background-color: var(--primary);
  color: var(--text-inverse);
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
  font-weight: var(--font-weight-medium);
  transition: all 0.2s ease;
}
```

#### Mobile Button
```css
.mobile-button {
  min-height: var(--touch-target-min);
  padding: 0.75rem 1.5rem;
  font-size: var(--mobile-font-base);
  touch-action: manipulation;
}
```

### Card Components

#### Standard Card
```css
.mobile-card {
  background: var(--surface);
  border: 1px solid var(--neutral-light);
  border-radius: var(--radius-lg);
  padding: 1rem;
  box-shadow: var(--shadow-sm);
}
```

### Form Elements

#### Input Fields
```css
.mobile-form-input {
  min-height: var(--touch-target-min);
  padding: 0.75rem;
  border: 1px solid var(--neutral-light);
  border-radius: var(--radius-md);
  font-size: var(--mobile-font-base);
}
```

## 🌐 Responsive Breakpoints

### Mobile-First Breakpoints
```css
/* Small phones (up to 375px) */
@media (max-width: 375px) { /* Extra small adjustments */ }

/* Medium phones (376px to 640px) */
@media (min-width: 376px) and (max-width: 640px) { /* Standard mobile */ }

/* Large phones/small tablets (641px to 768px) */
@media (min-width: 641px) and (max-width: 768px) { /* Large mobile */ }

/* Tablets (769px and up) */
@media (min-width: 769px) { /* Tablet and desktop */ }
```

### Custom Tailwind Breakpoints
```javascript
screens: {
  'xs': '475px',
  'mobile': {'max': '640px'},
  'tablet': {'min': '641px', 'max': '1024px'},
  'desktop': {'min': '1025px'},
}
```

## 🎭 Icon System

### Standardized Medical Emojis
```css
/* Navigation Icons */
.emoji-dashboard::before { content: "🏠"; }  /* Dashboard */
.emoji-users::before { content: "👥"; }      /* User Management */
.emoji-doctor::before { content: "👨‍⚕️"; }   /* Doctors */
.emoji-nurse::before { content: "👩‍⚕️"; }    /* Nurses */
.emoji-patient::before { content: "🤒"; }    /* Patients */

/* Medical Icons */
.emoji-medical-record::before { content: "📋"; }  /* Records */
.emoji-prescription::before { content: "💊"; }    /* Medications */
.emoji-procedure::before { content: "🔬"; }       /* Procedures */
.emoji-treatment::before { content: "🩺"; }       /* Treatments */

/* Financial Icons */
.emoji-money::before { content: "💰"; }       /* Money */
.emoji-revenue::before { content: "💵"; }     /* Revenue */
.emoji-expense::before { content: "💸"; }     /* Expenses */
.emoji-validation::before { content: "✅"; }   /* Validation */

/* Status Icons */
.emoji-success::before { content: "✅"; }     /* Success */
.emoji-warning::before { content: "⚠️"; }     /* Warning */
.emoji-error::before { content: "❌"; }       /* Error */
.emoji-pending::before { content: "⏳"; }     /* Pending */
```

### Icon Sizes
```css
.icon-xs { width: 1rem; height: 1rem; }      /* 16px */
.icon-sm { width: 1.25rem; height: 1.25rem; } /* 20px */
.icon-md { width: 1.5rem; height: 1.5rem; }   /* 24px */
.icon-lg { width: 2rem; height: 2rem; }       /* 32px */
.icon-xl { width: 2.5rem; height: 2.5rem; }   /* 40px */
```

## 🔄 Loading States

### Loading Spinner
```css
.loading-spinner {
  width: 1.5rem;
  height: 1.5rem;
  border: 2px solid rgba(102, 126, 234, 0.3);
  border-top-color: var(--primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}
```

### Progress Bar
```css
.progress-bar {
  width: 100%;
  height: 0.5rem;
  background-color: var(--surface-dark);
  border-radius: var(--radius-full);
  overflow: hidden;
}

.progress-bar-fill {
  height: 100%;
  background-color: var(--primary);
  transition: width 0.3s ease;
}
```

### Skeleton Loading
```css
.skeleton {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s infinite;
  border-radius: var(--radius-md);
}
```

## 📱 Mobile Navigation

### Bottom Navigation
```css
.mobile-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--surface);
  border-top: 1px solid var(--neutral-light);
  padding: 0.5rem;
  z-index: 1000;
}
```

### Navigation Items
```css
.mobile-nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: var(--touch-target-min);
  color: var(--text-secondary);
}

.mobile-nav-item.active {
  color: var(--primary);
}
```

## 🎨 Panel Branding

### Updated Panel Names
- **🏥 Dokterku Admin** - Full system administration
- **📋 Dokterku - Petugas** - Data entry and daily operations
- **👨‍⚕️ Dokterku - Paramedis** - Mobile-optimized for field work
- **💰 Dokterku - Bendahara** - Financial validation and management
- **📊 Dokterku - Manajer** - Analytics and reporting

### Navigation Groups with Icons
Each panel now includes standardized navigation groups with appropriate medical emojis and Heroicons for visual consistency.

## 🌙 Dark Mode Support

### CSS Custom Properties
The system supports automatic dark mode detection and manual toggle:

```css
@media (prefers-color-scheme: dark) {
  :root {
    --surface: #1f2937;
    --surface-light: #374151;
    --text-primary: #f9fafb;
    --text-secondary: #d1d5db;
  }
}
```

## ♿ Accessibility Features

### Touch Targets
- Minimum 44px touch targets for mobile
- Adequate spacing between interactive elements
- Touch action optimization for mobile devices

### Color Contrast
- WCAG AA compliant color ratios
- High contrast mode support
- Clear visual hierarchy

### Motion Preferences
```css
@media (prefers-reduced-motion: reduce) {
  * {
    animation: none !important;
    transition: none !important;
  }
}
```

## 📱 Mobile-First Features

### Safe Area Support
```css
padding-bottom: env(safe-area-inset-bottom);
```

### Touch Optimization
```css
touch-action: manipulation; /* Prevents zoom on double-tap */
-webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
```

### Viewport Meta Tag
```html
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
```

## 🚀 Implementation Guidelines

### CSS Architecture
1. **design-system.css** - Core design tokens and variables
2. **loading-system.css** - Loading states and progress indicators
3. **mobile-responsive.css** - Mobile-first responsive components

### JavaScript Integration
- **loading-manager.js** - Handles loading states and user feedback
- Integrates with Filament for seamless UX

### Filament Panel Integration
- Consistent color schemes across all panels
- Unified navigation patterns with medical emojis
- Mobile-optimized components for Paramedis panel

## 📏 Component Guidelines

### Cards
- Use for grouping related information
- Include clear headers and actions
- Maintain consistent padding and spacing

### Forms
- Label-above-input pattern for mobile
- Adequate spacing between form fields
- Clear validation states

### Lists
- Touch-friendly item heights
- Clear visual separation
- Consistent icon usage

## 🎯 Best Practices

### Performance
- Mobile-first CSS loading
- Optimized image sizes
- Minimal JavaScript for core functionality

### User Experience
- Clear visual feedback for all interactions
- Consistent navigation patterns
- Accessible color combinations

### Maintenance
- Use CSS custom properties for easy theming
- Consistent naming conventions
- Modular CSS architecture

This style guide ensures consistent, accessible, and mobile-optimized user experience across all Dokterku clinic application panels.