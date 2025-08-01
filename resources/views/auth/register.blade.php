<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/bootstrap/images/avi2.png') }}">

    <!-- Preloader CSS -->
    <link rel="stylesheet" href="{{ asset('assets/bootstrap/css/preloader.min.css') }}" type="text/css" />

    <!-- Bootstrap CSS -->
    <link href="{{ asset('assets/bootstrap/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet"
        type="text/css" />
    <!-- Icons CSS -->
    <link href="{{ asset('assets/bootstrap/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App CSS -->
    <link href="{{ asset('assets/bootstrap/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />

    <style>
        body {
            background: white;
            background-size: cover;
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5">
                    <div class="card shadow">
                        <div class="card-body p-4">
                            <div class="login-logo text-center mb-4">
                                <img src="{{ asset('assets/images/AVI.png') }}" alt="AVI Logo" class="img-fluid"
                                    style="max-width: 50%;">
                            </div>
                            <div class="p-2 mt-4">
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <form action="{{ route('register') }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Nama</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="npk" class="form-label">NPK</label>
                                        <input type="text" class="form-control" id="npk" name="npk" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="password" name="password"
                                            required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="password_confirmation" class="form-label">Konfirmasi
                                            Password</label>
                                        <input type="password" class="form-control" id="password_confirmation"
                                            name="password_confirmation" required>
                                    </div>

                                    <div class="d-grid">
                                        <button class="btn btn-primary" type="submit">Register</button>
                                    </div>

                                    <div class="mt-4 text-center">
                                        <a href="{{ route('login') }}" class="text-muted">
                                            <i class="bi bi-box-arrow-in-right me-1"></i> Sudah punya akun?
                                        </a>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>