import { useEffect, useState } from 'react';

// Hook to detect if SVG icons are rendering correctly on iOS
export function useIconFallback() {
  const [needsFallback, setNeedsFallback] = useState(false);
  
  useEffect(() => {
    // Check if we're on iOS Safari
    const isIOSSafari = /iPad|iPhone|iPod/.test(navigator.userAgent) && 
                       !window.MSStream &&
                       /Safari/.test(navigator.userAgent) &&
                       !/Chrome/.test(navigator.userAgent);
    
    if (isIOSSafari) {
      // Create a test SVG element
      const testSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      testSvg.setAttribute('width', '20');
      testSvg.setAttribute('height', '20');
      testSvg.style.position = 'absolute';
      testSvg.style.top = '-9999px';
      testSvg.innerHTML = '<path d="M10 10 L20 20" stroke="black" />';
      
      document.body.appendChild(testSvg);
      
      // Check if SVG rendered properly
      setTimeout(() => {
        const rect = testSvg.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) {
          console.warn('SVG rendering issue detected on iOS Safari');
          setNeedsFallback(true);
        }
        document.body.removeChild(testSvg);
      }, 100);
    }
  }, []);
  
  return needsFallback;
}