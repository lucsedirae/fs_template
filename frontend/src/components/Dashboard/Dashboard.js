import React, { useState } from 'react';
import './Dashboard.css';
import Navigation from './Navigation/Navigation';
import ApiTest from '../dev/ApiTest/ApiTest';
import DatabaseManager from '../dev/DatabaseManager/DatabaseManager';

/**
 * Dashboard Component
 * 
 * Main dashboard container that manages navigation and component rendering.
 * Coordinates between the navigation sidebar and the main content area.
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
    }
    // Add more menu items here as needed
    // {
    //   id: 'settings',
    //   name: 'Settings',
    //   icon: '⚙️',
    //   description: 'Application settings',
    //   category: 'app'
    // }
  ];

  /**
   * Handle navigation menu item click
   * @param {string} componentId - ID of the component to activate
   */
  const handleMenuItemClick = (componentId) => {
    setActiveComponent(componentId);
  };

  /**
   * Render the active component based on current selection
   * @return {JSX.Element} The active component
   */
  const renderActiveComponent = () => {
    switch (activeComponent) {
      case 'database':
        return <DatabaseManager />;
      case 'api':
        return <ApiTest />;
      default:
        return <DatabaseManager />;
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
        <div className="col-md-9 col-lg-10">
          <div className="bg-white min-vh-100">
            <div className="p-4">
              {renderActiveComponent()}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
