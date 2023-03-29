@extends("layouts.full-width")
@section("content")
    <div class="container-fluid vh-100 sign-in-page">
        <div class="row align-items-center justify-content-center h-100 sign-in-container">
            <div class="col-md-5 col-lg-5 col-xl-4">
                <div class="brand">
                    <a class="navbar-brand">
                        <div class="inner-brand">
                            <svg viewBox="0 0 1100 250" width="80" height="20" data-reactid="8">
                                <g data-reactid="9">
                                    <path data-reactid="10"
                                          d="M121.6 23.4c-60 0-105.1 45.5-105.1 106.2 0 58 44.2 103.2 100.5 103.2h39.5V173H123c-26.3 0-45.4-18.6-45.4-43.8 0-26.1 19.7-46.6 44.5-46.6 24.3 0 44.4 21.8 44.4 48.6v101.4h59.7V131.3c0-61.5-44.8-107.9-104.6-107.9zM676.7 23.3c-53.9 0-92.5 38.7-92.5 92.1v117.3h59.7V114.3c0-18.5 13.8-31.4 32.9-31.4 27.2 0 31.8 19.7 31.8 31.4v118.4h59.7V115.4c-.2-56-35.8-92.1-91.6-92.1zM241.1 97.4v135.3h59.7V106.3c0-17.9 16.6-27.7 32.5-27.7h27.2V23.9h-48.2c-40.2 0-71.2 32-71.2 73.5zM883.4 23.4c-59.4 0-104 45.5-104 106.2 0 58 44.2 103.2 100.5 103.2h37.4V173H886c-26.2 0-46-19-46-43.8 0-26.1 19.4-46.6 43.9-46.6 24.2 0 43.3 21.3 43.3 48.6v101.4h59.7V131.3c0-61.5-44.4-107.9-103.5-107.9zM468 23.7c-56.6 0-102.7 47.1-102.7 105.1 0 57.8 46.1 104.8 102.7 104.8 44.9 0 81.5-24.4 95.4-63.8l2.4-6.8H502l-1.2 1.5c-8.3 9.6-21.3 15.5-33.1 15.5-19.1 0-33.3-6.9-40-26.8h140.8l.4-4.5c3.6-37.8-5.8-70-26.9-93.2-18.6-20.5-44.9-31.8-74-31.8zm-39.5 79.7c8.7-19.9 25.8-25.2 39.2-25.2 14.3 0 32.3 5.3 40.1 25.2h-79.3z"></path>
                                </g>
                            </svg>
                        </div>
                        <span>office</span>
                    </a>
                </div>
                <div>
                    <form action="{{ route('docs.signin') }}" method="post" class="ng-pristine ng-invalid ng-touched" novalidate>
                        @csrf
                        <div class="form-group {{$errors->has('user')? ' has-error' : ''}}">
                            <label for="user">Email or Username</label>
                            <input type="text" placeholder="user@example.com" name="user" id="user" class="form-control" required>
                            <div class="invalid-feedback"> Please provide valid Email or Username.</div>
                        </div>
                        <div class="form-group" {{$errors->has('user')? ' has-error' : ''}}>
                            <label for="password" class="w-100"> Password
                                <a class="float-right text-sm text-muted">
                                    <small>Forgot Password?</small>
                                </a>
                            </label>
                            <input name="password" type="password" class="form-control"
                                   placeholder="************" id="password" required>
                            <div class="invalid-feedback"> Password is required.</div>
                        </div>
                        <button type="submit" class="btn btn-block btn-primary">Sign in</button>
                    </form>

                    @if ($errors->any())
                        <div class="alert alert-danger mt-3">
                            {{ $errors->first() }}
                        </div>
                    @endif

                </div>
            </div>
            <div class="col-md-7 col-lg-7 col-xl-8 d-none d-md-block d-lg-block h-100">
                <div class="sign-cover"></div>
            </div>
        </div>
    </div>
@endsection
