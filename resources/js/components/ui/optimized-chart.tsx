"use client";

import * as React from "react";
import * as RechartsPrimitive from "recharts";
import OptimizedResizeObserver from "../../utils/OptimizedResizeObserver";
import { cn } from "./utils";

// Format: { THEME_NAME: CSS_SELECTOR }
const THEMES = { light: "", dark: ".dark" } as const;

export type ChartConfig = {
  [k in string]: {
    label?: React.ReactNode;
    icon?: React.ComponentType;
  } & (
    | { color?: string; theme?: never }
    | { color?: never; theme: Record<keyof typeof THEMES, string> }
  );
};

type ChartContextProps = {
  config: ChartConfig;
};

const ChartContext = React.createContext<ChartContextProps | null>(null);

function useChart() {
  const context = React.useContext(ChartContext);

  if (!context) {
    throw new Error("useChart must be used within a <ChartContainer />");
  }

  return context;
}

/**
 * Optimized Chart Container with ResizeObserver performance improvements
 * 
 * Key optimizations:
 * - Uses OptimizedResizeObserver to prevent resize loops
 * - Manages dimensions explicitly to avoid ResponsiveContainer issues  
 * - Implements proper cleanup and error handling
 * - Debounces resize events to prevent excessive re-renders
 */
function OptimizedChartContainer({
  id,
  className,
  children,
  config,
  minWidth = 200,
  minHeight = 200,
  aspectRatio,
  ...props
}: React.ComponentProps<"div"> & {
  config: ChartConfig;
  children: React.ComponentProps<
    typeof RechartsPrimitive.ResponsiveContainer
  >["children"];
  minWidth?: number;
  minHeight?: number;
  aspectRatio?: number;
}) {
  const containerRef = React.useRef<HTMLDivElement>(null);
  const [dimensions, setDimensions] = React.useState({ 
    width: minWidth, 
    height: minHeight 
  });
  const [isReady, setIsReady] = React.useState(false);
  const uniqueId = React.useId();
  const chartId = `chart-${id || uniqueId.replace(/:/g, "")}`;

  // Optimized resize handling
  React.useEffect(() => {
    if (!containerRef.current) return;

    // Initial dimensions measurement
    const rect = containerRef.current.getBoundingClientRect();
    const initialWidth = Math.max(rect.width, minWidth);
    let initialHeight = Math.max(rect.height, minHeight);
    
    // Apply aspect ratio if specified
    if (aspectRatio && rect.width > 0) {
      initialHeight = Math.max(rect.width / aspectRatio, minHeight);
    }

    setDimensions({
      width: Math.floor(initialWidth),
      height: Math.floor(initialHeight)
    });
    setIsReady(true);

    // Set up optimized resize observer
    const cleanup = OptimizedResizeObserver.observeChart(
      containerRef.current,
      ({ width, height }) => {
        // Apply minimum dimensions
        const finalWidth = Math.max(width, minWidth);
        let finalHeight = Math.max(height, minHeight);
        
        // Apply aspect ratio if specified
        if (aspectRatio && width > 0) {
          finalHeight = Math.max(width / aspectRatio, minHeight);
        }

        // Only update if dimensions actually changed (prevents unnecessary re-renders)
        setDimensions(prev => {
          if (prev.width === finalWidth && prev.height === finalHeight) {
            return prev;
          }
          return {
            width: finalWidth,
            height: finalHeight
          };
        });
      }
    );

    return cleanup;
  }, [minWidth, minHeight, aspectRatio]);

  return (
    <ChartContext.Provider value={{ config }}>
      <div
        ref={containerRef}
        data-slot="chart"
        data-chart={chartId}
        className={cn(
          "[&_.recharts-cartesian-axis-tick_text]:fill-muted-foreground [&_.recharts-cartesian-grid_line[stroke='#ccc']]:stroke-border/50 [&_.recharts-curve.recharts-tooltip-cursor]:stroke-border [&_.recharts-polar-grid_[stroke='#ccc']]:stroke-border [&_.recharts-radial-bar-background-sector]:fill-muted [&_.recharts-rectangle.recharts-tooltip-cursor]:fill-muted [&_.recharts-reference-line_[stroke='#ccc']]:stroke-border flex justify-center text-xs [&_.recharts-dot[stroke='#fff']]:stroke-transparent [&_.recharts-layer]:outline-hidden [&_.recharts-sector]:outline-hidden [&_.recharts-sector[stroke='#fff']]:stroke-transparent [&_.recharts-surface]:outline-hidden",
          // Remove aspect-video to allow custom aspect ratios
          !aspectRatio && "aspect-video",
          className,
        )}
        style={{
          ...(aspectRatio && { aspectRatio: aspectRatio.toString() }),
          minWidth,
          minHeight
        }}
        {...props}
      >
        <ChartStyle id={chartId} config={config} />
        {isReady && dimensions.width > 0 && dimensions.height > 0 ? (
          <RechartsPrimitive.ResponsiveContainer
            width={dimensions.width}
            height={dimensions.height}
            // Disable ResponsiveContainer's own resize observer
            debounce={0}
          >
            {children}
          </RechartsPrimitive.ResponsiveContainer>
        ) : (
          // Loading skeleton
          <div 
            className="flex items-center justify-center bg-muted/10 rounded animate-pulse"
            style={{ width: dimensions.width, height: dimensions.height }}
          >
            <div className="text-muted-foreground text-sm">Loading chart...</div>
          </div>
        )}
      </div>
    </ChartContext.Provider>
  );
}

