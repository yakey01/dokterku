{{-- Vite Asset Loader with Fallback --}}
@php
    $viteIsRunning = false;
    $vitePort = config('vite.server.port', 5173);
    $viteHost = config('vite.server.host', '127.0.0.1');
    
    // Check if Vite dev server is running
    if (app()->environment('local')) {
        $sock = @fsockopen($viteHost, $vitePort, $errno, $errstr, 0.1);
        if ($sock) {
            fclose($sock);
            $viteIsRunning = true;
        }
    }
@endphp

@if ($viteIsRunning || app()->environment('production'))
    {{-- Use normal Vite directive --}}
    @vite($assets ?? [])
@else
    {{-- Fallback to production build in development when Vite is not running --}}
    @production
        @vite($assets ?? [])
    @else
        @php
            $manifestPath = public_path('build/manifest.json');
            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
            }
        @endphp
        
        @if (isset($manifest))
            @foreach ($assets ?? [] as $asset)
                @if (isset($manifest[$asset]))
                    @php $assetInfo = $manifest[$asset]; @endphp
                    
                    {{-- Load CSS files --}}
                    @if (isset($assetInfo['css']))
                        @foreach ($assetInfo['css'] as $css)
                            <link rel="stylesheet" href="/build/{{ $css }}">
                        @endforeach
                    @endif
                    
                    {{-- Load JS file --}}
                    @if (isset($assetInfo['file']))
                        @if (Str::endsWith($assetInfo['file'], '.css'))
                            <link rel="stylesheet" href="/build/{{ $assetInfo['file'] }}">
                        @else
                            <script type="module" src="/build/{{ $assetInfo['file'] }}"></script>
                        @endif
                    @endif
                @endif
            @endforeach
        @else
            {{-- No manifest found, show helpful message --}}
            <script>
                console.error('Vite dev server is not running and no production build found.');
                console.info('Run "npm run dev" for development or "npm run build" for production.');
            </script>
        @endif
    @endproduction
@endif