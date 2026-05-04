<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\ContactFormMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $recipient = config('app.contact_email') ?? config('mail.from.address');

        Mail::to($recipient)->send(new ContactFormMail(
            senderName: $data['name'],
            senderEmail: $data['email'],
            emailSubject: $data['subject'],
            body: $data['message'],
        ));

        return response()->json(['message' => 'Sent']);
    }
}
