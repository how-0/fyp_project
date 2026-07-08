<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Backpack\Base Routes
|--------------------------------------------------------------------------
|
| Copied from vendor/backpack/crud to use a custom login redirect.
|
*/

Route::group(
    [
        'namespace' => 'Backpack\CRUD\app\Http\Controllers',
        'middleware' => config('backpack.base.web_middleware', 'web'),
        'prefix' => config('backpack.base.route_prefix'),
    ],
    function () {
        if (config('backpack.base.setup_auth_routes')) {
            Route::get('login', [LoginController::class, 'showLoginForm'])->name('backpack.auth.login');
            Route::post('login', [LoginController::class, 'login']);
            Route::get('logout', [LoginController::class, 'logout'])->name('backpack.auth.logout');
            Route::post('logout', [LoginController::class, 'logout']);

            Route::get('register', 'Auth\RegisterController@showRegistrationForm')->name('backpack.auth.register');
            Route::post('register', 'Auth\RegisterController@register');

            if (config('backpack.base.setup_password_recovery_routes', true)) {
                Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('backpack.auth.password.reset');
                Route::post('password/reset', 'Auth\ResetPasswordController@reset');
                Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('backpack.auth.password.reset.token');
                Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('backpack.auth.password.email')->middleware('backpack.throttle.password.recovery:'.config('backpack.base.password_recovery_throttle_access'));
            }

            if (config('backpack.base.setup_email_verification_routes', false)) {
                Route::get('email/verify', 'Auth\VerifyEmailController@emailVerificationRequired')->name('verification.notice');
                Route::get('email/verify/{id}/{hash}', 'Auth\VerifyEmailController@verifyEmail')->name('verification.verify');
                Route::post('email/verification-notification', 'Auth\VerifyEmailController@resendVerificationEmail')->name('verification.send');
            }
        }

        if (config('backpack.base.setup_dashboard_routes')) {
            Route::get('dashboard', 'AdminController@dashboard')->name('backpack.dashboard');
            Route::get('/', 'AdminController@redirect')->name('backpack');
        }

        if (config('backpack.base.setup_my_account_routes')) {
            Route::get('edit-account-info', 'MyAccountController@getAccountInfoForm')->name('backpack.account.info');
            Route::post('edit-account-info', 'MyAccountController@postAccountInfoForm')->name('backpack.account.info.store');
            Route::post('change-password', 'MyAccountController@postChangePasswordForm')->name('backpack.account.password');
        }
    });
