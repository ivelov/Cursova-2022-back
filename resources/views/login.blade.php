<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;600&display=swap" rel="stylesheet">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Nunito', sans-serif;
                font-weight: 200;
                height: 100vh;
                margin: 0;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

        </style>
    </head>
    <body>
        <div class="flex-center position-ref full-height">
            
            <form action="{{ route('login') }}" method="post">
            @csrf
            <input type="text" placeholder="Email" name="email">
            <input type="password" placeholder="Password" name="password">
            <input type="submit" value="Enter">
            </form>
        </div>
        @error('email')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror
    </body>
</html>
