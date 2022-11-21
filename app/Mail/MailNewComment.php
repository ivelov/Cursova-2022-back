<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MailNewComment extends Mailable
{
    use Queueable, SerializesModels;

    public $userName, $conferenceTitle, $conferenceLink, $reportLink, $reportTitle, $reportTime;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($mailData)
    {
        $this->userName = $mailData['userName'];
        $this->conferenceTitle = $mailData['conferenceTitle'];
        $this->conferenceLink = $mailData['conferenceLink'];
        $this->reportLink = $mailData['reportLink'];
        $this->reportTitle = $mailData['reportTitle'];
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'New Comment',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'mails.NewComment',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
