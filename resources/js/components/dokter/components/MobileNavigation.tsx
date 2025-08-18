import React, { useState, useCallback } from 'react';
import { 
  Home, 
  BarChart3, 
  Users, 
  Calendar, 
  Settings, 
  Menu,
  X,
  ChevronLeft,
  ChevronRight
} from 'lucide-react';
import { useTouchGestures } from '../../../utils/TouchGestureManager';

interface NavigationItem {
  id: string;
  label: string;
  icon: React.ComponentType<any>;
  badge?: number;
  isActive?: boolean;
  onClick?: () => void;
}

interface MobileNavigationProps {
  items?: NavigationItem[];
  onNavigate?: (itemId: string) => void;
  className?: string;
}

const defaultItems: NavigationItem[] = [
  { id: 'dashboard', label: 'Dashboard', icon: Home, isActive: true },
  { id: 'analytics', label: 'Analytics', icon: BarChart3 },
  { id: 'leaderboard', label: 'Leaderboard', icon: Users },
  { id: 'schedule', label: 'Schedule', icon: Calendar, badge: 3 },
  { id: 'settings', label: 'Settings', icon: Settings }
];

const MobileNavigation: React.FC<MobileNavigationProps> = ({
  items = defaultItems,
  onNavigate,
  className = ''
}) => {
  const [activeIndex, setActiveIndex] = useState(0);
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [swipeOffset, setSwipeOffset] = useState(0);

  const handleNavigation = useCallback((itemId: string, index: number) => {
    setActiveIndex(index);
    onNavigate?.(itemId);
    setIsMenuOpen(false);
  }, [onNavigate]);

  const handleSwipeNavigation = useCallback((direction: 'left' | 'right') => {
    const newIndex = direction === 'left' 
      ? Math.min(activeIndex + 1, items.length - 1)
      : Math.max(activeIndex - 1, 0);
    
    if (newIndex !== activeIndex) {
      const item = items[newIndex];
      handleNavigation(item.id, newIndex);
    }
  }, [activeIndex, items, handleNavigation]);

  // Touch gesture handling for swipe navigation
  const gestureRef = useTouchGestures({
    onSwipe: (gesture) => {
      if (gesture.direction === 'left' || gesture.direction === 'right') {
        handleSwipeNavigation(gesture.direction);
      }
    },
    onTap: () => {
      // Close menu on tap outside
      if (isMenuOpen) {
        setIsMenuOpen(false);
      }
    }
  });

  const activeItem = items[activeIndex];

  return (
    <>
      {/* Mobile Bottom Navigation */}
      <div 
        ref={gestureRef}
        className={`
          fixed bottom-0 left-0 right-0 z-40
          bg-gray-900/95 backdrop-blur-lg border-t border-gray-700/50
          safe-area-pb
          ${className}
        `}
      >
        {/* Swipe indicator */}
        <div className="absolute top-1 left-1/2 transform -translate-x-1/2 w-12 h-1 bg-gray-600 rounded-full" />
        
        {/* Navigation content */}
        <div className="px-4 py-2">
          {/* Current page indicator with swipe hint */}
          <div className="flex items-center justify-between mb-2">
            <button
              onClick={() => handleSwipeNavigation('right')}
              className={`p-1 rounded-full transition-colors ${
                activeIndex > 0 
                  ? 'text-gray-400 hover:text-white hover:bg-gray-700' 
                  : 'text-gray-600 cursor-not-allowed'
              }`}
              disabled={activeIndex === 0}
            >
              <ChevronLeft className="w-4 h-4" />
            </button>

            <div className="flex items-center space-x-2">
              <activeItem.icon className="w-5 h-5 text-blue-400" />
              <span className="text-white font-medium">{activeItem.label}</span>
              {activeItem.badge && (
                <span className="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
                  {activeItem.badge}
                </span>
              )}
            </div>

            <button
              onClick={() => handleSwipeNavigation('left')}
              className={`p-1 rounded-full transition-colors ${
                activeIndex < items.length - 1
                  ? 'text-gray-400 hover:text-white hover:bg-gray-700'
                  : 'text-gray-600 cursor-not-allowed'
              }`}
              disabled={activeIndex === items.length - 1}
            >
              <ChevronRight className="w-4 h-4" />
            </button>
          </div>

          {/* Page dots indicator */}
          <div className="flex justify-center space-x-1">
            {items.map((_, index) => (
              <button
                key={index}
                onClick={() => handleNavigation(items[index].id, index)}
                className={`w-2 h-2 rounded-full transition-all duration-200 ${
                  index === activeIndex
                    ? 'bg-blue-400 w-4'
                    : 'bg-gray-600 hover:bg-gray-500'
                }`}
              />
            ))}
          </div>

          {/* Full menu toggle */}
          <button
            onClick={() => setIsMenuOpen(!isMenuOpen)}
            className="absolute top-3 right-4 p-2 text-gray-400 hover:text-white transition-colors"
          >
            {isMenuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
          </button>
        </div>
      </div>

      {/* Expandable Menu Overlay */}
      {isMenuOpen && (
        <div className="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
          <div className="absolute bottom-0 left-0 right-0 bg-gray-900 rounded-t-xl max-h-96 overflow-y-auto">
            {/* Menu header */}
            <div className="flex items-center justify-between p-4 border-b border-gray-700">
              <h3 className="text-lg font-semibold text-white">Navigation</h3>
              <button
                onClick={() => setIsMenuOpen(false)}
                className="p-2 text-gray-400 hover:text-white transition-colors"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Menu items */}
            <div className="p-4 space-y-2">
              {items.map((item, index) => {
                const IconComponent = item.icon;
                const isActive = index === activeIndex;

                return (
                  <button
                    key={item.id}
                    onClick={() => handleNavigation(item.id, index)}
                    className={`
                      w-full flex items-center space-x-3 p-3 rounded-lg
                      transition-all duration-200 text-left
                      ${isActive 
                        ? 'bg-blue-600 text-white shadow-lg' 
                        : 'text-gray-300 hover:bg-gray-800 hover:text-white'
                      }
                    `}
                  >
                    <div className={`p-2 rounded-lg ${
                      isActive ? 'bg-blue-500' : 'bg-gray-700'
                    }`}>
                      <IconComponent className="w-5 h-5" />
                    </div>
                    
                    <div className="flex-1">
                      <div className="font-medium">{item.label}</div>
                      {item.id === 'dashboard' && (
                        <div className="text-xs opacity-75">Swipe left/right to navigate</div>
                      )}
                    </div>

                    {item.badge && (
                      <span className="bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                        {item.badge}
                      </span>
                    )}

                    {isActive && (
                      <div className="w-2 h-2 bg-white rounded-full" />
                    )}
                  </button>
                );
              })}
            </div>

            {/* Swipe hint */}
            <div className="p-4 pt-0">
              <div className="bg-gray-800 rounded-lg p-3">
                <div className="text-sm text-gray-300 text-center">
                  💡 <strong>Tip:</strong> Swipe left/right on the bottom bar to quickly navigate between sections
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
};

export default React.memo(MobileNavigation);