import React from 'react';
import { performanceMonitor, usePerformanceMonitor } from '../../../utils/PerformanceMonitor';

/**
 * Optimized component patterns and memoization strategies
 * Demonstrates best practices for React performance optimization
 */

// Expensive computation simulation
const expensiveCalculation = (value: number): number => {
  // Simulate heavy computation
  let result = value;
  for (let i = 0; i < 100000; i++) {
    result += Math.sin(i) * 0.001;
  }
  return Math.round(result * 100) / 100;
};

// 1. Memoized expensive calculations
export const useMemoizedCalculation = (value: number) => {
  return React.useMemo(() => {
    performanceMonitor.start('expensive-calculation');
    const result = expensiveCalculation(value);
    performanceMonitor.end('expensive-calculation');
    return result;
  }, [value]);
};

// 2. Optimized event handlers with useCallback
export const useOptimizedHandlers = (onUpdate?: (data: any) => void) => {
  const handleRefresh = React.useCallback(async () => {
    performanceMonitor.start('refresh-action');
    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 100));
      onUpdate?.({ timestamp: Date.now() });
      performanceMonitor.end('refresh-action', 'success');
    } catch (error) {
      performanceMonitor.end('refresh-action', 'error');
    }
  }, [onUpdate]);

  const handleSearch = React.useCallback((query: string) => {
    performanceMonitor.start('search-action');
    // Debounced search logic would go here
    console.log('Searching for:', query);
    performanceMonitor.end('search-action');
  }, []);

  return { handleRefresh, handleSearch };
};

// 3. Memoized styles and computed values
export const useMemoizedStyles = (isActive: boolean, variant: 'primary' | 'secondary') => {
  const styles = React.useMemo(() => ({
    container: {
      backgroundColor: isActive ? '#6366f1' : '#374151',
      color: isActive ? '#ffffff' : '#d1d5db',
      padding: variant === 'primary' ? '16px' : '12px',
      borderRadius: variant === 'primary' ? '12px' : '8px',
      transition: 'all 0.2s ease-in-out',
    },
    icon: {
      transform: isActive ? 'scale(1.1)' : 'scale(1)',
      opacity: isActive ? 1 : 0.7,
    },
  }), [isActive, variant]);

  const classNames = React.useMemo(() => ({
    container: `
      transition-all duration-200 ease-in-out
      ${isActive ? 'bg-indigo-600 text-white shadow-lg' : 'bg-gray-700 text-gray-300'}
      ${variant === 'primary' ? 'p-4 rounded-xl' : 'p-3 rounded-lg'}
    `.trim().replace(/\s+/g, ' '),
    icon: `
      transition-transform duration-200
      ${isActive ? 'scale-110 opacity-100' : 'scale-100 opacity-70'}
    `.trim().replace(/\s+/g, ' '),
  }), [isActive, variant]);

  return { styles, classNames };
};

// 4. Performance-optimized list item with custom comparison
interface OptimizedListItemProps {
  id: string;
  title: string;
  subtitle: string;
  value: number;
  isSelected: boolean;
  onClick: (id: string) => void;
}

export const OptimizedListItem: React.FC<OptimizedListItemProps> = React.memo(({
  id,
  title,
  subtitle,
  value,
  isSelected,
  onClick
}) => {
  usePerformanceMonitor(`OptimizedListItem-${id}`);
  
  const memoizedValue = useMemoizedCalculation(value);
  const { classNames } = useMemoizedStyles(isSelected, 'secondary');
  
  const handleClick = React.useCallback(() => {
    onClick(id);
  }, [id, onClick]);

  return (
    <div 
      className={classNames.container}
      onClick={handleClick}
      role="button"
      tabIndex={0}
    >
      <div className="flex items-center justify-between">
        <div>
          <h3 className="font-semibold">{title}</h3>
          <p className="text-sm opacity-80">{subtitle}</p>
        </div>
        <div className="text-right">
          <div className="font-bold text-lg">{memoizedValue.toLocaleString()}</div>
          <div className={classNames.icon}>⭐</div>
        </div>
      </div>
    </div>
  );
}, (prevProps, nextProps) => {
  // Custom comparison function for optimal re-rendering
  return (
    prevProps.id === nextProps.id &&
    prevProps.title === nextProps.title &&
    prevProps.subtitle === nextProps.subtitle &&
    prevProps.value === nextProps.value &&
    prevProps.isSelected === nextProps.isSelected
    // Note: We don't compare onClick as it should be memoized by parent
  );
});

