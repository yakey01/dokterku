/**
 * Accessible Form Components
 * WCAG 2.1 AA compliant form elements with proper labeling, error handling, and validation
 */

import React, { useId, useState } from 'react';
import { AlertCircle, CheckCircle, Eye, EyeOff } from 'lucide-react';

interface AccessibleInputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label: string;
  error?: string;
  success?: string;
  description?: string;
  required?: boolean;
  showPasswordToggle?: boolean;
  containerClassName?: string;
}

interface AccessibleTextareaProps extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
  label: string;
  error?: string;
  success?: string;
  description?: string;
  required?: boolean;
  containerClassName?: string;
}

interface AccessibleSelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  label: string;
  error?: string;
  success?: string;
  description?: string;
  required?: boolean;
  options: { value: string; label: string; disabled?: boolean }[];
  placeholder?: string;
  containerClassName?: string;
}

/**
 * Accessible Input Field
 */
export const AccessibleInput: React.FC<AccessibleInputProps> = ({
  label,
  error,
  success,
  description,
  required = false,
  showPasswordToggle = false,
  containerClassName = '',
  type = 'text',
  id,
  className = '',
  ...props
}) => {
  const fieldId = useId();
  const actualId = id || fieldId;
  const descriptionId = description ? `${actualId}-description` : undefined;
  const errorId = error ? `${actualId}-error` : undefined;
  const successId = success ? `${actualId}-success` : undefined;

  const [showPassword, setShowPassword] = useState(false);
  const inputType = showPasswordToggle && type === 'password' 
    ? (showPassword ? 'text' : 'password') 
    : type;

  const describedByIds = [descriptionId, errorId, successId].filter(Boolean).join(' ') || undefined;

  return (
    <div className={`form-field ${containerClassName}`}>
      {/* Label */}
      <label 
        htmlFor={actualId}
        className={`form-label ${error ? 'text-error-accessible' : 'text-high-contrast'}`}
      >
        {label}
        {required && (
          <span className="text-error-accessible ml-1" aria-label="required field">
            *
          </span>
        )}
      </label>

      {/* Description */}
      {description && (
        <div 
          id={descriptionId}
          className="text-sm text-high-contrast-muted mt-1"
        >
          {description}
        </div>
      )}

      {/* Input Container */}
      <div className="relative mt-2">
        <input
          {...props}
          type={inputType}
          id={actualId}
          className={`
            form-input input-accessible w-full
            ${error ? 'border-red-500 focus:border-red-500' : 'focus:border-blue-500'}
            ${success ? 'border-green-500' : ''}
            ${showPasswordToggle ? 'pr-12' : ''}
            ${className}
          `}
          aria-invalid={error ? 'true' : 'false'}
          aria-describedby={describedByIds}
          aria-required={required}
        />

        {/* Password Toggle */}
        {showPasswordToggle && type === 'password' && (
          <button
            type="button"
            onClick={() => setShowPassword(!showPassword)}
            className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white focus-outline touch-target"
            aria-label={showPassword ? 'Hide password' : 'Show password'}
            tabIndex={0}
          >
            {showPassword ? (
              <EyeOff className="w-5 h-5" aria-hidden="true" />
            ) : (
              <Eye className="w-5 h-5" aria-hidden="true" />
            )}
          </button>
        )}

        {/* Status Icons */}
        {(error || success) && !showPasswordToggle && (
          <div className="absolute right-3 top-1/2 transform -translate-y-1/2">
            {error && (
              <AlertCircle 
                className="w-5 h-5 text-red-500" 
                aria-hidden="true"
              />
            )}
            {success && (
              <CheckCircle 
                className="w-5 h-5 text-green-500" 
                aria-hidden="true"
              />
            )}
          </div>
        )}
      </div>

      {/* Error Message */}
      {error && (
        <div 
          id={errorId}
          className="error-message mt-2 flex items-start space-x-2"
          role="alert"
          aria-live="polite"
        >
          <AlertCircle className="w-4 h-4 mt-0.5 flex-shrink-0" aria-hidden="true" />
          <span>{error}</span>
        </div>
      )}

      {/* Success Message */}
      {success && (
        <div 
          id={successId}
          className="success-message mt-2 flex items-start space-x-2"
          role="status"
          aria-live="polite"
        >
          <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" aria-hidden="true" />
          <span>{success}</span>
        </div>
      )}
    </div>
  );
};

