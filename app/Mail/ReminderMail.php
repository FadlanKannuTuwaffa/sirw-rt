<?php

namespace App\Mail;

use App\Models\Bill;
use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    protected Bill|Event|null $relatedModel;

    protected ?User $recipientUser;

    protected array $contentMetadata;

    public function __construct(
        protected string $subjectLine,
        protected string $body,
        array $metadata = [],
        Bill|Event|null $model = null,
        ?User $recipient = null,
    ) {
        $this->contentMetadata = $metadata;
        $this->relatedModel = $model;
        $this->recipientUser = $recipient;
    }

    public function build(): self
    {
        return $this->subject($this->subjectLine)
            ->view('emails.reminder')
            ->with([
                'subject' => $this->subjectLine,
                'body' => $this->body,
                'metadata' => $this->contentMetadata,
                'model' => $this->relatedModel,
                'recipient' => $this->recipientUser,
            ]);
    }
}
