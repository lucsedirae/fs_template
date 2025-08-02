import React, { useState, useEffect, useCallback } from 'react';
import './ApiTest.css';
const ApiTest = () => {
  const [helloResponse, setHelloResponse] = useState(null);
  const [statusResponse, setStatusResponse] = useState(null);
  const [dbTestResponse, setDbTestResponse] = useState(null);
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
  const testDbEndpoint = useCallback(() => callApi('/api/db-test', setDbTestResponse), [callApi]);

  // Test status endpoint on component mount
  useEffect(() => {
    testStatusEndpoint();
  }, [testStatusEndpoint]);

  const clearResponses = () => {
    setHelloResponse(null);
    setStatusResponse(null);
    setDbTestResponse(null);
    setError(null);
  };

  const renderResponse = (response, title) => {
    if (!response) return null;

    return (
      <div className="card mb-4 shadow-sm">
        <div className="card-header bg-light">
          <h5 className="card-title mb-0">{title}</h5>
        </div>
        <div className="card-body">
          <div className="d-flex flex-wrap gap-3 mb-3">
            <span className="badge bg-success fs-6">
              Status: {response.status}
            </span>
            <small className="text-muted align-self-center">
              Called at: {response.timestamp}
            </small>
          </div>
          <div className="bg-light border rounded p-3">
            <pre className="mb-0 text-wrap" style={{fontSize: '0.875rem', lineHeight: '1.4'}}>
              {JSON.stringify(response.data, null, 2)}
            </pre>
          </div>
        </div>
      </div>
    );
  };

  return (
    <div className="container-fluid">
      {/* Header */}
      <div className="row mb-4">
        <div className="col">
          <div className="text-center py-4 border-bottom">
            <h2 className="mb-3">
              <span className="me-2">🔗</span>
              Backend API Test
            </h2>
            <p className="text-muted mb-0">Test your PHP backend endpoints</p>
          </div>
        </div>
      </div>

      {/* Error Alert */}
      {error && (
        <div className="row mb-4">
          <div className="col">
            <div className="alert alert-danger d-flex align-items-center" role="alert">
              <span className="me-2">⚠️</span>
              <div>
                <strong>Error:</strong> {error}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Control Buttons */}
      <div className="row mb-4">
        <div className="col">
          <div className="d-flex flex-wrap justify-content-center gap-3">
            <button 
              onClick={testHelloEndpoint} 
              disabled={loading}
              className="btn btn-info"
            >
              {loading ? (
                <>
                  <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                  Testing...
                </>
              ) : (
                'Test /api/hello'
              )}
            </button>
            
            <button 
              onClick={testStatusEndpoint} 
              disabled={loading}
              className="btn btn-info"
            >
              {loading ? (
                <>
                  <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                  Testing...
                </>
              ) : (
                'Test /api/status'
              )}
            </button>

            <button 
              onClick={testDbEndpoint} 
              disabled={loading}
              className="btn btn-success"
            >
              {loading ? (
                <>
                  <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                  Testing...
                </>
              ) : (
                'Test /api/db-test'
              )}
            </button>
            
            <button 
              onClick={clearResponses} 
              className="btn btn-outline-danger"
            >
              Clear Results
            </button>
          </div>
        </div>
      </div>

      {/* API Responses */}
      <div className="row mb-4">
        <div className="col">
          {renderResponse(helloResponse, "Hello Endpoint Response")}
          {renderResponse(statusResponse, "Status Endpoint Response")}
          {renderResponse(dbTestResponse, "Database Test Response")}
        </div>
      </div>

      {/* API Information */}
      <div className="row">
        <div className="col">
          <div className="card border-info">
            <div className="card-header bg-info text-white">
              <h5 className="card-title mb-0">
                <span className="me-2">📋</span>
                Available Endpoints
              </h5>
            </div>
            <div className="card-body">
              <ul className="list-unstyled mb-0">
                <li className="mb-2">
                  <code className="bg-light text-danger px-2 py-1 rounded me-2">
                    GET {API_BASE_URL}/api/hello
                  </code>
                  - Returns a hello message
                </li>
                <li className="mb-2">
                  <code className="bg-light text-danger px-2 py-1 rounded me-2">
                    GET {API_BASE_URL}/api/status
                  </code>
                  - Returns backend status info
                </li>
                <li className="mb-0">
                  <code className="bg-light text-danger px-2 py-1 rounded me-2">
                    GET {API_BASE_URL}/api/db-test
                  </code>
                  - Tests database connection
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ApiTest;