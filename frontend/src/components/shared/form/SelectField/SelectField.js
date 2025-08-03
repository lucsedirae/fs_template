import React from 'react';
import './SelectField.css';

/**
 * SelectField Component
 * 
 * A reusable select dropdown component with consistent styling and behavior.
 * Supports option groups and validation states.
 * 
 * @param {Object} props
 * @param {string} props.id - Select field ID (required)
 * @param {string} props.label - Label text for the select
 * @param {string} props.value - Current selected value
 * @param {Function} props.onChange - Change handler function
 * @param {Array} props.options - Array of option objects {value, label, disabled}
 * @param {string} props.placeholder - Placeholder option text
 * @param {boolean} props.disabled - Whether select is disabled
 * @param {boolean} props.required - Whether select is required
 * @param {string} props.helpText - Help text to display below select
 * @param {string} props.error - Error message to display
 * @param {string} props.className - Additional CSS classes
 * @param {string} props.size - Bootstrap select size ('sm', 'lg')
 * @param {boolean} props.multiple - Whether multiple selection is allowed
 */
const SelectField = ({
  id,
  label,
  value,
  onChange,
  options = [],
  placeholder,
  disabled = false,
  required = false,
  helpText,
  error,
  className = '',
  size,
  multiple = false,
  ...rest
}) => {
  // Determine select classes based on validation state
  const selectClasses = [
    'form-select',
    size && `form-select-${size}`,
    error && 'is-invalid',
    className
  ].filter(Boolean).join(' ');

  /**
   * Render option elements
   * @param {Array} optionList - List of options to render
   * @return {Array} Array of option JSX elements
   */
  const renderOptions = (optionList) => {
    return optionList.map((option, index) => {
      // Handle option groups
      if (option.group) {
        return (
          <optgroup key={`group-${index}`} label={option.group}>
            {renderOptions(option.options)}
          </optgroup>
        );
      }

      // Handle individual options
      const optionValue = typeof option === 'object' ? option.value : option;
      const optionLabel = typeof option === 'object' ? option.label : option;
      const optionDisabled = typeof option === 'object' ? option.disabled : false;

      return (
        <option 
          key={`option-${index}`} 
          value={optionValue}
          disabled={optionDisabled}
        >
          {optionLabel}
        </option>
      );
    });
  };

  return (
    <div className="mb-3">
      {/* Label */}
      {label && (
        <label htmlFor={id} className="form-label">
          {label}
          {required && <span className="text-danger ms-1">*</span>}
        </label>
      )}

      {/* Select Field */}
      <select
        className={selectClasses}
        id={id}
        value={value}
        onChange={onChange}
        disabled={disabled}
        required={required}
        multiple={multiple}
        {...rest}
      >
        {/* Placeholder Option */}
        {placeholder && !multiple && (
          <option value="" disabled>
            {placeholder}
          </option>
        )}

        {/* Options */}
        {renderOptions(options)}
      </select>

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

export default SelectField;