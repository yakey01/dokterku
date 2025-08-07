var $=Object.defineProperty;var S=(p,e,s)=>e in p?$(p,e,{enumerable:!0,configurable:!0,writable:!0,value:s}):p[e]=s;var l=(p,e,s)=>S(p,typeof e!="symbol"?e+"":e,s);const f=class f{constructor(e,s={}){l(this,"observer");l(this,"callback");l(this,"isDestroyed",!1);l(this,"options");l(this,"metrics");l(this,"rafId",null);l(this,"pendingEntries",[]);l(this,"lastCallTime",0);l(this,"callbackTimes",[]);l(this,"loopDetectionCount",0);l(this,"instanceId");this.instanceId=`ro-${Date.now()}-${Math.random().toString(36).substr(2,9)}`,this.options={debounceMs:s.debounceMs??16,maxFPS:s.maxFPS??60,enableMetrics:s.enableMetrics??!0,enableLoopDetection:s.enableLoopDetection??!0,performanceThreshold:s.performanceThreshold??16.67,enableConsoleSupression:s.enableConsoleSupression??!0},this.metrics={totalObservations:0,loopErrors:0,averageCallbackTime:0,maxCallbackTime:0,memoryUsage:0,activeObservers:1,performanceScore:100},this.callback=this.createOptimizedCallback(e),this.observer=new window.ResizeObserver(this.callback),this.options.enableMetrics&&f.globalMetrics.set(this.instanceId,this.metrics),this.setupErrorSuppression(),this.startPerformanceMonitoring()}createOptimizedCallback(e){return(s,r)=>{if(this.isDestroyed)return;const t=performance.now();try{if(this.options.enableLoopDetection)if(t-this.lastCallTime<1){if(this.loopDetectionCount++,this.loopDetectionCount>10){this.metrics.loopErrors++,console.warn(`🔄 ResizeObserver loop detected (${this.loopDetectionCount} rapid calls) - applying throttling`);return}}else this.loopDetectionCount=0;this.pendingEntries=s,this.rafId!==null&&cancelAnimationFrame(this.rafId),this.rafId=requestAnimationFrame(()=>{this.executeSafeCallback(e,this.pendingEntries,r,t)})}catch(a){console.error("OptimizedResizeObserver callback error:",a)}this.lastCallTime=t}}executeSafeCallback(e,s,r,t){if(!this.isDestroyed)try{e(s,r),this.options.enableMetrics&&this.updateMetrics(t)}catch(a){a instanceof Error&&a.message.includes("ResizeObserver loop")?(this.metrics.loopErrors++,console.debug("🔄 ResizeObserver loop handled gracefully")):console.error("ResizeObserver callback execution error:",a)}}updateMetrics(e){const s=performance.now()-e;this.metrics.totalObservations++,this.metrics.maxCallbackTime=Math.max(this.metrics.maxCallbackTime,s),this.callbackTimes.push(s),this.callbackTimes.length>100&&(this.callbackTimes=this.callbackTimes.slice(-50)),this.metrics.averageCallbackTime=this.callbackTimes.reduce((a,o)=>a+o,0)/this.callbackTimes.length;const r=Math.min(this.options.performanceThreshold/this.metrics.averageCallbackTime,1),t=Math.max(0,1-this.metrics.loopErrors/this.metrics.totalObservations);this.metrics.performanceScore=Math.round(r*t*100),"memory"in performance&&performance.memory&&(this.metrics.memoryUsage=performance.memory.usedJSHeapSize)}setupErrorSuppression(){if(!this.options.enableConsoleSupression)return;const e=console.error;e._optimizedResizeObserverPatched||(console.error=function(...s){var t,a;const r=((a=(t=s[0])==null?void 0:t.toString)==null?void 0:a.call(t))||"";if(r.includes("ResizeObserver loop")||r.includes("ResizeObserver loop limit exceeded")||r.includes("ResizeObserver loop completed with undelivered notifications")){const o=globalThis._resizeObserverErrorCount||0;o<3&&(console.debug(`🔄 ResizeObserver loop ${o+1}/3 (suppressing future warnings for performance)`),globalThis._resizeObserverErrorCount=o+1);return}e.apply(console,s)},console.error._optimizedResizeObserverPatched=!0)}startPerformanceMonitoring(){if(!this.options.enableMetrics)return;const e=()=>{this.isDestroyed||(f.globalMetrics.set(this.instanceId,{...this.metrics}),setTimeout(e,5e3))};setTimeout(e,5e3)}observe(e,s){this.isDestroyed||this.observer.observe(e,s)}unobserve(e){this.isDestroyed||this.observer.unobserve(e)}disconnect(){this.isDestroyed=!0,this.rafId!==null&&(cancelAnimationFrame(this.rafId),this.rafId=null),this.observer.disconnect(),f.globalMetrics.delete(this.instanceId)}getMetrics(){return{...this.metrics}}static getGlobalMetrics(){const e=Array.from(this.globalMetrics.values());return e.length===0?{totalObservations:0,loopErrors:0,averageCallbackTime:0,maxCallbackTime:0,memoryUsage:0,activeObservers:0,performanceScore:100}:{totalObservations:e.reduce((s,r)=>s+r.totalObservations,0),loopErrors:e.reduce((s,r)=>s+r.loopErrors,0),averageCallbackTime:e.reduce((s,r)=>s+r.averageCallbackTime,0)/e.length,maxCallbackTime:Math.max(...e.map(s=>s.maxCallbackTime)),memoryUsage:Math.max(...e.map(s=>s.memoryUsage)),activeObservers:e.length,performanceScore:Math.round(e.reduce((s,r)=>s+r.performanceScore,0)/e.length)}}static observeChart(e,s,r){const t=new f(a=>{for(const o of a){const{width:n,height:i}=o.contentRect;s({width:Math.floor(n),height:Math.floor(i)})}},r);return t.observe(e),()=>{t.disconnect()}}static createPerformanceDashboard(){const e=document.createElement("div");e.className="resize-observer-dashboard",e.innerHTML=`
            <div class="performance-dashboard">
                <h3>🚀 ResizeObserver Performance</h3>
                <div class="metrics-grid">
                    <div class="metric">
                        <span class="label">Active Observers:</span>
                        <span class="value" id="ro-active">0</span>
                    </div>
                    <div class="metric">
                        <span class="label">Performance Score:</span>
                        <span class="value" id="ro-score">100</span>
                    </div>
                    <div class="metric">
                        <span class="label">Loop Errors:</span>
                        <span class="value" id="ro-loops">0</span>
                    </div>
                    <div class="metric">
                        <span class="label">Avg Callback Time:</span>
                        <span class="value" id="ro-time">0ms</span>
                    </div>
                </div>
            </div>
        `;const s=()=>{const r=f.getGlobalMetrics(),t=e.querySelector("#ro-active"),a=e.querySelector("#ro-score"),o=e.querySelector("#ro-loops"),n=e.querySelector("#ro-time");t&&(t.textContent=r.activeObservers.toString()),a&&(a.textContent=r.performanceScore.toString(),a.className=`value ${r.performanceScore>80?"good":r.performanceScore>60?"warning":"error"}`),o&&(o.textContent=r.loopErrors.toString()),n&&(n.textContent=`${r.averageCallbackTime.toFixed(2)}ms`)};return s(),setInterval(s,1e3),e}};l(f,"globalMetrics",new Map);let b=f;function C(p,e){return new b(p,e)}function x(){const p=console.error;p._resizeObserverPatched||(console.error=function(...e){var r,t;if((((t=(r=e[0])==null?void 0:r.toString)==null?void 0:t.call(r))||"").includes("ResizeObserver loop")){console.debug("🔄 ResizeObserver loop suppressed");return}p.apply(console,e)},console.error._resizeObserverPatched=!0)}function M(){return b.getGlobalMetrics()}function k(p){window._resizeObserverOptimized||(window.ResizeObserver=class extends b{constructor(e){super(e,p)}},window._resizeObserverOptimized=!0,console.log("✅ Global ResizeObserver optimization enabled"))}typeof window<"u"&&(x(),k());class w{static createCustomMarker(e={}){const{type:s="default",theme:r="medical",size:t="medium",animated:a=!0,pulsing:o=!1,glowing:n=!1,shadowIntensity:i="medium",customIcon:c,className:d=""}=e,h=this.themes.get(r)||this.themes.get("medical"),m=this.sizeMap.get(t)||this.sizeMap.get("medium"),u=c||this.iconMap.get(s)||this.iconMap.get("default"),y=this.generateSVGIcon({theme:h,dimensions:m,icon:u,animated:a,pulsing:o,glowing:n,shadowIntensity:i});return L.divIcon({html:y,className:`custom-marker-container ${d} ${a?"animated":""} ${o?"pulsing":""} ${n?"glowing":""}`,iconSize:[m.width,m.height],iconAnchor:[m.width/2,m.height],popupAnchor:[0,-m.height]})}static generateSVGIcon(e){const{theme:s,dimensions:r,icon:t,animated:a,pulsing:o,glowing:n,shadowIntensity:i}=e,{width:c,height:d}=r,h={none:0,light:.1,medium:.2,strong:.4}[i]||.2,m=`marker-${Math.random().toString(36).substr(2,9)}`,u=`glow-${m}`;return`
            <div class="marker-wrapper" style="position: relative; width: ${c}px; height: ${d}px;">
                <!-- Pulsing Animation Ring -->
                ${o?`
                <div class="marker-pulse-ring" style="
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    width: ${c*1.8}px;
                    height: ${c*1.8}px;
                    border: 2px solid ${s.primary};
                    border-radius: 50%;
                    transform: translate(-50%, -50%);
                    opacity: 0.6;
                    animation: markerPulse 2s infinite ease-out;
                    z-index: 1;
                "></div>
                `:""}

                <!-- Glowing Effect -->
                ${n?`
                <div class="marker-glow" style="
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    width: ${c*1.4}px;
                    height: ${c*1.4}px;
                    background: radial-gradient(circle, ${s.glow} 0%, transparent 70%);
                    border-radius: 50%;
                    transform: translate(-50%, -50%);
                    animation: markerGlow 3s infinite alternate ease-in-out;
                    z-index: 2;
                "></div>
                `:""}

                <!-- Main SVG Marker -->
                <svg width="${c}" height="${d}" viewBox="0 0 32 32" class="marker-svg" style="
                    position: relative;
                    z-index: 3;
                    filter: drop-shadow(0 ${d*.1}px ${d*.2}px rgba(0, 0, 0, ${h}));
                    ${a?"transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);":""}
                ">
                    <!-- Gradient Definitions -->
                    <defs>
                        <linearGradient id="${m}-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:${s.primary};stop-opacity:1" />
                            <stop offset="100%" style="stop-color:${s.accent};stop-opacity:1" />
                        </linearGradient>
                        <filter id="${u}" x="-50%" y="-50%" width="200%" height="200%">
                            <feGaussianBlur stdDeviation="2" result="coloredBlur"/>
                            <feMerge> 
                                <feMergeNode in="coloredBlur"/>
                                <feMergeNode in="SourceGraphic"/>
                            </feMerge>
                        </filter>
                        <radialGradient id="${m}-radial" cx="50%" cy="30%" r="70%">
                            <stop offset="0%" style="stop-color:${s.secondary};stop-opacity:0.9" />
                            <stop offset="70%" style="stop-color:${s.primary};stop-opacity:1" />
                            <stop offset="100%" style="stop-color:${s.accent};stop-opacity:1" />
                        </radialGradient>
                    </defs>

                    <!-- Marker Shape with Gradient -->
                    <path d="M16 2 C10.5 2 6 6.5 6 12 C6 20 16 30 16 30 C16 30 26 20 26 12 C26 6.5 21.5 2 16 2 Z" 
                          fill="url(#${m}-radial)" 
                          stroke="${s.secondary}" 
                          stroke-width="1"
                          ${n?`filter="url(#${u})"`:""}
                    />

                    <!-- Inner Circle -->
                    <circle cx="16" cy="12" r="6" 
                            fill="${s.secondary}" 
                            stroke="${s.primary}" 
                            stroke-width="1.5"
                            opacity="0.95" />

                    <!-- Icon Container -->
                    <foreignObject x="10" y="6" width="12" height="12">
                        <div style="
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            width: 100%;
                            height: 100%;
                            font-size: ${c*.25}px;
                            text-align: center;
                            line-height: 1;
                        ">${t}</div>
                    </foreignObject>

                    <!-- Highlight Effect -->
                    <ellipse cx="20" cy="8" rx="3" ry="2" 
                             fill="${s.secondary}" 
                             opacity="0.4" />
                </svg>

                <!-- Animated Bounce Effect -->
                ${a?`
                <style>
                    .marker-wrapper:hover .marker-svg {
                        transform: scale(1.1) translateY(-2px);
                        filter: drop-shadow(0 ${d*.15}px ${d*.3}px rgba(0, 0, 0, ${h*1.5}));
                    }
                </style>
                `:""}
            </div>

            <style>
                @keyframes markerPulse {
                    0% { transform: translate(-50%, -50%) scale(0.8); opacity: 0.8; }
                    50% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.3; }
                    100% { transform: translate(-50%, -50%) scale(1.5); opacity: 0; }
                }

                @keyframes markerGlow {
                    0% { opacity: 0.5; transform: translate(-50%, -50%) scale(1); }
                    100% { opacity: 0.8; transform: translate(-50%, -50%) scale(1.1); }
                }

                @keyframes markerBounce {
                    0%, 20%, 53%, 80%, 100% { transform: translate3d(0, 0, 0); }
                    40%, 43% { transform: translate3d(0, -8px, 0); }
                    70% { transform: translate3d(0, -4px, 0); }
                    90% { transform: translate3d(0, -2px, 0); }
                }

                .marker-wrapper.animated .marker-svg {
                    animation: markerBounce 2s infinite;
                }
            </style>
        `}static createGlassmorphicPopup(e={}){const{title:s="Location",description:r="",imageUrl:t="",actions:a=[],theme:o="glass",maxWidth:n=300}=e,i=`popup-${Math.random().toString(36).substr(2,9)}`,c={glass:{background:"rgba(255, 255, 255, 0.25)",backdropFilter:"blur(10px)",border:"1px solid rgba(255, 255, 255, 0.18)",boxShadow:"0 8px 32px 0 rgba(31, 38, 135, 0.37)"},modern:{background:"linear-gradient(145deg, #ffffff 0%, #f0f4f8 100%)",backdropFilter:"none",border:"1px solid rgba(0, 0, 0, 0.1)",boxShadow:"0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)"},minimal:{background:"#ffffff",backdropFilter:"none",border:"1px solid #e2e8f0",boxShadow:"0 4px 6px -1px rgba(0, 0, 0, 0.1)"},luxury:{background:"linear-gradient(145deg, #1a202c 0%, #2d3748 100%)",backdropFilter:"blur(15px)",border:"1px solid rgba(255, 255, 255, 0.1)",boxShadow:"0 20px 25px -5px rgba(0, 0, 0, 0.4)"}},d=c[o]||c.glass,h=o==="luxury"?"#ffffff":"#2d3748",m=o==="luxury"?"rgba(255, 255, 255, 0.8)":"#718096";return`
            <div id="${i}" class="glassmorphic-popup" style="
                max-width: ${n}px;
                background: ${d.background};
                backdrop-filter: ${d.backdropFilter};
                border: ${d.border};
                border-radius: 16px;
                box-shadow: ${d.boxShadow};
                padding: 0;
                margin: 0;
                overflow: hidden;
                position: relative;
            ">
                <!-- Image Header -->
                ${t?`
                <div class="popup-image" style="
                    width: 100%;
                    height: 120px;
                    background-image: url('${t}');
                    background-size: cover;
                    background-position: center;
                    position: relative;
                ">
                    <div style="
                        position: absolute;
                        bottom: 0;
                        left: 0;
                        right: 0;
                        height: 50%;
                        background: linear-gradient(transparent, rgba(0, 0, 0, 0.6));
                    "></div>
                </div>
                `:""}

                <!-- Content -->
                <div class="popup-content" style="padding: 20px;">
                    <!-- Title -->
                    <h3 style="
                        margin: 0 0 8px 0;
                        font-size: 18px;
                        font-weight: 600;
                        color: ${h};
                        line-height: 1.3;
                    ">${s}</h3>

                    <!-- Description -->
                    ${r?`
                    <p style="
                        margin: 0 0 16px 0;
                        font-size: 14px;
                        color: ${m};
                        line-height: 1.5;
                    ">${r}</p>
                    `:""}

                    <!-- Actions -->
                    ${a.length>0?`
                    <div class="popup-actions" style="
                        display: flex;
                        gap: 8px;
                        flex-wrap: wrap;
                        margin-top: 16px;
                    ">
                        ${a.map((u,y)=>`
                        <button 
                            onclick="window.popupActions['${i}-${y}']()"
                            style="
                                padding: 8px 16px;
                                border: none;
                                border-radius: 8px;
                                background: rgba(59, 130, 246, 0.8);
                                color: white;
                                font-size: 12px;
                                font-weight: 500;
                                cursor: pointer;
                                transition: all 0.2s ease;
                                backdrop-filter: blur(10px);
                                ${u.style||""}
                            "
                            onmouseover="this.style.transform='scale(1.05)'; this.style.background='rgba(59, 130, 246, 0.9)'"
                            onmouseout="this.style.transform='scale(1)'; this.style.background='rgba(59, 130, 246, 0.8)'"
                        >
                            ${u.label}
                        </button>
                        `).join("")}
                    </div>
                    `:""}
                </div>

                <!-- Decorative Elements -->
                <div style="
                    position: absolute;
                    top: 12px;
                    right: 12px;
                    width: 40px;
                    height: 40px;
                    background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
                    border-radius: 50%;
                    opacity: 0.6;
                "></div>

                <div style="
                    position: absolute;
                    bottom: 12px;
                    left: 12px;
                    width: 20px;
                    height: 20px;
                    background: radial-gradient(circle, rgba(59, 130, 246, 0.3) 0%, transparent 70%);
                    border-radius: 50%;
                    opacity: 0.8;
                "></div>
            </div>

            <style>
                .glassmorphic-popup {
                    animation: popupFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                }

                @keyframes popupFadeIn {
                    from {
                        opacity: 0;
                        transform: scale(0.9) translateY(10px);
                    }
                    to {
                        opacity: 1;
                        transform: scale(1) translateY(0);
                    }
                }

                .glassmorphic-popup:hover {
                    box-shadow: ${d.boxShadow.replace("0.1","0.15").replace("0.04","0.08")};
                    transform: translateY(-2px);
                    transition: all 0.3s ease;
                }
            </style>

            <script>
                // Store popup actions globally
                if (!window.popupActions) {
                    window.popupActions = {};
                }
                ${a.map((u,y)=>`
                window.popupActions['${i}-${y}'] = ${u.action.toString()};
                `).join("")}
            <\/script>
        `}static createMarkerCluster(e){const s=L.markerClusterGroup({iconCreateFunction:r=>{const t=r.getChildCount();let a="marker-cluster-small";return t<10?a="marker-cluster-small":t<100?a="marker-cluster-medium":a="marker-cluster-large",L.divIcon({html:`
                        <div class="cluster-inner">
                            <div class="cluster-count">${t}</div>
                            <div class="cluster-pulse"></div>
                        </div>
                    `,className:`marker-cluster ${a}`,iconSize:[40,40]})}});return e.forEach(r=>{const t=L.marker([r.lat,r.lng],{icon:this.createCustomMarker(r.options)});r.popup&&t.bindPopup(this.createGlassmorphicPopup(r.popup)),s.addLayer(t)}),s}static injectStyles(){if(document.getElementById("custom-marker-styles"))return;document.head.insertAdjacentHTML("beforeend",`
            <style id="custom-marker-styles">
                .custom-marker-container {
                    background: transparent !important;
                    border: none !important;
                    cursor: pointer;
                }

                .marker-cluster {
                    background-color: rgba(59, 130, 246, 0.8) !important;
                    border: 3px solid rgba(255, 255, 255, 0.9) !important;
                    border-radius: 50% !important;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
                    backdrop-filter: blur(10px) !important;
                }

                .marker-cluster-small {
                    width: 30px !important;
                    height: 30px !important;
                }

                .marker-cluster-medium {
                    width: 40px !important;
                    height: 40px !important;
                }

                .marker-cluster-large {
                    width: 50px !important;
                    height: 50px !important;
                }

                .cluster-inner {
                    position: relative;
                    width: 100%;
                    height: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .cluster-count {
                    color: white;
                    font-weight: bold;
                    font-size: 12px;
                    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
                    position: relative;
                    z-index: 2;
                }

                .cluster-pulse {
                    position: absolute;
                    top: -5px;
                    left: -5px;
                    right: -5px;
                    bottom: -5px;
                    border: 2px solid rgba(59, 130, 246, 0.5);
                    border-radius: 50%;
                    animation: clusterPulse 2s infinite;
                }

                @keyframes clusterPulse {
                    0% { transform: scale(0.8); opacity: 1; }
                    100% { transform: scale(1.3); opacity: 0; }
                }

                .leaflet-popup-content-wrapper {
                    padding: 0 !important;
                    background: transparent !important;
                    border-radius: 16px !important;
                    box-shadow: none !important;
                }

                .leaflet-popup-content {
                    margin: 0 !important;
                }

                .leaflet-popup-tip {
                    background: rgba(255, 255, 255, 0.25) !important;
                    backdrop-filter: blur(10px) !important;
                    border: 1px solid rgba(255, 255, 255, 0.18) !important;
                }
            </style>
        `)}}l(w,"themes",new Map([["medical",{primary:"#e53e3e",secondary:"#ffffff",accent:"#3182ce",shadow:"rgba(229, 62, 62, 0.3)",glow:"rgba(229, 62, 62, 0.5)"}],["corporate",{primary:"#3182ce",secondary:"#ffffff",accent:"#38a169",shadow:"rgba(49, 130, 206, 0.3)",glow:"rgba(49, 130, 206, 0.5)"}],["emergency",{primary:"#e53e3e",secondary:"#ffffff",accent:"#f56565",shadow:"rgba(229, 62, 62, 0.4)",glow:"rgba(245, 101, 101, 0.6)"}],["eco",{primary:"#38a169",secondary:"#ffffff",accent:"#68d391",shadow:"rgba(56, 161, 105, 0.3)",glow:"rgba(56, 161, 105, 0.5)"}],["luxury",{primary:"#805ad5",secondary:"#ffffff",accent:"#d69e2e",shadow:"rgba(128, 90, 213, 0.3)",glow:"rgba(128, 90, 213, 0.5)"}],["dark",{primary:"#2d3748",secondary:"#ffffff",accent:"#4299e1",shadow:"rgba(45, 55, 72, 0.4)",glow:"rgba(66, 153, 225, 0.5)"}]])),l(w,"iconMap",new Map([["hospital","🏥"],["clinic","🏨"],["office","🏢"],["pharmacy","💊"],["lab","🔬"],["emergency","🚑"],["default","📍"]])),l(w,"sizeMap",new Map([["small",{width:24,height:24}],["medium",{width:32,height:32}],["large",{width:40,height:40}],["xl",{width:48,height:48}]]));typeof window<"u"&&w.injectStyles();const g=class g{constructor(){l(this,"cache",new Map);l(this,"loadingPromises",new Map);l(this,"metrics");l(this,"retryDelays",[100,500,1500,3e3]);this.metrics={totalRequests:0,successfulLoads:0,failedLoads:0,fallbackUsage:0,averageLoadTime:0,cacheHitRate:0,generatedAssets:0}}static getInstance(){return g.instance||(g.instance=new g),g.instance}async loadAsset(e){const s=performance.now();this.metrics.totalRequests++;const r=this.getCachedAsset(e.url);if(r)return this.metrics.cacheHitRate=++this.metrics.successfulLoads/this.metrics.totalRequests,r;if(this.loadingPromises.has(e.url))return await this.loadingPromises.get(e.url);const t=this.performAssetLoad(e,s);this.loadingPromises.set(e.url,t);try{const a=await t;return this.loadingPromises.delete(e.url),a}catch(a){throw this.loadingPromises.delete(e.url),a}}async performAssetLoad(e,s){const r=[e.url,...e.fallbacks||[]];let t=null;for(let a=0;a<r.length;a++){const o=r[a],n=a===0;try{const i=await this.loadSingleAsset(o,e);if(i){const c=performance.now()-s;return this.updateMetrics(!0,c,!n),e.cache!==!1&&this.cacheAsset(e.url,i),i}}catch(i){t=i,console.warn(`Asset load failed for ${o}:`,i)}}try{const a=await this.generateFallbackAsset(e);if(a)return this.metrics.generatedAssets++,this.updateMetrics(!0,performance.now()-s,!0),a}catch(a){console.warn("Fallback asset generation failed:",a)}throw this.updateMetrics(!1,performance.now()-s,!1),t||new Error(`Failed to load asset: ${e.url}`)}async loadSingleAsset(e,s){return new Promise((r,t)=>{const a=s.timeout||1e4;let o;const n=()=>{o&&clearTimeout(o)};switch(o=setTimeout(()=>{n(),t(new Error(`Asset load timeout: ${e}`))},a),s.type){case"image":this.loadImage(e).then(i=>{n(),r(i)}).catch(i=>{n(),t(i)});break;case"css":this.loadCSS(e).then(i=>{n(),r(i)}).catch(i=>{n(),t(i)});break;case"js":this.loadScript(e).then(i=>{n(),r(i)}).catch(i=>{n(),t(i)});break;case"svg":this.loadSVG(e).then(i=>{n(),r(i)}).catch(i=>{n(),t(i)});break;case"font":this.loadFont(e).then(i=>{n(),r(i)}).catch(i=>{n(),t(i)});break;default:n(),t(new Error(`Unsupported asset type: ${s.type}`))}})}loadImage(e){return new Promise((s,r)=>{const t=new Image;t.onload=()=>s(e),t.onerror=()=>r(new Error(`Failed to load image: ${e}`)),t.src=e})}loadCSS(e){return new Promise((s,r)=>{const t=document.createElement("link");t.rel="stylesheet",t.href=e,t.onload=()=>s(t),t.onerror=()=>r(new Error(`Failed to load CSS: ${e}`)),document.head.appendChild(t)})}loadScript(e){return new Promise((s,r)=>{const t=document.createElement("script");t.src=e,t.onload=()=>s(t),t.onerror=()=>r(new Error(`Failed to load script: ${e}`)),document.head.appendChild(t)})}async loadSVG(e){const s=await fetch(e);if(!s.ok)throw new Error(`Failed to load SVG: ${e}`);return await s.text()}loadFont(e){return new Promise((s,r)=>{const t=new FontFace("CustomFont",`url(${e})`);t.load().then(()=>{document.fonts.add(t),s(e)}).catch(a=>r(a))})}async generateFallbackAsset(e){const s=e.url.toLowerCase();return s.includes("marker-icon")||s.includes("marker-shadow")?await this.generateMarkerAsset(e):s.includes("tile")||s.includes("png")?await this.generateTileAsset(e):null}async generateMarkerAsset(e){e.url.includes("marker-icon");const s=e.url.includes("2x"),r=e.url.includes("shadow"),t=s?50:25,a={size:{width:t,height:r?t*.6:t*1.6}};if(r){const o=`
                <svg width="${a.size.width}" height="${a.size.height}" xmlns="http://www.w3.org/2000/svg">
                    <ellipse cx="${a.size.width/2}" cy="${a.size.height/2}" 
                             rx="${a.size.width*.4}" ry="${a.size.height*.3}" 
                             fill="rgba(0, 0, 0, 0.2)" 
                             filter="blur(2px)" />
                </svg>
            `;return`data:image/svg+xml;base64,${btoa(o)}`}else{const o=`
                <svg width="${a.size.width}" height="${a.size.height}" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="markerGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3388ff;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#1166cc;stop-opacity:1" />
                        </linearGradient>
                        <filter id="shadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="rgba(0,0,0,0.3)"/>
                        </filter>
                    </defs>
                    <path d="M${t/2} 5 C${t*.75} 5 ${t*.9} ${t*.3} ${t*.9} ${t*.5} C${t*.9} ${t*.8} ${t/2} ${t*1.4} ${t/2} ${t*1.4} C${t/2} ${t*1.4} ${t*.1} ${t*.8} ${t*.1} ${t*.5} C${t*.1} ${t*.3} ${t*.25} 5 ${t/2} 5 Z" 
                          fill="url(#markerGrad)" 
                          stroke="white" 
                          stroke-width="2" 
                          filter="url(#shadow)" />
                    <circle cx="${t/2}" cy="${t*.5}" r="${t*.15}" fill="white" />
                </svg>
            `;return`data:image/svg+xml;base64,${btoa(o)}`}}async generateTileAsset(e){const s=document.createElement("canvas"),r=s.getContext("2d");s.width=256,s.height=256;const t=r.createLinearGradient(0,0,256,256);t.addColorStop(0,"#f0f4f8"),t.addColorStop(1,"#e2e8f0"),r.fillStyle=t,r.fillRect(0,0,256,256),r.strokeStyle="#cbd5e0",r.lineWidth=1;for(let a=0;a<256;a+=32)r.beginPath(),r.moveTo(a,0),r.lineTo(a,256),r.stroke(),r.beginPath(),r.moveTo(0,a),r.lineTo(256,a),r.stroke();return r.fillStyle="#a0aec0",r.font="14px Arial",r.textAlign="center",r.fillText("Map Tile",128,128),s.toDataURL("image/png")}getCachedAsset(e){const s=this.cache.get(e);if(!s)return null;const r=Date.now(),t=3600*1e3;return r-s.timestamp>t?(this.cache.delete(e),null):s.data}cacheAsset(e,s){if(this.cache.set(e,{data:s,timestamp:Date.now()}),this.cache.size>100){const r=this.cache.keys().next().value;this.cache.delete(r)}}updateMetrics(e,s,r){e?this.metrics.successfulLoads++:this.metrics.failedLoads++,r&&this.metrics.fallbackUsage++;const t=this.metrics.successfulLoads+this.metrics.failedLoads;this.metrics.averageLoadTime=(this.metrics.averageLoadTime*(t-1)+s)/t,this.metrics.cacheHitRate=this.metrics.successfulLoads/this.metrics.totalRequests}preloadAssets(e){return Promise.allSettled(e.map(s=>this.loadAsset(s))).then(s=>s.map(r=>r.status==="fulfilled"?r.value:null))}clearCache(){this.cache.clear(),console.log("Asset cache cleared")}getMetrics(){return{...this.metrics}}createPerformanceDashboard(){const e=document.createElement("div");e.className="asset-performance-dashboard",e.innerHTML=`
            <div class="performance-panel">
                <h3>📦 Asset Performance</h3>
                <div class="metrics-grid">
                    <div class="metric">
                        <span class="label">Success Rate:</span>
                        <span class="value" id="asset-success-rate">0%</span>
                    </div>
                    <div class="metric">
                        <span class="label">Cache Hit Rate:</span>
                        <span class="value" id="asset-cache-rate">0%</span>
                    </div>
                    <div class="metric">
                        <span class="label">Fallback Usage:</span>
                        <span class="value" id="asset-fallback-rate">0%</span>
                    </div>
                    <div class="metric">
                        <span class="label">Avg Load Time:</span>
                        <span class="value" id="asset-load-time">0ms</span>
                    </div>
                    <div class="metric">
                        <span class="label">Generated Assets:</span>
                        <span class="value" id="asset-generated">${this.metrics.generatedAssets}</span>
                    </div>
                </div>
            </div>
        `;const s=()=>{const r=this.getMetrics(),t=r.totalRequests>0?Math.round(r.successfulLoads/r.totalRequests*100):0,a=Math.round(r.cacheHitRate*100),o=r.totalRequests>0?Math.round(r.fallbackUsage/r.totalRequests*100):0,n=e.querySelector("#asset-success-rate"),i=e.querySelector("#asset-cache-rate"),c=e.querySelector("#asset-fallback-rate"),d=e.querySelector("#asset-load-time"),h=e.querySelector("#asset-generated");n&&(n.textContent=`${t}%`,n.className=`value ${t>90?"good":t>70?"warning":"error"}`),i&&(i.textContent=`${a}%`),c&&(c.textContent=`${o}%`),d&&(d.textContent=`${Math.round(r.averageLoadTime)}ms`),h&&(h.textContent=r.generatedAssets.toString())};return s(),setInterval(s,2e3),e}async setupLeafletAssets(){const e=[{url:"https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png",fallbacks:["https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png"],type:"image",priority:"high",cache:!0},{url:"https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png",fallbacks:["https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png"],type:"image",priority:"medium",cache:!0},{url:"https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png",fallbacks:["https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png"],type:"image",priority:"medium",cache:!0}];try{await this.preloadAssets(e),console.log("✅ Leaflet assets loaded successfully")}catch{console.warn("⚠️ Some Leaflet assets failed to load, fallbacks generated")}}};l(g,"instance");let v=g;const z=v.getInstance(),T={OptimizedResizeObserver:b,createOptimizedResizeObserver:C,suppressResizeObserverErrors:x,getResizeObserverMetrics:M,enableGlobalOptimization:k,CustomMarkerSystem:w,AssetManager:z};typeof window<"u"&&(window.LeafletUtilities=T,k(),x(),console.log("✅ Leaflet utilities loaded and optimizations enabled"));
