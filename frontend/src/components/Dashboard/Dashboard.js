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

  // Group menu items by category
  const devItems = menuItems.filter(item => item.category === 'dev');
  const appItems = menuItems.filter(item => item.category === 'app');

  return (
    <div className="container-fluid p-0">
      <div className="row g-0">
        {/* Sidebar */}
        <div className="col-md-3 col-lg-2">
          <div className="bg-primary text-white p-0 min-vh-100 shadow-sm">
            {/* Dashboard Header */}
            <div className="p-4 border-bottom border-white border-opacity-25">
              <h4 className="mb-2 fw-bold">
                <span className="me-2">🛠️</span>
                Dashboard
              </h4>
              <small className="text-white-50">Development & App Tools</small>
            </div>
            
            {/* Navigation Menu */}
            <nav className="p-3">
              {/* Development Tools Section */}
              {devItems.length > 0 && (
                <div className="mb-4">
                  <h6 className="text-uppercase text-white-50 fw-bold mb-3" style={{fontSize: '0.75rem', letterSpacing: '1px'}}>
                    Development Tools
                  </h6>
                  <div className="d-grid gap-2">
                    {devItems.map((item) => (
                      <button
                        key={item.id}
                        className={`btn text-start p-3 border-0 rounded-3 ${
                          activeComponent === item.id 
                            ? 'btn-light text-primary shadow-sm' 
                            : 'btn-outline-light text-white'
                        }`}
                        onClick={() => setActiveComponent(item.id)}
                      >
                        <div className="d-flex align-items-center">
                          <span className="fs-5 me-3">{item.icon}</span>
                          <div>
                            <div className="fw-semibold">{item.name}</div>
                            <small className={activeComponent === item.id ? 'text-muted' : 'text-white-75'}>
                              {item.description}
                            </small>
                          </div>
                        </div>
                      </button>
                    ))}
                  </div>
                </div>
              )}

              {/* App Components Section - for future use */}
              {appItems.length > 0 && (
                <div className="mb-4">
                  <h6 className="text-uppercase text-white-50 fw-bold mb-3" style={{fontSize: '0.75rem', letterSpacing: '1px'}}>
                    Application
                  </h6>
                  <div className="d-grid gap-2">
                    {appItems.map((item) => (
                      <button
                        key={item.id}
                        className={`btn text-start p-3 border-0 rounded-3 ${
                          activeComponent === item.id 
                            ? 'btn-light text-primary shadow-sm' 
                            : 'btn-outline-light text-white'
                        }`}
                        onClick={() => setActiveComponent(item.id)}
                      >
                        <div className="d-flex align-items-center">
                          <span className="fs-5 me-3">{item.icon}</span>
                          <div>
                            <div className="fw-semibold">{item.name}</div>
                            <small className={activeComponent === item.id ? 'text-muted' : 'text-white-75'}>
                              {item.description}
                            </small>
                          </div>
                        </div>
                      </button>
                    ))}
                  </div>
                </div>
              )}
            </nav>
          </div>
        </div>

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
