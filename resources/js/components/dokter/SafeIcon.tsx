import React from 'react';
import { LucideIcon } from 'lucide-react';

interface SafeIconProps {
  icon: LucideIcon;
  className?: string;
  size?: number;
  strokeWidth?: number;
  style?: React.CSSProperties;
}

// Komponen wrapper untuk memastikan ikon Lucide render dengan benar di iOS
export function SafeIcon({ 
  icon: Icon, 
  className = '', 
  size = 20, 
  strokeWidth = 2,
  style = {}
}: SafeIconProps) {
  return (
    <span 
      className="inline-block"
      style={{ 
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        width: size,
        height: size,
        fontSize: 0, // Prevent any text rendering
        lineHeight: 0,
        ...style
      }}
    >
      <Icon 
        className={className}
        width={size}
        height={size}
        strokeWidth={strokeWidth}
        style={{
          width: size,
          height: size,
          maxWidth: '100%',
          maxHeight: '100%',
          display: 'block'
        }}
      />
    </span>
  );
}