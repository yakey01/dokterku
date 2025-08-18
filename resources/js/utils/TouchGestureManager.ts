/**
 * Touch Gesture Manager for Mobile Dashboard
 * Provides swipe gestures, pull-to-refresh, and mobile-optimized interactions
 */

import React, { useEffect, useRef, useCallback, useState } from 'react';

interface TouchPoint {
  x: number;
  y: number;
  timestamp: number;
}

interface SwipeGesture {
  direction: 'left' | 'right' | 'up' | 'down';
  distance: number;
  velocity: number;
  duration: number;
}

interface PullToRefreshConfig {
  threshold: number; // Distance threshold to trigger refresh
  snapBackDuration: number; // Animation duration for snap back
  triggerDistance: number; // Distance to show refresh indicator
}

interface TouchGestureConfig {
  swipeThreshold: number; // Minimum distance for swipe
  velocityThreshold: number; // Minimum velocity for swipe
  pullToRefresh: PullToRefreshConfig;
  enableHaptics: boolean; // Enable haptic feedback on supported devices
}

type GestureHandler = {
  onSwipe?: (gesture: SwipeGesture) => void;
  onPullToRefresh?: () => Promise<void>;
  onTap?: (point: TouchPoint) => void;
  onLongPress?: (point: TouchPoint) => void;
  onPinch?: (scale: number) => void;
};

const DEFAULT_CONFIG: TouchGestureConfig = {
  swipeThreshold: 50,
  velocityThreshold: 0.3,
  pullToRefresh: {
    threshold: 80,
    snapBackDuration: 300,
    triggerDistance: 60
  },
  enableHaptics: true
};

class TouchGestureManager {
  private config: TouchGestureConfig;
  private startTouch: TouchPoint | null = null;
  private lastTouch: TouchPoint | null = null;
  private isTracking = false;
  private longPressTimer: NodeJS.Timeout | null = null;
  private isPullToRefreshActive = false;
  private pullDistance = 0;
  private pinchStartDistance = 0;
  private element: HTMLElement | null = null;

  constructor(config: Partial<TouchGestureConfig> = {}) {
    this.config = { ...DEFAULT_CONFIG, ...config };
  }

  /**
   * Bind gesture recognition to DOM element
   */
  bindToElement(element: HTMLElement, handlers: GestureHandler): () => void {
    this.element = element;
    
    const touchStartHandler = (e: TouchEvent) => this.handleTouchStart(e, handlers);
    const touchMoveHandler = (e: TouchEvent) => this.handleTouchMove(e, handlers);
    const touchEndHandler = (e: TouchEvent) => this.handleTouchEnd(e, handlers);
    const touchCancelHandler = () => this.handleTouchCancel();

    // Add passive listeners for better performance
    element.addEventListener('touchstart', touchStartHandler, { passive: false });
    element.addEventListener('touchmove', touchMoveHandler, { passive: false });
    element.addEventListener('touchend', touchEndHandler, { passive: true });
    element.addEventListener('touchcancel', touchCancelHandler, { passive: true });

    // Return cleanup function
    return () => {
      element.removeEventListener('touchstart', touchStartHandler);
      element.removeEventListener('touchmove', touchMoveHandler);
      element.removeEventListener('touchend', touchEndHandler);
      element.removeEventListener('touchcancel', touchCancelHandler);
    };
  }

  /**
   * Handle touch start
   */
  private handleTouchStart(e: TouchEvent, handlers: GestureHandler): void {
    const touch = e.touches[0];
    const point: TouchPoint = {
      x: touch.clientX,
      y: touch.clientY,
      timestamp: Date.now()
    };

    this.startTouch = point;
    this.lastTouch = point;
    this.isTracking = true;

    // Handle pinch gesture
    if (e.touches.length === 2 && handlers.onPinch) {
      const touch1 = e.touches[0];
      const touch2 = e.touches[1];
      this.pinchStartDistance = this.calculateDistance(
        { x: touch1.clientX, y: touch1.clientY },
        { x: touch2.clientX, y: touch2.clientY }
      );
    }

    // Start long press timer
    if (handlers.onLongPress) {
      this.longPressTimer = setTimeout(() => {
        if (this.isTracking && this.startTouch) {
          handlers.onLongPress!(this.startTouch);
          this.triggerHapticFeedback('medium');
        }
      }, 500);
    }

    // Prevent default for pull-to-refresh if at top of page
    if (this.isAtTop() && point.y < 100) {
      e.preventDefault();
    }
  }

