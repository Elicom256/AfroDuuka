<?php

use App\Http\Controllers\UnsubscribeNotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'API running',
    ]);
});

/*
| One-click unsubscribe (RFC 8058).
|
| Signed rather than authenticated because mail clients follow this with no session
| and no cookies. 'signed' is the whole authorisation here — do not add auth middleware
| to it, and do not remove the signature check, or anyone who guesses a delivery id
| could unsubscribe an arbitrary address.
|
| Deliberately a GET and not a POST-only route: List-Unsubscribe-Post is a convenience
| for clients that support it, but plain GET is what every other client does and what
| the header itself promises.
*/
Route::middleware('signed')->get(
    'notifications/unsubscribe/{delivery}',
    UnsubscribeNotificationController::class
)->name('notifications.unsubscribe');
