import React from 'react';
import './FormActions.css';

/**
 * FormActions Component
 * 
 * A reusable component for form action buttons (submit, cancel, reset, etc.).
 * Provides consistent styling and layout for form controls.
 * 
 * @param {Object} props
 * @param {Array} props.actions - Array of action objects
 * @param {string} props.alignment - Button alignment ('center', 'left', 'right', 'between')
 * @param {string} props.size - Button size ('sm', 'lg')
 * @param {string} props.className - Additional CSS classes
 * @param {boolean} props.loading - Whether any action is in progress
 * @param {string} props.stackOn - Breakpoint to stack buttons ('sm', 'md', 'lg')
 * 
 * Action object structure:
 * {
 *   id: string,
 *   label: string,
 *   type: 'submit' | 'button' | 'reset',
 *   variant: 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info' | 'light' | 'dark' | 'outline-*',
 *   icon: string (emoji or icon),
 *   onClick: function,
 *   disabled: boolean,
 *   loading: boolean,
 *   loadingText: string
 * }
 */
const FormActions = ({
  actions = [],
  alignment = 'center',
  size,
  className = '',
  loading = false,
  stackOn = 'sm',
  ...rest
}) => {
  // Determine container classes based on alignment
  const getContainerClasses = () => {
    const baseClasses = ['d-flex', 'gap-3'];
    
    // Add responsive stacking
    if (stackOn) {
      baseClasses.push('flex-column', `flex-${stackOn}-row`);
    }

    // Add alignment classes
    switch (alignment) {
      case 'left':
        baseClasses.push('justify-content-start');
        break;
      case 'right':
        baseClasses.push('justify-content-end');
        break;
      case 'between':
        baseClasses.push('justify-content-between');
        break;
      case 'center':
      default:
        baseClasses.push('justify-content-center');
        break;
    }

    if (className) {
      baseClasses.push(className);
    }

    return baseClasses.join(' ');
  };

  /**
   * Render a single action button
   * @param {Object} action - Action configuration object
   * @param {number} index - Action index for key
   * @return {JSX.Element} Button element
   */
  const renderAction = (action, index) => {
    const {
      id,
      label,
      type = 'button',
      variant = 'primary',
      icon,
      onClick,
      disabled = false,
      loading: actionLoading = false,
      loadingText
    } = action;

    // Determine button classes
    const buttonClasses = [
      'btn',
      `btn-${variant}`,
      size && `btn-${size}`
    ].filter(Boolean).join(' ');

    // Determine if button is disabled
    const isDisabled = disabled || loading || actionLoading;

    // Determine button content based on loading state
    const getButtonContent = () => {
      if (actionLoading || (loading && type === 'submit')) {
        return (
          <>
            <span 
              className="spinner-border spinner-border-sm me-2" 
              role="status" 
              aria-hidden="true"
            ></span>
            {loadingText || 'Loading...'}
          </>
        );
      }

      return (
        <>
          {icon && (
            <span className="me-2" role="img" aria-label={label}>
              {icon}
            </span>
          )}
          {label}
        </>
      );
    };

    return (
      <button
        key={id || `action-${index}`}
        type={type}
        className={buttonClasses}
        onClick={onClick}
        disabled={isDisabled}
        {...rest}
      >
        {getButtonContent()}
      </button>
    );
  };

  // Don't render if no actions provided
  if (!actions || actions.length === 0) {
    return null;
  }

  return (
    <div className={getContainerClasses()}>
      {actions.map(renderAction)}
    </div>
  );
};

export default FormActions;