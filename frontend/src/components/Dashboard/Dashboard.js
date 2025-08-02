import React, { useState } from 'react';
import './Dashboard.css';
import ApiTest from '../dev/ApiTest/ApiTest';
import DatabaseManager from '../dev/DatabaseManager/DatabaseManager';

const Dashboard = () => {
  const [activeComponent, setActiveComponent] = useState('database');

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
  ];

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

  // Group menu items by category for future organization
  const devItems = menuItems.filter(item => item.category === 'dev');
  const appItems = menuItems.filter(item => item.category === 'app');

  return (
    <div className="dashboard">
      <div className="dashboard-sidebar">
        <div className="dashboard-header">
          <h2>🛠️ Dashboard</h2>
          <p>Development & App Tools</p>
        </div>
        
        <nav className="dashboard-menu">
          {/* Development Tools Section */}
          {devItems.length > 0 && (
            <div className="menu-section">
              <div className="menu-section-title">Development Tools</div>
              {devItems.map((item) => (
                <button
                  key={item.id}
                  className={`menu-item ${activeComponent === item.id ? 'active' : ''}`}
                  onClick={() => setActiveComponent(item.id)}
                >
                  <span className="menu-icon">{item.icon}</span>
                  <div className="menu-content">
                    <span className="menu-name">{item.name}</span>
                    <span className="menu-description">{item.description}</span>
                  </div>
                </button>
              ))}
            </div>
          )}

          {/* App Components Section - for future use */}
          {appItems.length > 0 && (
            <div className="menu-section">
              <div className="menu-section-title">Application</div>
              {appItems.map((item) => (
                <button
                  key={item.id}
                  className={`menu-item ${activeComponent === item.id ? 'active' : ''}`}
                  onClick={() => setActiveComponent(item.id)}
                >
                  <span className="menu-icon">{item.icon}</span>
                  <div className="menu-content">
                    <span className="menu-name">{item.name}</span>
                    <span className="menu-description">{item.description}</span>
                  </div>
                </button>
              ))}
            </div>
          )}
        </nav>
      </div>

      <div className="dashboard-main">
        <div className="dashboard-content">
          {renderActiveComponent()}
        </div>
      </div>
    </div>
  );
};

export default Dashboard;