/**
 * Accessible Textarea Field
 */
export const AccessibleTextarea: React.FC<AccessibleTextareaProps> = ({
  label,
  error,
  success,
  description,
  required = false,
  containerClassName = '',
  id,
  className = '',
  rows = 3,
  ...props
}) => {
  const fieldId = useId();
  const actualId = id || fieldId;
  const descriptionId = description ? `${actualId}-description` : undefined;
  const errorId = error ? `${actualId}-error` : undefined;
  const successId = success ? `${actualId}-success` : undefined;

  const describedByIds = [descriptionId, errorId, successId].filter(Boolean).join(' ') || undefined;

  return (
    <div className={`form-field ${containerClassName}`}>
      {/* Label */}
      <label 
        htmlFor={actualId}
        className={`form-label ${error ? 'text-error-accessible' : 'text-high-contrast'}`}
      >
        {label}
        {required && (
          <span className="text-error-accessible ml-1" aria-label="required field">
            *
          </span>
        )}
      </label>

      {/* Description */}
      {description && (
        <div 
          id={descriptionId}
          className="text-sm text-high-contrast-muted mt-1"
        >
          {description}
        </div>
      )}

      {/* Textarea */}
      <div className="relative mt-2">
        <textarea
          {...props}
          id={actualId}
          rows={rows}
          className={`
            form-input input-accessible w-full resize-y
            ${error ? 'border-red-500 focus:border-red-500' : 'focus:border-blue-500'}
            ${success ? 'border-green-500' : ''}
            ${className}
          `}
          aria-invalid={error ? 'true' : 'false'}
          aria-describedby={describedByIds}
          aria-required={required}
        />

        {/* Status Icons */}
        {(error || success) && (
          <div className="absolute right-3 top-3">
            {error && (
              <AlertCircle 
                className="w-5 h-5 text-red-500" 
                aria-hidden="true"
              />
            )}
            {success && (
              <CheckCircle 
                className="w-5 h-5 text-green-500" 
                aria-hidden="true"
              />
            )}
          </div>
        )}
      </div>

      {/* Error Message */}
      {error && (
        <div 
          id={errorId}
          className="error-message mt-2 flex items-start space-x-2"
          role="alert"
          aria-live="polite"
        >
          <AlertCircle className="w-4 h-4 mt-0.5 flex-shrink-0" aria-hidden="true" />
          <span>{error}</span>
        </div>
      )}

      {/* Success Message */}
      {success && (
        <div 
          id={successId}
          className="success-message mt-2 flex items-start space-x-2"
          role="status"
          aria-live="polite"
        >
          <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" aria-hidden="true" />
          <span>{success}</span>
        </div>
      )}
    </div>
  );
};

/**
 * Accessible Select Field
 */
