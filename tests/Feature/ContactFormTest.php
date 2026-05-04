<?php

use App\Mail\ContactFormMail;
use App\Models\Doctor;
use Illuminate\Support\Facades\Mail;

it('guest can access doctors list without auth', function (): void {
    $this->getJson('/api/v1/doctors')->assertOk();
});

it('guest can access doctor slots without auth', function (): void {
    $doctor = Doctor::factory()->create();
    $this->getJson("/api/v1/doctors/{$doctor->id}/slots")->assertOk();
});

it('GET to /api/v1/contact returns 405 method not allowed', function (): void {
    $this->getJson('/api/v1/contact')->assertStatus(405);
});

it('POST /api/v1/contact with valid data sends mail and returns 200', function (): void {
    Mail::fake();

    $this->postJson('/api/v1/contact', [
        'name' => 'Jonas Jonaitis',
        'email' => 'jonas@example.com',
        'subject' => 'Klausimas',
        'message' => 'Norėčiau sužinoti daugiau apie paslaugas.',
    ])->assertOk()->assertJson(['message' => 'Sent']);

    Mail::assertSent(ContactFormMail::class, function (ContactFormMail $mail): bool {
        return $mail->senderName === 'Jonas Jonaitis'
            && $mail->senderEmail === 'jonas@example.com'
            && $mail->emailSubject === 'Klausimas';
    });
});

it('POST /api/v1/contact with missing fields returns 422', function (): void {
    $this->postJson('/api/v1/contact', [])->assertStatus(422)->assertJsonValidationErrors(['name', 'email', 'subject', 'message']);
});

it('POST /api/v1/contact rate limits after 5 requests from same IP', function (): void {
    Mail::fake();

    $payload = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'subject' => 'Test',
        'message' => 'Test message',
    ];

    foreach (range(1, 5) as $i) {
        $this->postJson('/api/v1/contact', $payload)->assertOk();
    }

    $this->postJson('/api/v1/contact', $payload)->assertStatus(429);
});