/**
 * Performance-optimized Chart Style component
 */
const ChartStyle = React.memo(({ id, config }: { id: string; config: ChartConfig }) => {
  const colorConfig = React.useMemo(() => 
    Object.entries(config).filter(
      ([, config]) => config.theme || config.color,
    ), 
    [config]
  );

  if (!colorConfig.length) {
    return null;
  }

  const styleContent = React.useMemo(() => 
    Object.entries(THEMES)
      .map(
        ([theme, prefix]) => `
${prefix} [data-chart=${id}] {
${colorConfig
  .map(([key, itemConfig]) => {
    const color =
      itemConfig.theme?.[theme as keyof typeof itemConfig.theme] ||
      itemConfig.color;
    return color ? `  --color-${key}: ${color};` : null;
  })
  .filter(Boolean)
  .join("\n")}
}`,
      )
      .join("\n"),
    [colorConfig, id]
  );

  return (
    <style
      dangerouslySetInnerHTML={{
        __html: styleContent
      }}
    />
  );
});

ChartStyle.displayName = "ChartStyle";

/**
 * Medical Dashboard Specific Chart Containers
 */

// For progress/metric charts in medical dashboard
function MedicalMetricChart({
  config,
  children,
  className,
  ...props
}: React.ComponentProps<typeof OptimizedChartContainer>) {
  return (
    <OptimizedChartContainer
      config={config}
      className={cn("h-24", className)}
      minWidth={150}
      minHeight={96}
      aspectRatio={2.5} // Wide aspect ratio for metrics
      {...props}
    >
      {children}
    </OptimizedChartContainer>
  );
}

// For main dashboard charts
function MedicalDashboardChart({
  config,
  children,
  className,
  ...props
}: React.ComponentProps<typeof OptimizedChartContainer>) {
  return (
    <OptimizedChartContainer
      config={config}
      className={cn("h-64", className)}
      minWidth={300}
      minHeight={256}
      aspectRatio={16/9}
      {...props}
    >
      {children}
    </OptimizedChartContainer>
  );
}

// For Jaspel component charts
function JaspelChart({
  config,
  children,
  className,
  ...props
}: React.ComponentProps<typeof OptimizedChartContainer>) {
  return (
    <OptimizedChartContainer
      config={config}
      className={cn("h-48", className)}
      minWidth={250}
      minHeight={192}
      aspectRatio={4/3}
      {...props}
    >
      {children}
    </OptimizedChartContainer>
  );
}

// Re-export existing components for compatibility
const ChartTooltip = RechartsPrimitive.Tooltip;

