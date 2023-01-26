<?php

use App\Models\Contact;
use App\Mail\ContactConfirmation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/mailable', function () {
    if (App::environment('local')) {
        $fakeContact = new Contact;
        $fakeContact->name = "Jane";
        return new ContactConfirmation($fakeContact);
    } else {
        abort(404);
    }
});
