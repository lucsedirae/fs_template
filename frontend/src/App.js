import React from 'react';
import './App.css';
import Dashboard from './components/Dashboard/Dashboard';

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
        <Dashboard />
      </main>
    </div>
  );
}

export default App;