OptimizedListItem.displayName = 'OptimizedListItem';

// 5. Virtualized list for large datasets
interface VirtualizedListProps {
  items: Array<{
    id: string;
    title: string;
    subtitle: string;
    value: number;
  }>;
  selectedId?: string;
  onItemClick: (id: string) => void;
  itemHeight?: number;
  containerHeight?: number;
}

export const VirtualizedList: React.FC<VirtualizedListProps> = React.memo(({
  items,
  selectedId,
  onItemClick,
  itemHeight = 80,
  containerHeight = 400
}) => {
  const [scrollTop, setScrollTop] = React.useState(0);
  
  const visibleItemsCount = Math.ceil(containerHeight / itemHeight);
  const startIndex = Math.floor(scrollTop / itemHeight);
  const endIndex = Math.min(startIndex + visibleItemsCount + 1, items.length);
  
  const visibleItems = React.useMemo(() => {
    return items.slice(startIndex, endIndex).map((item, index) => ({
      ...item,
      index: startIndex + index,
    }));
  }, [items, startIndex, endIndex]);

  const handleScroll = React.useCallback((e: React.UIEvent<HTMLDivElement>) => {
    setScrollTop(e.currentTarget.scrollTop);
  }, []);

  const totalHeight = items.length * itemHeight;
  const offsetY = startIndex * itemHeight;

  return (
    <div 
      className="overflow-auto"
      style={{ height: containerHeight }}
      onScroll={handleScroll}
    >
      <div style={{ height: totalHeight, position: 'relative' }}>
        <div style={{ transform: `translateY(${offsetY}px)` }}>
          {visibleItems.map((item) => (
            <div key={item.id} style={{ height: itemHeight }}>
              <OptimizedListItem
                id={item.id}
                title={item.title}
                subtitle={item.subtitle}
                value={item.value}
                isSelected={item.id === selectedId}
                onClick={onItemClick}
              />
            </div>
          ))}
        </div>
      </div>
    </div>
  );
});

VirtualizedList.displayName = 'VirtualizedList';

// 6. Performance comparison component (for testing)
export const PerformanceComparison: React.FC = () => {
  const [optimizedCount, setOptimizedCount] = React.useState(0);
  const [unoptimizedCount, setUnoptimizedCount] = React.useState(0);
  
  // Optimized version with memoization
  const OptimizedCounter = React.memo(({ count }: { count: number }) => {
    const expensiveValue = useMemoizedCalculation(count);
    return (
      <div className="p-4 bg-green-500/20 rounded-lg border border-green-500/30">
        <h3 className="font-bold text-green-300">Optimized (Memoized)</h3>
        <p>Count: {count}</p>
        <p>Expensive calc: {expensiveValue}</p>
      </div>
    );
  });

  // Unoptimized version - recalculates every render
  const UnoptimizedCounter = ({ count }: { count: number }) => {
    const expensiveValue = expensiveCalculation(count); // No memoization!
    return (
      <div className="p-4 bg-red-500/20 rounded-lg border border-red-500/30">
        <h3 className="font-bold text-red-300">Unoptimized (No Memo)</h3>
        <p>Count: {count}</p>
        <p>Expensive calc: {expensiveValue}</p>
      </div>
    );
  };

  return (
    <div className="p-6 space-y-4">
      <h2 className="text-xl font-bold text-white">Performance Comparison</h2>
      
      <div className="grid grid-cols-2 gap-4">
        <OptimizedCounter count={optimizedCount} />
        <UnoptimizedCounter count={unoptimizedCount} />
      </div>
      
      <div className="flex space-x-4">
        <button
          onClick={() => setOptimizedCount(c => c + 1)}
          className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
        >
          Increment Optimized
        </button>
        <button
          onClick={() => setUnoptimizedCount(c => c + 1)}
          className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
        >
          Increment Unoptimized
        </button>
      </div>
      
      <p className="text-gray-300 text-sm">
        Open DevTools Console to see performance differences. 
        The optimized version should be significantly faster on subsequent renders.
      </p>
    </div>
  );
};

// 7. HOC for performance monitoring
export const withPerformanceMonitoring = <P extends object>(
  Component: React.ComponentType<P>,
  componentName: string
) => {
  const WrappedComponent = React.forwardRef<any, P>((props, ref) => {
    usePerformanceMonitor(componentName);
    return <Component {...props} ref={ref} />;
  });
  
  WrappedComponent.displayName = `withPerformanceMonitoring(${componentName})`;
  return React.memo(WrappedComponent);
};

export default OptimizedListItem;