<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login</title>
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/AVI.png') }}">

    <!-- Preloader CSS -->
    <link rel="stylesheet" href="{{ asset('assets/bootstrap/css/preloader.min.css') }}" type="text/css" />

    <!-- Bootstrap CSS -->
    <link href="{{ asset('assets/bootstrap/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons CSS -->
    <link href="{{ asset('assets/bootstrap/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App CSS -->
    <link href="{{ asset('assets/bootstrap/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />

    <style>
        body {
            position: relative;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        .account-pages::before {
            content: "";
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: white;
            background-size: cover;
            opacity: 0.8;
            z-index: -1;
        }
    </style>
</head>
<body>
<div class="account-pages my-5 pt-sm-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="login-logo text-center mb-4">
                            <img src="{{ asset('assets/images/AVI.png') }}" alt="AVI Logo" class="img-fluid" style="max-width: 50%;" />
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

                            @if (session('error'))
                                <div class="alert alert-danger">{{ session('error') }}</div>
                            @endif
<form action="{{ route('login') }}" method="post">
    @csrf
    <div class="mb-3">
        <label for="npk" class="form-label">NPK</label>
        <input type="text" 
               class="form-control" 
               id="npk" 
               name="npk" 
               value="{{ old('npk') }}" 
               required 
               autofocus />
    </div>


                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           required />
                                </div>

                                <div class="d-grid">
                                    <button class="btn btn-primary" type="submit">Log In</button>
                                </div>
                                <div class="mt-3 text-center">
                                    <p>Don't have an account? <a href="{{ route('register') }}" class="fw-medium text-primary">Sign up now</a></p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="{{ asset('assets/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

</body>
</html>
