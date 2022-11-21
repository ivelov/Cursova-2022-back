<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MailNewAnnouncer extends Mailable
{
    use Queueable, SerializesModels;

    public $userName, $conferenceTitle, $conferenceLink, $reportLink, $reportTitle, $reportDuration;

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
        $this->reportDuration = $mailData['reportDuration'];
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'New Announcer',
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
            view: 'mails.NewReport',
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
