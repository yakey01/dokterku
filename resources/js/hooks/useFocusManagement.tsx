import { useCallback, useEffect, useRef } from 'react';

/**
 * Hook for managing focus in accessible applications
 * Handles focus trapping, restoration, and keyboard navigation
 */
export const useFocusManagement = () => {
  const focusHistoryRef = useRef<HTMLElement[]>([]);
  const trapRefs = useRef<Set<HTMLElement>>(new Set());

  /**
   * Store current focus and move to target element
   * @param targetElement - Element to focus
   * @param storeFocus - Whether to store current focus for restoration
   */
  const moveFocus = useCallback((targetElement: HTMLElement | string, storeFocus = true) => {
    if (storeFocus && document.activeElement) {
      focusHistoryRef.current.push(document.activeElement as HTMLElement);
    }

    const element = typeof targetElement === 'string' 
      ? document.getElementById(targetElement) || document.querySelector(targetElement)
      : targetElement;

    if (element) {
      element.focus();
      
      // Ensure element is focusable
      if (element.tabIndex < 0 && !element.hasAttribute('tabindex')) {
        element.tabIndex = -1;
      }
    }
  }, []);

  /**
   * Restore focus to previous element
   */
  const restoreFocus = useCallback(() => {
    const previousElement = focusHistoryRef.current.pop();
    if (previousElement && document.contains(previousElement)) {
      previousElement.focus();
    }
  }, []);

  /**
   * Create a focus trap within a container
   * @param container - Container element to trap focus within
   */
  const createFocusTrap = useCallback((container: HTMLElement) => {
    const focusableElements = container.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"]), [role="button"], [role="link"]'
    );

    const firstFocusable = focusableElements[0] as HTMLElement;
    const lastFocusable = focusableElements[focusableElements.length - 1] as HTMLElement;

    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Tab') {
        if (event.shiftKey) {
          // Shift + Tab
          if (document.activeElement === firstFocusable) {
            event.preventDefault();
            lastFocusable?.focus();
          }
        } else {
          // Tab
          if (document.activeElement === lastFocusable) {
            event.preventDefault();
            firstFocusable?.focus();
          }
        }
      }

      if (event.key === 'Escape') {
        event.preventDefault();
        event.stopPropagation();
        releaseFocusTrap(container);
      }
    };

    container.addEventListener('keydown', handleKeyDown);
    trapRefs.current.add(container);

    // Focus first element
    if (firstFocusable) {
      firstFocusable.focus();
    }

    return () => {
      container.removeEventListener('keydown', handleKeyDown);
      trapRefs.current.delete(container);
    };
  }, []);

  /**
   * Release focus trap
   * @param container - Container to release trap from
   */
  const releaseFocusTrap = useCallback((container: HTMLElement) => {
    trapRefs.current.delete(container);
    restoreFocus();
  }, [restoreFocus]);

  /**
   * Handle modal/dialog focus management
   * @param modalElement - Modal container element
   */
  const manageModalFocus = useCallback((modalElement: HTMLElement) => {
    // Store current focus
    const activeElement = document.activeElement as HTMLElement;
    if (activeElement) {
      focusHistoryRef.current.push(activeElement);
    }

    // Set up focus trap
    const releaseTrap = createFocusTrap(modalElement);

    // Return cleanup function
    return () => {
      releaseTrap();
      restoreFocus();
    };
  }, [createFocusTrap, restoreFocus]);

  /**
   * Manage focus for search/combobox components
   * @param inputElement - Input element
   * @param listboxElement - Listbox element (optional)
   */
  const manageComboboxFocus = useCallback((
    inputElement: HTMLElement,
    listboxElement?: HTMLElement,
    isExpanded = false
  ) => {
    const handleKeyDown = (event: KeyboardEvent) => {
      if (!listboxElement || !isExpanded) return;

      const options = listboxElement.querySelectorAll('[role="option"]');
      const currentIndex = Array.from(options).findIndex(
        option => option.getAttribute('aria-selected') === 'true'
      );

      switch (event.key) {
        case 'ArrowDown':
          event.preventDefault();
          const nextIndex = Math.min(currentIndex + 1, options.length - 1);
          updateOptionSelection(options, nextIndex);
          break;

        case 'ArrowUp':
          event.preventDefault();
          const prevIndex = Math.max(currentIndex - 1, 0);
          updateOptionSelection(options, prevIndex);
          break;

        case 'Home':
          event.preventDefault();
          updateOptionSelection(options, 0);
          break;

        case 'End':
          event.preventDefault();
          updateOptionSelection(options, options.length - 1);
          break;

        case 'Escape':
          event.preventDefault();
          inputElement.focus();
          break;

        case 'Enter':
        case ' ':
          event.preventDefault();
          const selectedOption = options[currentIndex] as HTMLElement;
          selectedOption?.click();
          break;
      }
    };

    inputElement.addEventListener('keydown', handleKeyDown);

    return () => {
      inputElement.removeEventListener('keydown', handleKeyDown);
    };
  }, []);

  /**
   * Update option selection in listbox
   */
  const updateOptionSelection = useCallback((options: NodeListOf<Element>, index: number) => {
    options.forEach((option, i) => {
      option.setAttribute('aria-selected', i === index ? 'true' : 'false');
      if (i === index) {
        (option as HTMLElement).scrollIntoView({ block: 'nearest' });
      }
    });
  }, []);

  /**
   * Handle roving tabindex for component groups
   * @param container - Container with focusable items
   * @param orientation - Navigation orientation
   */
  const manageRovingTabindex = useCallback((
    container: HTMLElement,
    orientation: 'horizontal' | 'vertical' | 'both' = 'horizontal'
  ) => {
    const items = container.querySelectorAll('[role="tab"], [role="menuitem"], [role="option"]');
    let currentIndex = 0;

    // Set initial tabindex
    items.forEach((item, index) => {
      (item as HTMLElement).tabIndex = index === 0 ? 0 : -1;
    });

    const handleKeyDown = (event: KeyboardEvent) => {
      const { key } = event;
      let newIndex = currentIndex;

      const isHorizontalKey = key === 'ArrowLeft' || key === 'ArrowRight';
      const isVerticalKey = key === 'ArrowUp' || key === 'ArrowDown';

      if (
        (orientation === 'horizontal' && isHorizontalKey) ||
        (orientation === 'vertical' && isVerticalKey) ||
        (orientation === 'both' && (isHorizontalKey || isVerticalKey))
      ) {
        event.preventDefault();

        if (key === 'ArrowRight' || key === 'ArrowDown') {
          newIndex = (currentIndex + 1) % items.length;
        } else if (key === 'ArrowLeft' || key === 'ArrowUp') {
          newIndex = (currentIndex - 1 + items.length) % items.length;
        } else if (key === 'Home') {
          newIndex = 0;
        } else if (key === 'End') {
          newIndex = items.length - 1;
        }

        // Update tabindex
        (items[currentIndex] as HTMLElement).tabIndex = -1;
        (items[newIndex] as HTMLElement).tabIndex = 0;
        (items[newIndex] as HTMLElement).focus();

        currentIndex = newIndex;
      }
    };

    container.addEventListener('keydown', handleKeyDown);

    return () => {
      container.removeEventListener('keydown', handleKeyDown);
    };
  }, []);

  /**
   * Clear focus history
   */
  const clearFocusHistory = useCallback(() => {
    focusHistoryRef.current = [];
  }, []);

  /**
   * Get focusable elements within a container
   */
  const getFocusableElements = useCallback((container: HTMLElement) => {
    return container.querySelectorAll(
      'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"]):not([disabled]), [role="button"]:not([aria-disabled="true"]), [role="link"]:not([aria-disabled="true"])'
    );
  }, []);

  /**
   * Skip to main content functionality
   */
  const skipToMainContent = useCallback(() => {
    const mainContent = document.getElementById('main-content') || 
                       document.querySelector('main') ||
                       document.querySelector('[role="main"]');
    
    if (mainContent) {
      (mainContent as HTMLElement).focus();
      (mainContent as HTMLElement).scrollIntoView({ behavior: 'smooth' });
    }
  }, []);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      trapRefs.current.clear();
      clearFocusHistory();
    };
  }, [clearFocusHistory]);

  return {
    moveFocus,
    restoreFocus,
    createFocusTrap,
    releaseFocusTrap,
    manageModalFocus,
    manageComboboxFocus,
    manageRovingTabindex,
    clearFocusHistory,
    getFocusableElements,
    skipToMainContent
  };
};

export default useFocusManagement;