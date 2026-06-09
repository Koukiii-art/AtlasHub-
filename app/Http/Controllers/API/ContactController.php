<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * Receive a contact-form submission and e-mail it to the admin address.
     */
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10',
            'company' => 'nullable|string|max:255',
            'phone'   => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data    = $validator->validated();
        $toEmail = env('CONTACT_EMAIL', 'sweetkouki73@gmail.com');

        try {
            Mail::raw(
                $this->buildEmailBody($data),
                function ($mail) use ($data, $toEmail) {
                    $mail->to($toEmail, 'AtlasHub Admin')
                         ->subject('AtlasHub Contact: ' . $data['subject'])
                         ->replyTo($data['email'], $data['name']);
                }
            );

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message. Please try again later.',
                'debug'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Build a plain-text email body from form data.
     */
    private function buildEmailBody(array $data): string
    {
        $company = $data['company'] ?? 'N/A';
        $phone   = $data['phone']   ?? 'N/A';

        return <<<EOT
New contact message from the AtlasHub website
==============================================

Name    : {$data['name']}
Email   : {$data['email']}
Company : {$company}
Phone   : {$phone}
Subject : {$data['subject']}

Message
-------
{$data['message']}

---
Sent automatically from the AtlasHub Contact Form.
EOT;
    }
}
