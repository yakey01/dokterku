import React, { useState, useCallback } from 'react';
import { useTouchGestures } from '../../../utils/TouchGestureManager';
import { MoreVertical, ChevronRight, Star } from 'lucide-react';

interface TouchOptimizedCardProps {
  children: React.ReactNode;
  title?: string;
  subtitle?: string;
  onTap?: () => void;
  onLongPress?: () => void;
  onSwipeLeft?: () => void;
  onSwipeRight?: () => void;
  rightAction?: {
    icon: React.ComponentType<any>;
    label: string;
    color: string;
    onClick: () => void;
  };
  leftAction?: {
    icon: React.ComponentType<any>;
    label: string;
    color: string;
    onClick: () => void;
  };
  priority?: 'low' | 'medium' | 'high';
  isInteractive?: boolean;
  isLoading?: boolean;
  className?: string;
}

const TouchOptimizedCard: React.FC<TouchOptimizedCardProps> = ({
  children,
  title,
  subtitle,
  onTap,
  onLongPress,
  onSwipeLeft,
  onSwipeRight,
  rightAction,
  leftAction,
  priority = 'medium',
  isInteractive = true,
  isLoading = false,
  className = ''
}) => {
  const [swipeOffset, setSwipeOffset] = useState(0);
  const [isPressed, setIsPressed] = useState(false);
  const [showActions, setShowActions] = useState(false);

  const handleSwipe = useCallback((gesture: any) => {
    if (!isInteractive) return;

    if (gesture.direction === 'left' && onSwipeLeft) {
      onSwipeLeft();
      // Show right action feedback
      if (rightAction) {
        setShowActions(true);
        setTimeout(() => setShowActions(false), 2000);
      }
    } else if (gesture.direction === 'right' && onSwipeRight) {
      onSwipeRight();
      // Show left action feedback
      if (leftAction) {
        setShowActions(true);
        setTimeout(() => setShowActions(false), 2000);
      }
    }
  }, [isInteractive, onSwipeLeft, onSwipeRight, rightAction, leftAction]);

  const handleTap = useCallback(() => {
    if (!isInteractive || isLoading) return;
    
    setIsPressed(true);
    setTimeout(() => setIsPressed(false), 150);
    
    onTap?.();
  }, [isInteractive, isLoading, onTap]);

  const handleLongPress = useCallback(() => {
    if (!isInteractive || isLoading) return;
    
    setShowActions(!showActions);
    onLongPress?.();
  }, [isInteractive, isLoading, showActions, onLongPress]);

  const gestureRef = useTouchGestures({
    onSwipe: handleSwipe,
    onTap: handleTap,
    onLongPress: handleLongPress
  });

  const getPriorityBorder = () => {
    switch (priority) {
      case 'high':
        return 'border-l-4 border-l-red-400';
      case 'medium':
        return 'border-l-4 border-l-blue-400';
      case 'low':
        return 'border-l-4 border-l-green-400';
      default:
        return 'border-l-4 border-l-gray-400';
    }
  };

  const getInteractiveClasses = () => {
    if (!isInteractive) return '';
    
    return `
      transform transition-all duration-150 ease-out
      ${isPressed ? 'scale-95 bg-gray-700' : 'hover:bg-gray-750'}
      ${onTap ? 'cursor-pointer' : ''}
      active:scale-95 active:bg-gray-700
    `;
  };

  return (
    <div className="relative">
      {/* Swipe Action Backgrounds */}
      {leftAction && (
        <div className={`
          absolute inset-y-0 left-0 right-1/2 
          flex items-center justify-start pl-6
          ${leftAction.color} rounded-lg
          transition-opacity duration-200
          ${showActions ? 'opacity-100' : 'opacity-0'}
        `}>
          <leftAction.icon className="w-6 h-6 text-white mr-2" />
          <span className="text-white font-medium">{leftAction.label}</span>
        </div>
      )}

      {rightAction && (
        <div className={`
          absolute inset-y-0 right-0 left-1/2 
          flex items-center justify-end pr-6
          ${rightAction.color} rounded-lg
          transition-opacity duration-200
          ${showActions ? 'opacity-100' : 'opacity-0'}
        `}>
          <span className="text-white font-medium mr-2">{rightAction.label}</span>
          <rightAction.icon className="w-6 h-6 text-white" />
        </div>
      )}

      {/* Main Card */}
      <div
        ref={gestureRef}
        className={`
          relative bg-gray-800 rounded-lg p-4 
          ${getPriorityBorder()}
          ${getInteractiveClasses()}
          ${isLoading ? 'opacity-60 pointer-events-none' : ''}
          min-h-[60px] touch-manipulation
          ${className}
        `}
        style={{
          transform: `translateX(${swipeOffset}px)`,
          WebkitTapHighlightColor: 'transparent'
        }}
      >
        {/* Loading indicator */}
        {isLoading && (
          <div className="absolute inset-0 flex items-center justify-center bg-gray-800/80 rounded-lg">
            <div className="w-6 h-6 border-2 border-blue-400 border-t-transparent rounded-full animate-spin" />
          </div>
        )}

        {/* Header */}
        {(title || subtitle) && (
          <div className="flex items-start justify-between mb-3">
            <div className="flex-1 min-w-0">
              {title && (
                <h3 className="text-white font-semibold text-lg leading-tight truncate">
                  {title}
                </h3>
              )}
              {subtitle && (
                <p className="text-gray-400 text-sm mt-1 truncate">
                  {subtitle}
                </p>
              )}
            </div>

            {/* Action indicators */}
            <div className="flex items-center space-x-2 ml-3">
              {priority === 'high' && (
                <Star className="w-4 h-4 text-red-400 fill-current" />
              )}
              
              {isInteractive && onTap && (
                <ChevronRight className="w-4 h-4 text-gray-400" />
              )}
              
              {(rightAction || leftAction) && (
                <MoreVertical className="w-4 h-4 text-gray-400" />
              )}
            </div>
          </div>
        )}

        {/* Content */}
        <div className="text-gray-300">
          {children}
        </div>

        {/* Touch feedback overlay */}
        <div className={`
          absolute inset-0 rounded-lg pointer-events-none
          transition-opacity duration-150
          ${isPressed ? 'bg-white/5' : 'bg-transparent'}
        `} />
      </div>

      {/* Action hints */}
      {showActions && (isInteractive && (leftAction || rightAction)) && (
        <div className="absolute -bottom-2 left-1/2 transform -translate-x-1/2 z-10">
          <div className="bg-gray-900 text-white text-xs px-3 py-1 rounded-full whitespace-nowrap">
            {leftAction && rightAction
              ? 'Swipe left or right for actions'
              : leftAction
              ? 'Swipe right for action'
              : 'Swipe left for action'
            }
          </div>
        </div>
      )}
    </div>
  );
};

export default React.memo(TouchOptimizedCard);