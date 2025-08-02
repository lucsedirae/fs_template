import React, { useState, useEffect, useCallback } from 'react';
import './ApiTest.css';

const ApiTest = () => {
  const [helloResponse, setHelloResponse] = useState(null);
  const [statusResponse, setStatusResponse] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Base URL for the PHP backend
  const API_BASE_URL = 'http://localhost:8080';

  // Generic API call function
  const callApi = useCallback(async (endpoint, setResponse) => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await fetch(`${API_BASE_URL}${endpoint}`);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      setResponse({
        status: response.status,
        data: data,
        timestamp: new Date().toLocaleTimeString()
      });
    } catch (err) {
      setError(`Failed to call ${endpoint}: ${err.message}`);
      console.error('API Error:', err);
    } finally {
      setLoading(false);
    }
  }, [API_BASE_URL]);

  // Individual endpoint functions
  const testHelloEndpoint = useCallback(() => callApi('/api/hello', setHelloResponse), [callApi]);
  const testStatusEndpoint = useCallback(() => callApi('/api/status', setStatusResponse), [callApi]);

  // Test status endpoint on component mount
  useEffect(() => {
    testStatusEndpoint();
  }, [testStatusEndpoint]);

  const clearResponses = () => {
    setHelloResponse(null);
    setStatusResponse(null);
    setError(null);
  };

  const renderResponse = (response, title) => {
    if (!response) return null;

    return (
      <div className="response-container">
        <h4>{title}</h4>
        <div className="response-meta">
          <span className="status-badge">Status: {response.status}</span>
          <span className="timestamp">Called at: {response.timestamp}</span>
        </div>
        <pre className="response-body">
          {JSON.stringify(response.data, null, 2)}
        </pre>
      </div>
    );
  };

  return (
    <div className="api-test">
      <div className="api-test-header">
        <h2>🔗 Backend API Test</h2>
        <p>Test your PHP backend endpoints</p>
      </div>

      {error && (
        <div className="error-message">
          <strong>Error:</strong> {error}
        </div>
      )}

      <div className="controls">
        <button 
          onClick={testHelloEndpoint} 
          disabled={loading}
          className="test-button"
        >
          {loading ? 'Testing...' : 'Test /api/hello'}
        </button>
        
        <button 
          onClick={testStatusEndpoint} 
          disabled={loading}
          className="test-button"
        >
          {loading ? 'Testing...' : 'Test /api/status'}
        </button>
        
        <button 
          onClick={clearResponses} 
          className="clear-button"
        >
          Clear Results
        </button>
      </div>

      <div className="responses">
        {renderResponse(helloResponse, "Hello Endpoint Response")}
        {renderResponse(statusResponse, "Status Endpoint Response")}
      </div>

      <div className="api-info">
        <h3>Available Endpoints:</h3>
        <ul>
          <li><code>GET {API_BASE_URL}/api/hello</code> - Returns a hello message</li>
          <li><code>GET {API_BASE_URL}/api/status</code> - Returns backend status info</li>
        </ul>
      </div>
    </div>
  );
};

export default ApiTest;