  /**
   * Handle touch move
   */
  private handleTouchMove(e: TouchEvent, handlers: GestureHandler): void {
    if (!this.isTracking || !this.startTouch) return;

    const touch = e.touches[0];
    const currentPoint: TouchPoint = {
      x: touch.clientX,
      y: touch.clientY,
      timestamp: Date.now()
    };

    // Clear long press if moved too much
    if (this.longPressTimer) {
      const moveDistance = this.calculateDistance(this.startTouch, currentPoint);
      if (moveDistance > 10) {
        clearTimeout(this.longPressTimer);
        this.longPressTimer = null;
      }
    }

    // Handle pinch gesture
    if (e.touches.length === 2 && handlers.onPinch && this.pinchStartDistance > 0) {
      const touch1 = e.touches[0];
      const touch2 = e.touches[1];
      const currentDistance = this.calculateDistance(
        { x: touch1.clientX, y: touch1.clientY },
        { x: touch2.clientX, y: touch2.clientY }
      );
      const scale = currentDistance / this.pinchStartDistance;
      handlers.onPinch(scale);
      return;
    }

    // Handle pull to refresh
    if (handlers.onPullToRefresh && this.isAtTop()) {
      const deltaY = currentPoint.y - this.startTouch.y;
      
      if (deltaY > 0) {
        this.pullDistance = Math.min(deltaY, this.config.pullToRefresh.threshold * 1.5);
        this.updatePullToRefreshIndicator(this.pullDistance);
        
        // Prevent scrolling during pull
        e.preventDefault();
        
        // Trigger haptic at threshold
        if (this.pullDistance >= this.config.pullToRefresh.threshold && !this.isPullToRefreshActive) {
          this.isPullToRefreshActive = true;
          this.triggerHapticFeedback('light');
        }
      }
    }

    this.lastTouch = currentPoint;
  }

  /**
   * Handle touch end
   */
  private handleTouchEnd(e: TouchEvent, handlers: GestureHandler): void {
    if (!this.isTracking || !this.startTouch || !this.lastTouch) return;

    const endPoint = this.lastTouch;
    const duration = endPoint.timestamp - this.startTouch.timestamp;
    const distance = this.calculateDistance(this.startTouch, endPoint);
    const velocity = distance / duration;

    // Clear long press timer
    if (this.longPressTimer) {
      clearTimeout(this.longPressTimer);
      this.longPressTimer = null;
    }

    // Handle pull to refresh
    if (this.isPullToRefreshActive && handlers.onPullToRefresh) {
      if (this.pullDistance >= this.config.pullToRefresh.threshold) {
        this.triggerPullToRefresh(handlers.onPullToRefresh);
      } else {
        this.cancelPullToRefresh();
      }
    }

    // Handle swipe gesture
    else if (distance >= this.config.swipeThreshold && velocity >= this.config.velocityThreshold && handlers.onSwipe) {
      const deltaX = endPoint.x - this.startTouch.x;
      const deltaY = endPoint.y - this.startTouch.y;
      
      let direction: SwipeGesture['direction'];
      if (Math.abs(deltaX) > Math.abs(deltaY)) {
        direction = deltaX > 0 ? 'right' : 'left';
      } else {
        direction = deltaY > 0 ? 'down' : 'up';
      }

      const gesture: SwipeGesture = {
        direction,
        distance,
        velocity,
        duration
      };

      handlers.onSwipe(gesture);
      this.triggerHapticFeedback('light');
    }

    // Handle tap
    else if (distance < 10 && duration < 300 && handlers.onTap) {
      handlers.onTap(this.startTouch);
    }

    this.resetState();
  }

  /**
   * Handle touch cancel
   */
  private handleTouchCancel(): void {
    if (this.longPressTimer) {
      clearTimeout(this.longPressTimer);
      this.longPressTimer = null;
    }

    if (this.isPullToRefreshActive) {
      this.cancelPullToRefresh();
    }

    this.resetState();
  }

  /**
   * Calculate distance between two points
   */
  private calculateDistance(point1: { x: number; y: number }, point2: { x: number; y: number }): number {
    const deltaX = point2.x - point1.x;
    const deltaY = point2.y - point1.y;
    return Math.sqrt(deltaX * deltaX + deltaY * deltaY);
  }

  /**
   * Check if element is at top of scroll
   */
  private isAtTop(): boolean {
    if (!this.element) return false;
    return this.element.scrollTop <= 5; // Small threshold for floating point precision
  }

