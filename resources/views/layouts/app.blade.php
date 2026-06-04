<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>House Rental</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <style>
        .btn-hover-green {
            --bs-btn-hover-color: #FFCE1B;
            --bs-btn-hover-bg: #072117;
            --bs-btn-hover-border-color: #072117;
            --bs-btn-active-color: #FFCE1B;
            --bs-btn-active-bg: #072117;
            --bs-btn-active-border-color: #072117;
            --bs-btn-focus-shadow-rgb: 7, 33, 23;
        }
    </style>
    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
</head>

<body class="d-flex flex-column min-vh-100">

    @include('partials.navbar')

    <main class="container my-4 flex-fill">
        @php
            $flashTypes = [
                'success' => 'success',
                'error' => 'danger',
                'warning' => 'warning',
                'info' => 'info',
            ];
        @endphp

        @foreach($flashTypes as $sessionKey => $alertType)
            @if(session($sessionKey))
                <div class="alert alert-{{ $alertType }} alert-dismissible fade show auto-dismiss-alert" role="alert">
                    {{ session($sessionKey) }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
        @endforeach

        @yield('content')
    </main>

    @include('partials.footer')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

            fetch('/timezone', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ timezone: timezone })
            });

            setTimeout(function () {
                document.querySelectorAll('.auto-dismiss-alert').forEach(function (el) {
                    if (!el.classList.contains('show')) {
                        return;
                    }

                    const alert = bootstrap.Alert.getOrCreateInstance(el);
                    alert.close();
                });
            }, 3000);
        });
    </script>

</body>
</html>
