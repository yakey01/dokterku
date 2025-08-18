/**
 * Badge Management Hook
 * Manages gaming badge states, animations, and interactions
 */

import { useState, useCallback, useRef, useEffect } from 'react';
import { BadgeConfig, JaspelStatus, JaspelVariant } from './types';
import { 
  getStatusBadge, 
  getComplexityBadge, 
  getGamingBadgeConfig 
} from './utils';
import { GamingBadgeVariants } from '../../components/ui/GamingBadge';

interface BadgeAnimation {
  id: string;
  type: 'glow' | 'pulse' | 'bounce' | 'shake' | 'celebration';
  duration: number;
  intensity?: 'low' | 'medium' | 'high';
}

interface BadgeManagerOptions {
  variant: JaspelVariant;
  enableAnimations?: boolean;
  autoHideDelay?: number;
  celebrationDuration?: number;
}

export const useBadgeManager = (options: BadgeManagerOptions) => {
  const {
    variant,
    enableAnimations = true,
    autoHideDelay = 5000,
    celebrationDuration = 3000
  } = options;

  const [activeBadges, setActiveBadges] = useState<Map<string, BadgeConfig>>(new Map());
  const [activeAnimations, setActiveAnimations] = useState<Map<string, BadgeAnimation>>(new Map());
  const [badgeQueue, setBadgeQueue] = useState<Array<{ id: string; config: BadgeConfig }>>([]);
  
  const timeoutRefs = useRef<Map<string, NodeJS.Timeout>>(new Map());
  const animationRefs = useRef<Map<string, NodeJS.Timeout>>(new Map());

  // Cleanup timeouts on unmount
  useEffect(() => {
    return () => {
      timeoutRefs.current.forEach(timeout => clearTimeout(timeout));
      animationRefs.current.forEach(timeout => clearTimeout(timeout));
    };
  }, []);

  /**
   * Add a new badge with optional auto-hide
   */
  const addBadge = useCallback((
    id: string, 
    config: BadgeConfig, 
    options: {
      autoHide?: boolean;
      animation?: BadgeAnimation['type'];
      animationIntensity?: BadgeAnimation['intensity'];
    } = {}
  ) => {
    const { autoHide = true, animation, animationIntensity = 'medium' } = options;

    // Clear existing timeout for this badge
    const existingTimeout = timeoutRefs.current.get(id);
    if (existingTimeout) {
      clearTimeout(existingTimeout);
    }

    // Add badge to active badges
    setActiveBadges(prev => new Map(prev.set(id, {
      ...config,
      animated: enableAnimations && config.animated !== false
    })));

    // Add animation if specified
    if (enableAnimations && animation) {
      addAnimation(id, {
        id,
        type: animation,
        duration: animation === 'celebration' ? celebrationDuration : 1000,
        intensity: animationIntensity
      });
    }

    // Auto-hide if enabled
    if (autoHide) {
      const timeout = setTimeout(() => {
        removeBadge(id);
      }, autoHideDelay);
      
      timeoutRefs.current.set(id, timeout);
    }
  }, [enableAnimations, autoHideDelay, celebrationDuration]);

  /**
   * Remove a badge
   */
  const removeBadge = useCallback((id: string) => {
    setActiveBadges(prev => {
      const newMap = new Map(prev);
      newMap.delete(id);
      return newMap;
    });

    // Clear associated timeout and animation
    const timeout = timeoutRefs.current.get(id);
    if (timeout) {
      clearTimeout(timeout);
      timeoutRefs.current.delete(id);
    }

    removeAnimation(id);
  }, []);

  /**
   * Add animation to a badge
   */
  const addAnimation = useCallback((id: string, animation: BadgeAnimation) => {
    setActiveAnimations(prev => new Map(prev.set(id, animation)));

    // Auto-remove animation after duration
    const timeout = setTimeout(() => {
      removeAnimation(id);
    }, animation.duration);

    animationRefs.current.set(id, timeout);
  }, []);

  /**
   * Remove animation from a badge
   */
  const removeAnimation = useCallback((id: string) => {
    setActiveAnimations(prev => {
      const newMap = new Map(prev);
      newMap.delete(id);
      return newMap;
    });

    const timeout = animationRefs.current.get(id);
    if (timeout) {
      clearTimeout(timeout);
      animationRefs.current.delete(id);
    }
  }, []);

  /**
   * Clear all badges
   */
  const clearAllBadges = useCallback(() => {
    setActiveBadges(new Map());
    setActiveAnimations(new Map());
    setBadgeQueue([]);
    
    // Clear all timeouts
    timeoutRefs.current.forEach(timeout => clearTimeout(timeout));
    animationRefs.current.forEach(timeout => clearTimeout(timeout));
    timeoutRefs.current.clear();
    animationRefs.current.clear();
  }, []);

  /**
   * Show status badge with appropriate styling
   */
  const showStatusBadge = useCallback((
    id: string, 
    status: JaspelStatus, 
    options: {
      autoHide?: boolean;
      celebration?: boolean;
    } = {}
  ) => {
    const config = getStatusBadge(status, variant);
    const animation = options.celebration ? 'celebration' : 
                     status === 'disetujui' || status === 'paid' ? 'glow' : undefined;

    addBadge(id, config, {
      autoHide: options.autoHide,
      animation,
      animationIntensity: options.celebration ? 'high' : 'medium'
    });
  }, [variant, addBadge]);

  /**
   * Show achievement badge with celebration animation
   */
  const showAchievementBadge = useCallback((
    id: string, 
    achievementType: string, 
    customText?: string
  ) => {
    const config = getGamingBadgeConfig(achievementType, true);
    
    if (customText) {
      config.text = customText;
    }

    addBadge(id, config, {
      autoHide: true,
      animation: 'celebration',
      animationIntensity: 'high'
    });
  }, [addBadge]);

  /**
   * Show gaming badge using predefined variants
   */
  const showGamingBadge = useCallback((
    id: string, 
    variantType: keyof typeof GamingBadgeVariants,
    options: {
      autoHide?: boolean;
      animation?: BadgeAnimation['type'];
      customText?: string;
    } = {}
  ) => {
    const variantFunction = GamingBadgeVariants[variantType];
    if (!variantFunction) {
      console.warn(`Unknown gaming badge variant: ${variantType}`);
      return;
    }

    let config: BadgeConfig;
    
    // Handle variants that require parameters
    if (variantType.startsWith('status')) {
      config = (variantFunction as (variant: JaspelVariant) => BadgeConfig)(variant);
    } else {
      config = (variantFunction as (animated?: boolean) => BadgeConfig)(true);
    }

    if (options.customText) {
      config.text = options.customText;
    }

    addBadge(id, config, {
      autoHide: options.autoHide,
      animation: options.animation
    });
  }, [variant, addBadge]);

  /**
   * Queue multiple badges to show in sequence
   */
  const queueBadges = useCallback((badges: Array<{
    id: string;
    config: BadgeConfig;
    delay?: number;
  }>) => {
    badges.forEach((badge, index) => {
      const delay = badge.delay || (index * 500); // Default 500ms between badges
      
      setTimeout(() => {
        addBadge(badge.id, badge.config);
      }, delay);
    });
  }, [addBadge]);

  /**
   * Update badge configuration
   */
  const updateBadge = useCallback((id: string, updates: Partial<BadgeConfig>) => {
    setActiveBadges(prev => {
      const existing = prev.get(id);
      if (!existing) return prev;
      
      const newMap = new Map(prev);
      newMap.set(id, { ...existing, ...updates });
      return newMap;
    });
  }, []);

  /**
   * Get current animation for a badge
   */
  const getBadgeAnimation = useCallback((id: string): BadgeAnimation | undefined => {
    return activeAnimations.get(id);
  }, [activeAnimations]);

  /**
   * Check if badge is currently active
   */
  const isBadgeActive = useCallback((id: string): boolean => {
    return activeBadges.has(id);
  }, [activeBadges]);

  /**
   * Get all active badge IDs
   */
  const getActiveBadgeIds = useCallback((): string[] => {
    return Array.from(activeBadges.keys());
  }, [activeBadges]);

  return {
    // State
    activeBadges: Array.from(activeBadges.entries()).map(([id, config]) => ({
      id,
      config,
      animation: activeAnimations.get(id)
    })),
    badgeCount: activeBadges.size,
    animationCount: activeAnimations.size,

    // Basic operations
    addBadge,
    removeBadge,
    updateBadge,
    clearAllBadges,

    // Specialized badge functions
    showStatusBadge,
    showAchievementBadge,
    showGamingBadge,
    queueBadges,

    // Animation management
    addAnimation,
    removeAnimation,
    getBadgeAnimation,

    // Utility functions
    isBadgeActive,
    getActiveBadgeIds,

    // Configuration
    variant,
    enableAnimations
  };
};