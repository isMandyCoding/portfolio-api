<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use App\Mail\ContactConfirmation;

class ContactTest extends TestCase
{
    const FROM_ADDRESS = "mandy@email.com";
    const FROM_NAME = "Mandy Codes";

    const REPLY_TO_NAME = "Mandy Me";
    const REPLY_TO_ADDRESS = "mandyreplies@email.com";

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('mail.from.name', $this::FROM_ADDRESS);
        Config::set('mail.from.address', $this::FROM_ADDRESS);

        Config::set('mail.reply_to.name', $this::REPLY_TO_NAME);
        Config::set('mail.reply_to.address', $this::REPLY_TO_ADDRESS);

        Mail::fake();

    }

    /**
     * post to /contact with valid contact info creates new contact in db and returns success status.
     *
     * @return void
     */
    public function test_post_to_contact_with_valid_contact_creates_new_contact_and_queues_email()
    {
        $fakeContact = [
            'name' => fake()->name(),
            'email' => fake()->email(),
            'subject' => fake()->title(),
            'content' => fake()->text(500),
            'website' => null,
            'formTime' => fake()->numberBetween(6, 300),
        ];

        $response = $this->postJson('/api/contact', $fakeContact);

        $expected = [
            'status' => 'success'
        ];
        $expectedDbRecord = [
            'name' => $fakeContact['name'],
            'email' => $fakeContact['email'],
            'subject' => $fakeContact['subject'],
            'content' => $fakeContact['content'],
            'honeypot' => null,
            'form_time' => null,
        ];
        $response->assertStatus(200);
        $response->assertExactJson($expected);
        $this->assertDatabaseHas('contacts', $expectedDbRecord);
        Mail::assertQueued(ContactConfirmation::class, function (ContactConfirmation $mail)
        use ($fakeContact) {
            return $mail->hasTo($fakeContact["email"]);
        });
    }

    /**
     * post to /contact with suspiciously short form time creates spam contact and does not queue email
     *
     * @return void
     */
    public function test_post_to_contact_with_suspiciously_short_form_time_creates_spam_contact_and_does_not_queue_email()
    {
        $fakeContact = [
            'name' => fake()->name(),
            'email' => fake()->email(),
            'subject' => fake()->title(),
            'content' => fake()->text(500),
            'website' => null,
            'formTime' => fake()->numberBetween(0, 5),
        ];

        $response = $this->postJson('/api/contact', $fakeContact);

        $expected = [''];

        $expectedDbRecord = [
            'name' => $fakeContact['name'],
            'email' => $fakeContact['email'],
            'subject' => $fakeContact['subject'],
            'content' => $fakeContact['content'],
            'honeypot' => null,
            'form_time' => $fakeContact['formTime'],
        ];
        $response->assertStatus(200);
        $response->assertExactJson($expected);
        $this->assertDatabaseHas('contacts', $expectedDbRecord);
        Mail::assertNotQueued(ContactConfirmation::class);
    }

    /**
     * post to /contact with honeypot filled creates spam contact and does not queue email
     *
     * @return void
     */
    public function test_post_to_contact_with_honeypot_filled_creates_spam_contact_and_does_not_queue_email()
    {
        $fakeContact = [
            'name' => fake()->name(),
            'email' => fake()->email(),
            'subject' => fake()->title(),
            'content' => fake()->text(500),
            'website' => fake()->text(),
            'formTime' => fake()->numberBetween(6, 30),
        ];

        $response = $this->postJson('/api/contact', $fakeContact);

        $expected = [''];

        $expectedDbRecord = [
            'name' => $fakeContact['name'],
            'email' => $fakeContact['email'],
            'subject' => $fakeContact['subject'],
            'content' => $fakeContact['content'],
            'honeypot' => $fakeContact['website'],
            'form_time' => $fakeContact['formTime'],
        ];
        $response->assertStatus(200);
        $response->assertExactJson($expected);
        $this->assertDatabaseHas('contacts', $expectedDbRecord);
        Mail::assertNotQueued(ContactConfirmation::class);
    }
}