function ChartTooltipContent({
  active,
  payload,
  className,
  indicator = "dot",
  hideLabel = false,
  hideIndicator = false,
  label,
  labelFormatter,
  labelClassName,
  formatter,
  color,
  nameKey,
  labelKey,
}: React.ComponentProps<typeof RechartsPrimitive.Tooltip> &
  React.ComponentProps<"div"> & {
    hideLabel?: boolean;
    hideIndicator?: boolean;
    indicator?: "line" | "dot" | "dashed";
    nameKey?: string;
    labelKey?: string;
  }) {
  const { config } = useChart();

  const tooltipLabel = React.useMemo(() => {
    if (hideLabel || !payload?.length) {
      return null;
    }

    const [item] = payload;
    const key = `${labelKey || item?.dataKey || item?.name || "value"}`;
    const itemConfig = getPayloadConfigFromPayload(config, item, key);
    const value =
      !labelKey && typeof label === "string"
        ? config[label as keyof typeof config]?.label || label
        : itemConfig?.label;

    if (labelFormatter) {
      return (
        <div className={cn("font-medium", labelClassName)}>
          {labelFormatter(value, payload)}
        </div>
      );
    }

    if (!value) {
      return null;
    }

    return <div className={cn("font-medium", labelClassName)}>{value}</div>;
  }, [
    label,
    labelFormatter,
    payload,
    hideLabel,
    labelClassName,
    config,
    labelKey,
  ]);

  if (!active || !payload?.length) {
    return null;
  }

  const nestLabel = payload.length === 1 && indicator !== "dot";

  return (
    <div
      className={cn(
        "border-border/50 bg-background grid min-w-[8rem] items-start gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs shadow-xl",
        className,
      )}
    >
      {!nestLabel ? tooltipLabel : null}
      <div className="grid gap-1.5">
        {payload.map((item, index) => {
          const key = `${nameKey || item.name || item.dataKey || "value"}`;
          const itemConfig = getPayloadConfigFromPayload(config, item, key);
          const indicatorColor = color || item.payload.fill || item.color;

          return (
            <div
              key={item.dataKey}
              className={cn(
                "[&>svg]:text-muted-foreground flex w-full flex-wrap items-stretch gap-2 [&>svg]:h-2.5 [&>svg]:w-2.5",
                indicator === "dot" && "items-center",
              )}
            >
              {formatter && item?.value !== undefined && item.name ? (
                formatter(item.value, item.name, item, index, item.payload)
              ) : (
                <>
                  {itemConfig?.icon ? (
                    <itemConfig.icon />
                  ) : (
                    !hideIndicator && (
                      <div
                        className={cn(
                          "shrink-0 rounded-[2px] border-(--color-border) bg-(--color-bg)",
                          {
                            "h-2.5 w-2.5": indicator === "dot",
                            "w-1": indicator === "line",
                            "w-0 border-[1.5px] border-dashed bg-transparent":
                              indicator === "dashed",
                            "my-0.5": nestLabel && indicator === "dashed",
                          },
                        )}
                        style={
                          {
                            "--color-bg": indicatorColor,
                            "--color-border": indicatorColor,
                          } as React.CSSProperties
                        }
                      />
                    )
                  )}
                  <div
                    className={cn(
                      "flex flex-1 justify-between leading-none",
                      nestLabel ? "items-end" : "items-center",
                    )}
                  >
                    <div className="grid gap-1.5">
                      {nestLabel ? tooltipLabel : null}
                      <span className="text-muted-foreground">
                        {itemConfig?.label || item.name}
                      </span>
                    </div>
                    {item.value && (
                      <span className="text-foreground font-mono font-medium tabular-nums">
                        {item.value.toLocaleString()}
                      </span>
                    )}
                  </div>
                </>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}

const ChartLegend = RechartsPrimitive.Legend;

function ChartLegendContent({
  className,
  hideIcon = false,
  payload,
  verticalAlign = "bottom",
  nameKey,
}: React.ComponentProps<"div"> &
  Pick<RechartsPrimitive.LegendProps, "payload" | "verticalAlign"> & {
    hideIcon?: boolean;
    nameKey?: string;
  }) {
  const { config } = useChart();

  if (!payload?.length) {
    return null;
  }

  return (
    <div
      className={cn(
        "flex items-center justify-center gap-4",
        verticalAlign === "top" ? "pb-3" : "pt-3",
        className,
      )}
    >
      {payload.map((item) => {
        const key = `${nameKey || item.dataKey || "value"}`;
        const itemConfig = getPayloadConfigFromPayload(config, item, key);

        return (
          <div
            key={item.value}
            className={cn(
              "[&>svg]:text-muted-foreground flex items-center gap-1.5 [&>svg]:h-3 [&>svg]:w-3",
            )}
          >
            {itemConfig?.icon && !hideIcon ? (
              <itemConfig.icon />
            ) : (
              <div
                className="h-2 w-2 shrink-0 rounded-[2px]"
                style={{
                  backgroundColor: item.color,
                }}
              />
            )}
            {itemConfig?.label}
          </div>
        );
      })}
    </div>
  );
}

// Helper to extract item config from a payload.
function getPayloadConfigFromPayload(
  config: ChartConfig,
  payload: unknown,
  key: string,
) {
  if (typeof payload !== "object" || payload === null) {
    return undefined;
  }

  const payloadPayload =
    "payload" in payload &&
    typeof payload.payload === "object" &&
    payload.payload !== null
      ? payload.payload
      : undefined;

  let configLabelKey: string = key;

  if (
    key in payload &&
    typeof payload[key as keyof typeof payload] === "string"
  ) {
    configLabelKey = payload[key as keyof typeof payload] as string;
  } else if (
    payloadPayload &&
    key in payloadPayload &&
    typeof payloadPayload[key as keyof typeof payloadPayload] === "string"
  ) {
    configLabelKey = payloadPayload[
      key as keyof typeof payloadPayload
    ] as string;
  }

  return configLabelKey in config
    ? config[configLabelKey]
    : config[key as keyof typeof config];
}

// Backward compatibility alias
const ChartContainer = OptimizedChartContainer;

export {
  OptimizedChartContainer,
  OptimizedChartContainer as ChartContainer,
  MedicalMetricChart,
  MedicalDashboardChart,
  JaspelChart,
  ChartTooltip,
  ChartTooltipContent,
  ChartLegend,
  ChartLegendContent,
  ChartStyle,
  useChart,
  type ChartConfig
};