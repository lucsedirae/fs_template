import React from 'react';
import './App.css';
import ApiTest from './components/ApiTest';

function App() {
  return (
    <div className="App">
      <header className="App-header">
        <h1>🐳 React Docker App</h1>
        <p>
          Your React app is running successfully in Docker!
        </p>
      </header>
      
      {/* Add the API Test component */}
      <main>
        <ApiTest />
      </main>
    </div>
  );
}

export default App;