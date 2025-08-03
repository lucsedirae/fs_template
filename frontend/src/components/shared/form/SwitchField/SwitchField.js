import React from 'react';
import './SwitchField.css';

/**
 * SwitchField Component
 * 
 * A reusable switch/checkbox component with consistent styling and behavior.
 * Uses Bootstrap's form-switch styling for modern toggle appearance.
 * 
 * @param {Object} props
 * @param {string} props.id - Input field ID (required)
 * @param {string} props.label - Label text for the switch
 * @param {boolean} props.checked - Whether switch is checked
 * @param {Function} props.onChange - Change handler function
 * @param {boolean} props.disabled - Whether switch is disabled
 * @param {string} props.helpText - Help text to display below switch
 * @param {string} props.error - Error message to display
 * @param {string} props.className - Additional CSS classes
 * @param {string} props.size - Bootstrap size ('sm', 'lg')
 * @param {string} props.variant - Switch variant ('switch' or 'checkbox')
 */
const SwitchField = ({
  id,
  label,
  checked,
  onChange,
  disabled = false,
  helpText,
  error,
  className = '',
  size,
  variant = 'switch',
  ...rest
}) => {
  // Determine container classes
  const containerClasses = [
    'form-check',
    variant === 'switch' && 'form-switch',
    size && `form-check-${size}`,
    className
  ].filter(Boolean).join(' ');

  // Determine input classes
  const inputClasses = [
    'form-check-input',
    error && 'is-invalid'
  ].filter(Boolean).join(' ');

  return (
    <div className="mb-3">
      <div className={containerClasses}>
        {/* Switch Input */}
        <input
          type="checkbox"
          className={inputClasses}
          id={id}
          checked={checked}
          onChange={onChange}
          disabled={disabled}
          {...rest}
        />

        {/* Label */}
        {label && (
          <label className="form-check-label" htmlFor={id}>
            {label}
          </label>
        )}
      </div>

      {/* Help Text */}
      {helpText && !error && (
        <div className="form-text mt-1">
          {helpText}
        </div>
      )}

      {/* Error Message */}
      {error && (
        <div className="invalid-feedback d-block">
          {error}
        </div>
      )}
    </div>
  );
};

export default SwitchField;