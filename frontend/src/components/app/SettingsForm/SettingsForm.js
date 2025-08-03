import React, { useState, useEffect } from 'react';
import './SettingsForm.css';

// Import shared components
import {
  SettingsCard,
  TextField,
  SwitchField,
  SelectField,
  FormActions
} from '../../shared';

/**
 * SettingsForm Component (Refactored)
 * 
 * Provides a form interface for managing application settings and user preferences.
 * Now uses shared/reusable components for consistent UI design.
 * 
 * @param {Object} props
 * @param {string} props.className - Additional CSS classes for the container
 */
const SettingsForm = ({ className = "" }) => {
  // Form state
  const [settings, setSettings] = useState({
    // Application Settings
    appName: 'React Docker App',
    appVersion: '1.0.0',
    maintenanceMode: false,
    debugMode: false,

    // User Interface Settings
    theme: 'light',
    language: 'en',
    itemsPerPage: 25,
    showNotifications: true,

    // Database Settings
    maxConnections: 100,
    queryTimeout: 30,
    enableCaching: true,

    // API Settings
    apiTimeout: 5000,
    enableCors: true,
    rateLimitEnabled: true,
    rateLimitRequests: 100
  });

  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState(null);
  const [error, setError] = useState(null);

  // Clear messages after 5 seconds
  useEffect(() => {
    if (error || success) {
      const timer = setTimeout(() => {
        setError(null);
        setSuccess(null);
      }, 5000);
      return () => clearTimeout(timer);
    }
  }, [error, success]);

  /**
   * Update a setting value
   * @param {string} key - Setting key
   * @param {*} value - New value
   */
  const updateSetting = (key, value) => {
    setSettings(prev => ({
      ...prev,
      [key]: value
    }));
  };

  /**
   * Handle form submission
   * @param {Event} e - Form submit event
   */
  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      // Simulate API call to save settings
      await new Promise(resolve => setTimeout(resolve, 1000));

      setSuccess('Settings saved successfully!');

      // Optional: Store in localStorage for demo purposes
      localStorage.setItem('appSettings', JSON.stringify(settings));

    } catch (err) {
      setError('Failed to save settings: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  /**
   * Reset settings to defaults
   */
  const handleReset = () => {
    if (window.confirm('Are you sure you want to reset all settings to defaults? This action cannot be undone.')) {
      setSettings({
        appName: 'React Docker App',
        appVersion: '1.0.0',
        maintenanceMode: false,
        debugMode: false,
        theme: 'light',
        language: 'en',
        itemsPerPage: 25,
        showNotifications: true,
        maxConnections: 100,
        queryTimeout: 30,
        enableCaching: true,
        apiTimeout: 5000,
        enableCors: true,
        rateLimitEnabled: true,
        rateLimitRequests: 100
      });
      setSuccess('Settings reset to defaults');
    }
  };

  /**
   * Load settings from localStorage on component mount
   */
  useEffect(() => {
    try {
      const savedSettings = localStorage.getItem('appSettings');
      if (savedSettings) {
        setSettings(JSON.parse(savedSettings));
      }
    } catch (err) {
      console.error('Failed to load settings from localStorage:', err);
    }
  }, []);

  // Form action buttons configuration
  const formActions = [
    {
      id: 'submit',
      label: 'Save Settings',
      type: 'submit',
      variant: 'primary',
      icon: '💾',
      loading: loading,
      loadingText: 'Saving...',
      disabled: loading
    },
    {
      id: 'reset',
      label: 'Reset to Defaults',
      type: 'button',
      variant: 'outline-danger',
      icon: '🔄',
      onClick: handleReset,
      disabled: loading
    }
  ];

  // Dropdown options
  const themeOptions = [
    { value: '', label: 'Select Theme', disabled: true },
    { value: 'light', label: 'Light (Coming Soon)' },
    { value: 'dark', label: 'Dark (Coming Soon)' }
  ];

  const languageOptions = [
    { value: '', label: 'Select Language', disabled: true },
    { value: 'en', label: 'English (Coming Soon)' },
    { value: 'es', label: 'Spanish (Coming Soon)' },
    { value: 'fr', label: 'French (Coming Soon)' }
  ];

  const itemsPerPageOptions = [
    { value: 10, label: '10 items' },
    { value: 25, label: '25 items' },
    { value: 50, label: '50 items' },
    { value: 100, label: '100 items' }
  ];

  return (
    <div className={`container-fluid ${className}`}>
      {/* Header */}
      <div className="row mb-4">
        <div className="col">
          <h2 className="mb-3">
            <span className="me-2">⚙️</span>
            Application Settings
          </h2>
          <p className="text-muted">
            Configure application preferences and system settings
          </p>
        </div>
      </div>

      {/* Alert Messages */}
      {error && (
        <div className="row mb-4">
          <div className="col">
            <div className="alert alert-danger alert-dismissible fade show" role="alert">
              <strong>Error:</strong> {error}
              <button type="button" className="btn-close" onClick={() => setError(null)}></button>
            </div>
          </div>
        </div>
      )}

      {success && (
        <div className="row mb-4">
          <div className="col">
            <div className="alert alert-success alert-dismissible fade show" role="alert">
              <strong>Success:</strong> {success}
              <button type="button" className="btn-close" onClick={() => setSuccess(null)}></button>
            </div>
          </div>
        </div>
      )}

      {/* Settings Form */}
      <form onSubmit={handleSubmit}>
        <div className="row g-4">
          {/* Application Settings Card */}
          <div className="col-md-6">
            <SettingsCard
              title="Application Settings"
              icon="🏠"
              headerColor="primary"
            >
              <TextField
                id="appName"
                label="Application Name"
                value={settings.appName}
                onChange={(e) => updateSetting('appName', e.target.value)}
                disabled={loading}
                required
                helpText="The display name for your application"
              />

              <TextField
                id="appVersion"
                label="Application Version"
                value={settings.appVersion}
                onChange={(e) => updateSetting('appVersion', e.target.value)}
                disabled={loading}
                helpText="Current version of the application"
              />

              <SwitchField
                id="maintenanceMode"
                label="Maintenance Mode"
                checked={settings.maintenanceMode}
                onChange={(e) => updateSetting('maintenanceMode', e.target.checked)}
                disabled={loading}
                helpText="Enable to put the application in maintenance mode"
              />

              <SwitchField
                id="debugMode"
                label="Debug Mode"
                checked={settings.debugMode}
                onChange={(e) => updateSetting('debugMode', e.target.checked)}
                disabled={loading}
                helpText="Enable debug logging and error details"
              />
            </SettingsCard>
          </div>

          {/* User Interface Settings Card */}
          <div className="col-md-6">
            <SettingsCard
              title="User Interface"
              icon="🎨"
              headerColor="success"
            >
              <SelectField
                id="theme"
                label="Theme"
                value={settings.theme}
                onChange={(e) => updateSetting('theme', e.target.value)}
                options={themeOptions}
                disabled={loading}
                helpText="Choose your preferred color theme"
              />

              <SelectField
                id="language"
                label="Language"
                value={settings.language}
                onChange={(e) => updateSetting('language', e.target.value)}
                options={languageOptions}
                disabled={loading}
                helpText="Select your preferred language"
              />

              <SwitchField
                id="showNotifications"
                label="Show Notifications"
                checked={settings.showNotifications}
                onChange={(e) => updateSetting('showNotifications', e.target.checked)}
                disabled={loading}
                helpText="Enable system notifications and alerts"
              />
            </SettingsCard>
          </div>
        </div>

        {/* Form Actions */}
        <div className="row mt-4">
          <div className="col">
            <FormActions
              actions={formActions}
              alignment="center"
              size="lg"
              loading={loading}
              stackOn="sm"
            />
          </div>
        </div>
      </form>
    </div>
  );
};

export default SettingsForm;