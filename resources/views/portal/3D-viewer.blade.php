<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Hub Viewer | {{ $submission->project_name }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon/sabah-logo.png') }}" />

    @viteReactRefresh
    @vite(['resources/js/viewer/main.tsx', 'resources/css/app.css'])
    <style>
        body { margin: 0; padding: 0; overflow: hidden; background-color: #000; }
        #root { width: 100vw; height: 100vh; }
    </style>
</head>
<body>
    <div id="root"></div>
    
    <script>
        // Provide the Laravel route context to the React application
        window.TemaDataPortal_API_BASE = window.location.origin;
        
        // If the React app needs the ID from a global variable as fallback
        window.__viewerId = "{{ $submission->id }}";

        @if($submission->processed_data_path)
        // Ensure tileset_url is in query params for the React app to consume
        if (!window.location.search.includes('tileset_url=')) {
            const url = new URL(window.location.href);
            url.searchParams.set('tileset_url', "{{ $submission->processed_data_path }}");
            url.searchParams.set('title', "{{ $submission->project_name }}");
            window.history.replaceState({}, '', url);
        }
        @endif
    </script>
</body>
</html>