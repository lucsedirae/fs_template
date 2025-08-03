import React, { useState, useEffect } from 'react';
import './SettingsForm.css';

/**
 * SettingsForm Component
 * 
 * Provides a form interface for managing application settings and user preferences.
 * Uses Bootstrap styling for consistent UI design.
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

      // Here you would normally make an API call to save settings
      // const response = await fetch('/api/settings', {
      //   method: 'POST',
      //   headers: { 'Content-Type': 'application/json' },
      //   body: JSON.stringify(settings)
      // });

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
          {/* Application Settings */}
          <div className="col-md-6">
            <div className="card h-100 shadow-sm">
              <div className="card-header bg-primary text-white">
                <h5 className="card-title mb-0">
                  <span className="me-2">🏠</span>
                  Application Settings
                </h5>
              </div>
              <div className="card-body">
                {/* App Name */}
                <div className="mb-3">
                  <label htmlFor="appName" className="form-label">Application Name</label>
                  <input
                    type="text"
                    className="form-control"
                    id="appName"
                    value={settings.appName}
                    onChange={(e) => updateSetting('appName', e.target.value)}
                    disabled={loading}
                  />
                </div>

                {/* Maintenance Mode */}
                <div className="mb-3">
                  <div className="form-check form-switch">
                    <input
                      type="checkbox"
                      className="form-check-input"
                      id="maintenanceMode"
                      checked={settings.maintenanceMode}
                      onChange={(e) => updateSetting('maintenanceMode', e.target.checked)}
                      disabled={loading}
                    />
                    <label className="form-check-label" htmlFor="maintenanceMode">
                      Maintenance Mode
                    </label>
                  </div>
                </div>

                {/* Debug Mode */}
                <div className="mb-0">
                  <div className="form-check form-switch">
                    <input
                      type="checkbox"
                      className="form-check-input"
                      id="debugMode"
                      checked={settings.debugMode}
                      onChange={(e) => updateSetting('debugMode', e.target.checked)}
                      disabled={loading}
                    />
                    <label className="form-check-label" htmlFor="debugMode">
                      Debug Mode
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* User Interface Settings */}
          <div className="col-md-6">
            <div className="card h-100 shadow-sm">
              <div className="card-header bg-success text-white">
                <h5 className="card-title mb-0">
                  <span className="me-2">🎨</span>
                  User Interface
                </h5>
              </div>
              <div className="card-body">
                {/* Theme */}
                <div className="mb-3">
                  <label htmlFor="theme" className="form-label">Theme</label>
                  <select
                    className="form-select"
                    id="theme"
                    value={settings.theme}
                    onChange={(e) => updateSetting('theme', e.target.value)}
                    disabled={loading}
                  >
                    <option value="light">Light</option>
                    <option value="dark">Dark</option>
                    <option value="auto">Auto</option>
                  </select>
                </div>

                {/* Language */}
                <div className="mb-3">
                  <label htmlFor="language" className="form-label">Language</label>
                  <select
                    className="form-select"
                    id="language"
                    value={settings.language}
                    onChange={(e) => updateSetting('language', e.target.value)}
                    disabled={loading}
                  >
                    <option value="en">English</option>
                    <option value="es">Spanish</option>
                    <option value="fr">French</option>
                    <option value="de">German</option>
                  </select>
                </div>

                {/* Show Notifications */}
                <div className="mb-0">
                  <div className="form-check form-switch">
                    <input
                      type="checkbox"
                      className="form-check-input"
                      id="showNotifications"
                      checked={settings.showNotifications}
                      onChange={(e) => updateSetting('showNotifications', e.target.checked)}
                      disabled={loading}
                    />
                    <label className="form-check-label" htmlFor="showNotifications">
                      Show Notifications
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Form Actions */}
        <div className="row mt-4">
          <div className="col">
            <div className="d-flex justify-content-center gap-3">
              <button
                type="submit"
                className="btn btn-primary btn-lg"
                disabled={loading}
              >
                {loading ? (
                  <>
                    <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                    Saving...
                  </>
                ) : (
                  <>
                    <span className="me-2">💾</span>
                    Save Settings
                  </>
                )}
              </button>

              <button
                type="button"
                onClick={handleReset}
                className="btn btn-outline-danger btn-lg"
                disabled={loading}
              >
                <span className="me-2">🔄</span>
                Reset to Defaults
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  );
};

export default SettingsForm;