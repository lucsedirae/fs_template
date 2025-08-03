import React from 'react';
import './MainContent.css';
import ApiTest from '../../dev/ApiTest/ApiTest';
import DatabaseManager from '../../dev/DatabaseManager/DatabaseManager';
import SettingsForm from '../../app/SettingsForm/SettingsForm';

/**
 * Component registry mapping component IDs to their React components
 * This makes it easy to add new components without modifying the render logic
 */
const componentRegistry = {
    'database': {
        component: DatabaseManager,
        title: 'Database Manager',
        description: 'Manage database tables and structure'
    },
    'api': {
        component: ApiTest,
        title: 'API Tester',
        description: 'Test backend API endpoints'
    },
    'settings': {
        component: SettingsForm,
        title: 'Settings',
        description: 'Application settings and configuration'
    }
    // Add new components here as they're created:
    // 'users': {
    //   component: UserManager,
    //   title: 'User Management',
    //   description: 'Manage user accounts and permissions'
    // }
};

/**
 * Get component configuration by ID
 * @param {string} componentId - ID of the component
 * @return {Object|null} Component configuration object
 */
const getComponentConfig = (componentId) => {
    return componentRegistry[componentId] || null;
};

/**
 * Get list of available components for debugging/development
 * @return {Array} Array of available component IDs
 */
const getAvailableComponents = () => {
    return Object.keys(componentRegistry);
};

/**
 * MainContent Component
 * 
 * Renders the main content area of the dashboard based on the active component.
 * Manages component routing and provides a consistent layout wrapper.
 * 
 * @param {Object} props
 * @param {string} props.activeComponent - ID of the currently active component
 * @param {Object} props.componentProps - Props to pass to the active component
 * @param {string} props.className - Additional CSS classes for the container
 * @param {Function} props.onComponentChange - Callback when component changes (optional)
 */
const MainContent = ({
    activeComponent = 'database',
    componentProps = {},
    className = "",
    onComponentChange
}) => {

    /**
     * Render the active component based on current selection
     * @return {JSX.Element} The active component or fallback
     */
    const renderActiveComponent = () => {
        const config = getComponentConfig(activeComponent);

        if (!config) {
            // Fallback to default component if activeComponent is not found
            const defaultConfig = getComponentConfig('database');
            if (defaultConfig) {
                const DefaultComponent = defaultConfig.component;
                return <DefaultComponent {...componentProps} />;
            }

            // Ultimate fallback if no components are available
            return (
                <div className="text-center py-5">
                    <div className="text-muted">
                        <span className="fs-1 d-block mb-3" role="img" aria-label="Warning">⚠️</span>
                        <h4 className="mb-3">Component Not Found</h4>
                        <p>The requested component "{activeComponent}" is not available.</p>
                    </div>
                </div>
            );
        }

        const ActiveComponent = config.component;
        return <ActiveComponent {...componentProps} />;
    };

    /**
     * Handle component mount/unmount effects
     */
    React.useEffect(() => {
        if (onComponentChange) {
            const config = getComponentConfig(activeComponent);
            onComponentChange(activeComponent, config);
        }
    }, [activeComponent, onComponentChange]);

    // Development mode: log available components
    React.useEffect(() => {
        if (process.env.NODE_ENV === 'development') {
            console.log('MainContent: Available components:', getAvailableComponents());
            console.log('MainContent: Active component:', activeComponent);
        }
    }, [activeComponent]);

    return (
        <div className={`col-md-9 col-lg-10 ${className}`}>
            <div className="bg-white min-vh-100">
                <div className="main-content-wrapper p-4">
                    {/* Optional: Component header for debugging */}
                    {process.env.NODE_ENV === 'development' && (
                        <div className="d-none"> {/* Hidden by default, can be shown for debugging */}
                            <small className="text-muted">
                                Active Component: {activeComponent} |
                                Available: {getAvailableComponents().join(', ')}
                            </small>
                        </div>
                    )}

                    {/* Main component content */}
                    <div className="component-content">
                        {renderActiveComponent()}
                    </div>
                </div>
            </div>
        </div>
    );
};

export default MainContent;