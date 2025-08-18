import React, { useState, useCallback } from 'react';
import { 
  X, 
  Filter, 
  Calendar, 
  ChevronDown, 
  Check, 
  Trash2,
  RefreshCw,
  Sliders,
  Tag,
  ToggleLeft,
  ToggleRight
} from 'lucide-react';
import type { FilterConfig } from '../../../utils/SearchFilterManager';

interface FilterValue {
  value: any;
  label: string;
  count?: number;
}

interface AdvancedFilterPanelProps {
  isOpen: boolean;
  filters: Record<string, any>;
  filterConfigs: FilterConfig[];
  onFilterChange: (key: string, value: any) => void;
  onClearFilters: () => void;
  onClose: () => void;
  getFilterValues?: (key: string) => FilterValue[];
  className?: string;
}

const AdvancedFilterPanel: React.FC<AdvancedFilterPanelProps> = ({
  isOpen,
  filters,
  filterConfigs,
  onFilterChange,
  onClearFilters,
  onClose,
  getFilterValues,
  className = ''
}) => {
  const [expandedFilters, setExpandedFilters] = useState<Set<string>>(new Set());
  const [dateInputs, setDateInputs] = useState<Record<string, { start: string; end: string }>>({});

  const toggleFilterExpansion = useCallback((filterKey: string) => {
    setExpandedFilters(prev => {
      const newSet = new Set(prev);
      if (newSet.has(filterKey)) {
        newSet.delete(filterKey);
      } else {
        newSet.add(filterKey);
      }
      return newSet;
    });
  }, []);

  const handleRangeChange = useCallback((filterKey: string, type: 'min' | 'max', value: string) => {
    const currentFilter = filters[filterKey] || {};
    const newFilter = {
      ...currentFilter,
      [type]: value ? Number(value) : undefined
    };
    
    // Remove empty range filters
    if (!newFilter.min && !newFilter.max) {
      onFilterChange(filterKey, null);
    } else {
      onFilterChange(filterKey, newFilter);
    }
  }, [filters, onFilterChange]);

  const handleDateRangeChange = useCallback((filterKey: string, type: 'start' | 'end', value: string) => {
    setDateInputs(prev => ({
      ...prev,
      [filterKey]: {
        ...prev[filterKey],
        [type]: value
      }
    }));

    const currentInputs = { ...dateInputs[filterKey], [type]: value };
    
    if (currentInputs.start && currentInputs.end) {
      onFilterChange(filterKey, {
        start: currentInputs.start,
        end: currentInputs.end
      });
    } else if (currentInputs.start || currentInputs.end) {
      onFilterChange(filterKey, currentInputs.start || currentInputs.end);
    } else {
      onFilterChange(filterKey, null);
    }
  }, [dateInputs, onFilterChange]);

  const renderFilterInput = (config: FilterConfig) => {
    const currentValue = filters[config.key];
    const isExpanded = expandedFilters.has(config.key);

    switch (config.type) {
      case 'text':
        return (
          <input
            type="text"
            value={currentValue || ''}
            onChange={(e) => onFilterChange(config.key, e.target.value || null)}
            placeholder={config.placeholder || `Filter by ${config.label.toLowerCase()}...`}
            className="
              w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md
              text-white placeholder-gray-400 text-sm
              focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
            "
          />
        );

      case 'select':
        const selectOptions = config.options || (getFilterValues?.(config.key) || []);
        const selectedValues = Array.isArray(currentValue) ? currentValue : (currentValue ? [currentValue] : []);

        return (
          <div className="space-y-2">
            <button
              onClick={() => toggleFilterExpansion(config.key)}
              className="
                w-full flex items-center justify-between px-3 py-2
                bg-gray-700 border border-gray-600 rounded-md text-white text-sm
                hover:bg-gray-600 transition-colors
              "
            >
              <span>
                {selectedValues.length > 0 
                  ? `${selectedValues.length} selected`
                  : config.placeholder || 'Select options...'
                }
              </span>
              <ChevronDown className={`w-4 h-4 transition-transform ${isExpanded ? 'rotate-180' : ''}`} />
            </button>

            {isExpanded && (
              <div className="max-h-40 overflow-y-auto bg-gray-800 border border-gray-600 rounded-md">
                {selectOptions.map((option) => {
                  const isSelected = selectedValues.includes(option.value);
                  
                  return (
                    <button
                      key={option.value}
                      onClick={() => {
                        if (config.multiple) {
                          const newValues = isSelected 
                            ? selectedValues.filter(v => v !== option.value)
                            : [...selectedValues, option.value];
                          onFilterChange(config.key, newValues.length > 0 ? newValues : null);
                        } else {
                          onFilterChange(config.key, isSelected ? null : option.value);
                        }
                      }}
                      className="
                        w-full flex items-center justify-between px-3 py-2
                        hover:bg-gray-700 transition-colors text-left
                      "
                    >
                      <div className="flex items-center space-x-2">
                        <div className={`
                          w-4 h-4 rounded border-2 flex items-center justify-center
                          ${isSelected 
                            ? 'bg-blue-600 border-blue-600' 
                            : 'border-gray-500'
                          }
                        `}>
                          {isSelected && <Check className="w-3 h-3 text-white" />}
                        </div>
                        <span className="text-white text-sm">{option.label}</span>
                      </div>
                      {option.count !== undefined && (
                        <span className="text-gray-400 text-xs">{option.count}</span>
                      )}
                    </button>
                  );
                })}
              </div>
            )}
          </div>
        );

      case 'date':
        return (
          <div className="space-y-2">
            <input
              type="date"
              value={dateInputs[config.key]?.start || ''}
              onChange={(e) => handleDateRangeChange(config.key, 'start', e.target.value)}
              className="
                w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md
                text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
              "
            />
            <div className="text-center text-gray-400 text-xs">to</div>
            <input
              type="date"
              value={dateInputs[config.key]?.end || ''}
              onChange={(e) => handleDateRangeChange(config.key, 'end', e.target.value)}
              className="
                w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md
                text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
              "
            />
          </div>
        );

      case 'range':
        return (
          <div className="space-y-2">
            <div className="flex items-center space-x-2">
              <input
                type="number"
                placeholder="Min"
                value={currentValue?.min || ''}
                onChange={(e) => handleRangeChange(config.key, 'min', e.target.value)}
                className="
                  flex-1 px-3 py-2 bg-gray-700 border border-gray-600 rounded-md
                  text-white placeholder-gray-400 text-sm
                  focus:outline-none focus:ring-2 focus:ring-blue-500
                "
              />
              <span className="text-gray-400">-</span>
              <input
                type="number"
                placeholder="Max"
                value={currentValue?.max || ''}
                onChange={(e) => handleRangeChange(config.key, 'max', e.target.value)}
                className="
                  flex-1 px-3 py-2 bg-gray-700 border border-gray-600 rounded-md
                  text-white placeholder-gray-400 text-sm
                  focus:outline-none focus:ring-2 focus:ring-blue-500
                "
              />
            </div>
          </div>
        );

      case 'boolean':
        return (
          <button
            onClick={() => onFilterChange(config.key, currentValue ? null : true)}
            className="
              flex items-center space-x-3 w-full px-3 py-2
              bg-gray-700 border border-gray-600 rounded-md
              hover:bg-gray-600 transition-colors
            "
          >
            {currentValue ? (
              <ToggleRight className="w-5 h-5 text-blue-400" />
            ) : (
              <ToggleLeft className="w-5 h-5 text-gray-400" />
            )}
            <span className="text-white text-sm">
              {currentValue ? 'Enabled' : 'Disabled'}
            </span>
          </button>
        );

      case 'tags':
        const tagOptions = config.options || (getFilterValues?.(config.key) || []);
        const selectedTags = Array.isArray(currentValue) ? currentValue : (currentValue ? [currentValue] : []);

        return (
          <div className="space-y-2">
            <div className="flex flex-wrap gap-1">
              {tagOptions.map((tag) => {
                const isSelected = selectedTags.includes(tag.value);
                
                return (
                  <button
                    key={tag.value}
                    onClick={() => {
                      const newTags = isSelected 
                        ? selectedTags.filter(t => t !== tag.value)
                        : [...selectedTags, tag.value];
                      onFilterChange(config.key, newTags.length > 0 ? newTags : null);
                    }}
                    className={`
                      flex items-center space-x-1 px-2 py-1 rounded-full text-xs
                      transition-all duration-200
                      ${isSelected 
                        ? 'bg-blue-600 text-white' 
                        : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
                      }
                    `}
                  >
                    <Tag className="w-3 h-3" />
                    <span>{tag.label}</span>
                    {tag.count !== undefined && (
                      <span className={`
                        px-1 rounded-full text-xs
                        ${isSelected ? 'bg-blue-500' : 'bg-gray-600'}
                      `}>
                        {tag.count}
                      </span>
                    )}
                  </button>
                );
              })}
            </div>
          </div>
        );

      default:
        return null;
    }
  };

  const getFilterIcon = (type: string) => {
    switch (type) {
      case 'date':
        return <Calendar className="w-4 h-4" />;
      case 'range':
        return <Sliders className="w-4 h-4" />;
      case 'tags':
        return <Tag className="w-4 h-4" />;
      case 'boolean':
        return <ToggleLeft className="w-4 h-4" />;
      default:
        return <Filter className="w-4 h-4" />;
    }
  };

  const activeFiltersCount = Object.values(filters).filter(value => 
    value !== null && value !== undefined && value !== ''
  ).length;

  if (!isOpen) return null;

  return (
    <div className={`
      fixed inset-0 z-50 lg:relative lg:inset-auto
      ${className}
    `}>
      {/* Backdrop (mobile only) */}
      <div 
        className="fixed inset-0 bg-black/50 backdrop-blur-sm lg:hidden"
        onClick={onClose}
      />

      {/* Panel */}
      <div className="
        fixed right-0 top-0 bottom-0 w-full max-w-sm bg-gray-900 shadow-xl
        lg:relative lg:w-auto lg:min-w-[300px] lg:rounded-lg lg:border lg:border-gray-700
        overflow-y-auto
      ">
        {/* Header */}
        <div className="flex items-center justify-between p-4 border-b border-gray-700">
          <div className="flex items-center space-x-2">
            <Filter className="w-5 h-5 text-blue-400" />
            <h3 className="text-lg font-semibold text-white">Filters</h3>
            {activeFiltersCount > 0 && (
              <span className="px-2 py-1 bg-blue-600 text-white text-xs rounded-full">
                {activeFiltersCount}
              </span>
            )}
          </div>

          <div className="flex items-center space-x-2">
            {/* Clear All */}
            {activeFiltersCount > 0 && (
              <button
                onClick={onClearFilters}
                className="
                  p-2 text-gray-400 hover:text-white transition-colors
                  hover:bg-gray-700 rounded-md
                "
                title="Clear all filters"
              >
                <Trash2 className="w-4 h-4" />
              </button>
            )}

            {/* Close */}
            <button
              onClick={onClose}
              className="
                p-2 text-gray-400 hover:text-white transition-colors
                hover:bg-gray-700 rounded-md lg:hidden
              "
            >
              <X className="w-4 h-4" />
            </button>
          </div>
        </div>

        {/* Filter Controls */}
        <div className="p-4 space-y-6">
          {filterConfigs.map((config) => (
            <div key={config.key} className="space-y-2">
              <div className="flex items-center justify-between">
                <label className="flex items-center space-x-2 text-sm font-medium text-white">
                  {getFilterIcon(config.type)}
                  <span>{config.label}</span>
                </label>

                {/* Clear individual filter */}
                {filters[config.key] && (
                  <button
                    onClick={() => onFilterChange(config.key, null)}
                    className="p-1 text-gray-400 hover:text-white transition-colors"
                    title={`Clear ${config.label} filter`}
                  >
                    <X className="w-3 h-3" />
                  </button>
                )}
              </div>

              {renderFilterInput(config)}
            </div>
          ))}

          {/* No filters message */}
          {filterConfigs.length === 0 && (
            <div className="text-center py-8">
              <Filter className="w-12 h-12 text-gray-600 mx-auto mb-3" />
              <div className="text-gray-400 text-sm">
                No filters available for this view
              </div>
            </div>
          )}
        </div>

        {/* Footer (mobile only) */}
        <div className="sticky bottom-0 bg-gray-900 border-t border-gray-700 p-4 lg:hidden">
          <div className="flex space-x-3">
            <button
              onClick={onClearFilters}
              className="
                flex-1 px-4 py-2 bg-gray-700 text-white rounded-lg
                hover:bg-gray-600 transition-colors
              "
            >
              Clear All
            </button>
            <button
              onClick={onClose}
              className="
                flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg
                hover:bg-blue-700 transition-colors
              "
            >
              Apply Filters
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default React.memo(AdvancedFilterPanel);