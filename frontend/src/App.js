import React from 'react';
import './App.css';
import ApiTest from './components/ApiTest';
import DatabaseManager from './components/DatabaseManager';

function App() {
  return (
    <div className="App">
      <header className="App-header">
        <h1>🐳 React Docker App</h1>
        <p>
          Your React app is running successfully in Docker!
        </p>
      </header>
      
      <main>
        {/* Database Management Section */}
        <section className="app-section">
          <DatabaseManager />
        </section>
        
        {/* API Test Section */}
        <section className="app-section">
          <ApiTest />
        </section>
      </main>
    </div>
  );
}

export default App;