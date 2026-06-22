// main.tsx
import './cesium-wrapper'; // Ensure the wrapper sets the global first
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';

if (typeof window !== 'undefined') {
    // Serve Cesium directly from Laravel's public directory to prevent Web Worker CORS blocks
    (window as any).CESIUM_BASE_URL = '/cesium/';
}

console.log('🚀 main.tsx: Cesium global initialized');

const rootElement = document.getElementById('root');
if (rootElement) {
    createRoot(rootElement).render(
        <StrictMode>
            <App />
        </StrictMode>,
    );
    console.log('✅ main.tsx: App mounted');
}
