import React from 'react';
import './SettingsCard.css';

/**
 * SettingsCard Component
 * 
 * A reusable card component for grouping related settings fields.
 * Provides consistent styling and layout for form sections.
 * 
 * @param {Object} props
 * @param {string} props.title - Card title text
 * @param {string} props.icon - Icon emoji or character to display
 * @param {string} props.headerColor - Bootstrap color class for header (default: 'primary')
 * @param {React.ReactNode} props.children - Form fields and content
 * @param {string} props.className - Additional CSS classes
 * @param {boolean} props.fullHeight - Whether card should take full height (default: true)
 */
const SettingsCard = ({
    title,
    icon,
    headerColor = 'primary',
    children,
    className = '',
    fullHeight = true
}) => {
    return (
        <div className={`card ${fullHeight ? 'h-100' : ''} shadow-sm ${className}`}>
            {/* Card Header */}
            <div className={`card-header bg-${headerColor} text-white`}>
                <h5 className="card-title mb-0">
                    {icon && (
                        <span className="me-2" role="img" aria-label={title}>
                            {icon}
                        </span>
                    )}
                    {title}
                </h5>
            </div>

            {/* Card Body */}
            <div className="card-body">
                {children}
            </div>
        </div>
    );
};

export default SettingsCard;