export const AccessibleSelect: React.FC<AccessibleSelectProps> = ({
  label,
  error,
  success,
  description,
  required = false,
  options,
  placeholder,
  containerClassName = '',
  id,
  className = '',
  ...props
}) => {
  const fieldId = useId();
  const actualId = id || fieldId;
  const descriptionId = description ? `${actualId}-description` : undefined;
  const errorId = error ? `${actualId}-error` : undefined;
  const successId = success ? `${actualId}-success` : undefined;

  const describedByIds = [descriptionId, errorId, successId].filter(Boolean).join(' ') || undefined;

  return (
    <div className={`form-field ${containerClassName}`}>
      {/* Label */}
      <label 
        htmlFor={actualId}
        className={`form-label ${error ? 'text-error-accessible' : 'text-high-contrast'}`}
      >
        {label}
        {required && (
          <span className="text-error-accessible ml-1" aria-label="required field">
            *
          </span>
        )}
      </label>

      {/* Description */}
      {description && (
        <div 
          id={descriptionId}
          className="text-sm text-high-contrast-muted mt-1"
        >
          {description}
        </div>
      )}

      {/* Select */}
      <div className="relative mt-2">
        <select
          {...props}
          id={actualId}
          className={`
            form-input input-accessible w-full
            ${error ? 'border-red-500 focus:border-red-500' : 'focus:border-blue-500'}
            ${success ? 'border-green-500' : ''}
            ${className}
          `}
          aria-invalid={error ? 'true' : 'false'}
          aria-describedby={describedByIds}
          aria-required={required}
        >
          {placeholder && (
            <option value="" disabled>
              {placeholder}
            </option>
          )}
          {options.map((option) => (
            <option 
              key={option.value} 
              value={option.value} 
              disabled={option.disabled}
            >
              {option.label}
            </option>
          ))}
        </select>

        {/* Status Icons */}
        {(error || success) && (
          <div className="absolute right-8 top-1/2 transform -translate-y-1/2">
            {error && (
              <AlertCircle 
                className="w-5 h-5 text-red-500" 
                aria-hidden="true"
              />
            )}
            {success && (
              <CheckCircle 
                className="w-5 h-5 text-green-500" 
                aria-hidden="true"
              />
            )}
          </div>
        )}
      </div>

      {/* Error Message */}
      {error && (
        <div 
          id={errorId}
          className="error-message mt-2 flex items-start space-x-2"
          role="alert"
          aria-live="polite"
        >
          <AlertCircle className="w-4 h-4 mt-0.5 flex-shrink-0" aria-hidden="true" />
          <span>{error}</span>
        </div>
      )}

      {/* Success Message */}
      {success && (
        <div 
          id={successId}
          className="success-message mt-2 flex items-start space-x-2"
          role="status"
          aria-live="polite"
        >
          <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" aria-hidden="true" />
          <span>{success}</span>
        </div>
      )}
    </div>
  );
};

/**
 * Accessible Form Container
 */
interface AccessibleFormProps {
  children: React.ReactNode;
  onSubmit?: (e: React.FormEvent) => void;
  title?: string;
  description?: string;
  className?: string;
  noValidate?: boolean;
}

export const AccessibleForm: React.FC<AccessibleFormProps> = ({
  children,
  onSubmit,
  title,
  description,
  className = '',
  noValidate = true
}) => {
  return (
    <form 
      onSubmit={onSubmit}
      className={`space-y-6 ${className}`}
      noValidate={noValidate}
      role="form"
      aria-label={title}
    >
      {title && (
        <div className="mb-6">
          <h2 className="text-xl font-bold text-high-contrast mb-2">
            {title}
          </h2>
          {description && (
            <p className="text-high-contrast-muted">
              {description}
            </p>
          )}
        </div>
      )}
      {children}
    </form>
  );
};

/**
 * Accessible Submit Button
 */
interface AccessibleSubmitButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  loading?: boolean;
  loadingText?: string;
  variant?: 'primary' | 'secondary' | 'success' | 'warning' | 'error';
  size?: 'sm' | 'md' | 'lg';
}

export const AccessibleSubmitButton: React.FC<AccessibleSubmitButtonProps> = ({
  children,
  loading = false,
  loadingText = 'Loading...',
  variant = 'primary',
  size = 'md',
  disabled,
  className = '',
  ...props
}) => {
  const baseClasses = 'font-medium rounded-lg transition-colors focus-outline touch-target disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2';
  
  const variantClasses = {
    primary: 'btn-primary-accessible',
    secondary: 'btn-high-contrast',
    success: 'btn-success-accessible',
    warning: 'btn-warning-accessible',
    error: 'btn-error-accessible'
  };

  const sizeClasses = {
    sm: 'px-3 py-2 text-sm',
    md: 'px-4 py-2.5 text-base',
    lg: 'px-6 py-3 text-lg'
  };

  return (
    <button
      {...props}
      type="submit"
      disabled={disabled || loading}
      className={`${baseClasses} ${variantClasses[variant]} ${sizeClasses[size]} ${className}`}
      aria-describedby={loading ? 'loading-state' : undefined}
    >
      {loading && (
        <div 
          className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" 
          aria-hidden="true"
        />
      )}
      <span>{loading ? loadingText : children}</span>
      {loading && (
        <span id="loading-state" className="sr-only">
          Form is being submitted, please wait
        </span>
      )}
    </button>
  );
};

export default {
  AccessibleInput,
  AccessibleTextarea,
  AccessibleSelect,
  AccessibleForm,
  AccessibleSubmitButton
};