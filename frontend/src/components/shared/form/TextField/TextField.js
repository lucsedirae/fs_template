import React from 'react';
import './TextField.css';

/**
 * TextField Component
 * 
 * A reusable text input field component with consistent styling and behavior.
 * Supports various input types and validation states.
 * 
 * @param {Object} props
 * @param {string} props.id - Input field ID (required)
 * @param {string} props.label - Label text for the input
 * @param {string} props.value - Current input value
 * @param {Function} props.onChange - Change handler function
 * @param {string} props.type - Input type (default: 'text')
 * @param {string} props.placeholder - Placeholder text
 * @param {boolean} props.disabled - Whether input is disabled
 * @param {boolean} props.required - Whether input is required
 * @param {string} props.helpText - Help text to display below input
 * @param {string} props.error - Error message to display
 * @param {string} props.className - Additional CSS classes
 * @param {number} props.maxLength - Maximum input length
 * @param {string} props.size - Bootstrap input size ('sm', 'lg')
 */
const TextField = ({
  id,
  label,
  value,
  onChange,
  type = 'text',
  placeholder,
  disabled = false,
  required = false,
  helpText,
  error,
  className = '',
  maxLength,
  size,
  ...rest
}) => {
  // Determine input classes based on validation state
  const inputClasses = [
    'form-control',
    size && `form-control-${size}`,
    error && 'is-invalid',
    className
  ].filter(Boolean).join(' ');

  return (
    <div className="mb-3">
      {/* Label */}
      {label && (
        <label htmlFor={id} className="form-label">
          {label}
          {required && <span className="text-danger ms-1">*</span>}
        </label>
      )}

      {/* Input Field */}
      <input
        type={type}
        className={inputClasses}
        id={id}
        value={value}
        onChange={onChange}
        placeholder={placeholder}
        disabled={disabled}
        required={required}
        maxLength={maxLength}
        {...rest}
      />

      {/* Help Text */}
      {helpText && !error && (
        <div className="form-text">
          {helpText}
        </div>
      )}

      {/* Error Message */}
      {error && (
        <div className="invalid-feedback">
          {error}
        </div>
      )}
    </div>
  );
};

export default TextField;