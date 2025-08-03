import React, { useState } from 'react';
import './Dashboard.css';
import Navigation from './Navigation/Navigation';
import MainContent from './MainContent/MainContent';

/**
 * Dashboard Component
 * 
 * Main dashboard container that coordinates between navigation and main content.
 * Acts as the primary layout manager for the dashboard interface.
 */
const Dashboard = () => {
  // Active component state
  const [activeComponent, setActiveComponent] = useState('database');

  // Menu items configuration
  const menuItems = [
    {
      id: 'database',
      name: 'Database Manager',
      icon: '🗄️',
      description: 'Manage database tables and structure',
      category: 'dev'
    },
    {
      id: 'api',
      name: 'API Tester',
      icon: '🔗',
      description: 'Test backend API endpoints',
      category: 'dev'
    },
    {
      id: 'settings',
      name: 'Settings',
      icon: '⚙️',
      description: 'Application settings and configuration',
      category: 'app'
    }
  ];

  /**
   * Handle navigation menu item click
   * @param {string} componentId - ID of the component to activate
   */
  const handleMenuItemClick = (componentId) => {
    setActiveComponent(componentId);
  };

  /**
   * Handle component change events from MainContent
   * @param {string} componentId - ID of the active component
   * @param {Object} componentConfig - Configuration of the active component
   */
  const handleComponentChange = (componentId, componentConfig) => {
    // Optional: Add analytics, logging, or other side effects here
    if (process.env.NODE_ENV === 'development') {
      console.log('Dashboard: Component changed to:', componentId, componentConfig);
    }
  };

  return (
    <div className="container-fluid p-0">
      <div className="row g-0">
        {/* Navigation Sidebar */}
        <Navigation
          menuItems={menuItems}
          activeComponent={activeComponent}
          onMenuItemClick={handleMenuItemClick}
        />

        {/* Main Content Area */}
        <MainContent
          activeComponent={activeComponent}
          onComponentChange={handleComponentChange}
        />
      </div>
    </div>
  );
};

export default Dashboard;