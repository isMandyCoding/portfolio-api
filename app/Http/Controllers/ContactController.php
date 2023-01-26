<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactConfirmation;
use App\Models\Contact;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

// use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|min:1|max:250',
            'email' => 'required|email:rfc',
            'subject' => 'required|min:1|max:250',
            'content' => 'required|string|min:1|max:500',
            'colorMode' => 'nullable|string',
            'website' => 'nullable|string',
            'formTime' => 'nullable|integer|numeric'
        ]);

        if ($request->has('website') && !empty($request->website)) {
            return $this->storeSuspectedSpamAccount($request);
        }

        if ($request->has('formTime') && $request->formTime <= 5) {
            return $this->storeSuspectedSpamAccount($request);
        }

        try {
            $newContact = $this->storeContact($request);
            Mail::to($newContact->email)->send(new ContactConfirmation($newContact));
            return response()->json([
                'status' => 'success'
            ]);
        } catch (Exception $ex) {
            Log::error("There was an error storing or emailing a new contact");
            return response([
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Store a suspected spam contact.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    private function storeSuspectedSpamAccount(Request $request)
    {
        $spamContact = new Contact;
        $spamContact->name = $request->name;
        $spamContact->email = $request->email;
        $spamContact->subject = $request->subject;
        $spamContact->content = $request->content;
        $spamContact->honeypot = $request->website;
        $spamContact->form_time = $request->formTime;
        $spamContact->visitor = $request->ip();
        $spamContact->save();
        return response()->json('');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Collection $validated
     * @return Contact
     */
    private function storeContact(Request $request)
    {
        Log::info($request->name);
        $newContact = new Contact;
        $newContact->name = $request->name;
        $newContact->email = $request->email;
        $newContact->subject = $request->subject;
        $newContact->content = $request->content;
        Log::info($newContact->toArray());

        $result = $newContact->save();
        if ($result) {
            return $newContact;
        }
    }
}
