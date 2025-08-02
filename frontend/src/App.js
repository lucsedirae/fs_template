import React from 'react';
import Dashboard from './components/Dashboard/Dashboard';

function App() {
  return (
    <div className="App">
      <header className="bg-dark text-white py-4">
        <div className="container text-center">
          <h1 className="mb-3">
            <span className="me-3">🐳</span>
            React Docker App
          </h1>
          <p className="lead mb-0">
            Your React app is running successfully in Docker!
          </p>
        </div>
      </header>
      
      <main className="bg-light">
        <Dashboard />
      </main>
    </div>
  );
}

export default App;