  /**
   * Update pull to refresh visual indicator
   */
  private updatePullToRefreshIndicator(distance: number): void {
    const indicator = document.getElementById('pull-refresh-indicator');
    if (!indicator) return;

    const progress = Math.min(distance / this.config.pullToRefresh.threshold, 1);
    const rotation = progress * 180;
    
    indicator.style.transform = `translateY(${distance * 0.5}px) rotate(${rotation}deg)`;
    indicator.style.opacity = `${Math.min(progress, 1)}`;
    
    // Change appearance when threshold reached
    if (progress >= 1) {
      indicator.classList.add('ready-to-refresh');
    } else {
      indicator.classList.remove('ready-to-refresh');
    }
  }

  /**
   * Trigger pull to refresh action
   */
  private async triggerPullToRefresh(refreshHandler: () => Promise<void>): Promise<void> {
    const indicator = document.getElementById('pull-refresh-indicator');
    if (indicator) {
      indicator.classList.add('refreshing');
      indicator.style.transform = 'translateY(40px) rotate(360deg)';
    }

    this.triggerHapticFeedback('medium');

    try {
      await refreshHandler();
    } catch (error) {
      console.error('❌ Pull to refresh failed:', error);
    } finally {
      this.cancelPullToRefresh();
    }
  }

  /**
   * Cancel pull to refresh and return to normal state
   */
  private cancelPullToRefresh(): void {
    const indicator = document.getElementById('pull-refresh-indicator');
    if (indicator) {
      indicator.style.transition = `transform ${this.config.pullToRefresh.snapBackDuration}ms ease-out`;
      indicator.style.transform = 'translateY(-40px) rotate(0deg)';
      indicator.style.opacity = '0';
      indicator.classList.remove('ready-to-refresh', 'refreshing');
      
      // Remove transition after animation
      setTimeout(() => {
        indicator.style.transition = '';
      }, this.config.pullToRefresh.snapBackDuration);
    }

    this.isPullToRefreshActive = false;
    this.pullDistance = 0;
  }

  /**
   * Trigger haptic feedback on supported devices
   */
  private triggerHapticFeedback(type: 'light' | 'medium' | 'heavy'): void {
    if (!this.config.enableHaptics) return;

    try {
      // @ts-ignore - HapticFeedback is not in TypeScript types yet
      if (window.navigator.vibrate) {
        const patterns = {
          light: [10],
          medium: [20],
          heavy: [30]
        };
        window.navigator.vibrate(patterns[type]);
      }
      
      // @ts-ignore - iOS haptic feedback
      if (window.DeviceMotionEvent && typeof DeviceMotionEvent.requestPermission === 'function') {
        // iOS haptic feedback implementation would go here
      }
    } catch (error) {
      // Haptic feedback not supported, silently continue
    }
  }

  /**
   * Reset gesture tracking state
   */
  private resetState(): void {
    this.startTouch = null;
    this.lastTouch = null;
    this.isTracking = false;
    this.pinchStartDistance = 0;
  }
}

// React hook for touch gestures
export const useTouchGestures = (
  handlers: GestureHandler,
  config: Partial<TouchGestureConfig> = {}
) => {
  const elementRef = useRef<HTMLElement>(null);
  const gestureManagerRef = useRef<TouchGestureManager | null>(null);

  const bindGestures = useCallback(() => {
    if (!elementRef.current) return;

    gestureManagerRef.current = new TouchGestureManager(config);
    const cleanup = gestureManagerRef.current.bindToElement(elementRef.current, handlers);

    return cleanup;
  }, [handlers, config]);

  useEffect(() => {
    const cleanup = bindGestures();
    return cleanup;
  }, [bindGestures]);

  return elementRef;
};

// React hook for pull-to-refresh specifically
export const usePullToRefresh = (
  onRefresh: () => Promise<void>,
  config: Partial<PullToRefreshConfig> = {}
) => {
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [pullDistance, setPullDistance] = useState(0);

  const handleRefresh = useCallback(async () => {
    setIsRefreshing(true);
    try {
      await onRefresh();
    } finally {
      setIsRefreshing(false);
      setPullDistance(0);
    }
  }, [onRefresh]);

  const gestureRef = useTouchGestures({
    onPullToRefresh: handleRefresh
  }, {
    pullToRefresh: { ...DEFAULT_CONFIG.pullToRefresh, ...config },
    swipeThreshold: 999999, // Disable swipe to focus on pull-to-refresh
    enableHaptics: true
  });

  return {
    gestureRef,
    isRefreshing,
    pullDistance
  };
};

export default TouchGestureManager;