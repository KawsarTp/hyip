<?php

namespace App\Jobs;

use App\Services\EmailNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTemplateEmail implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 86400;

    /**
     * @param  array<string, scalar|null>  $variables
     */
    public function __construct(
        public string $templateKey,
        public string $recipient,
        public string $recipientName,
        public array $variables,
        public string $eventKey,
        public ?string $locale = null,
    ) {
        $this->onQueue('emails');
        $this->afterCommit();
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function uniqueId(): string
    {
        return $this->eventKey;
    }

    public function handle(EmailNotificationService $emailNotificationService): void
    {
        $emailNotificationService->send(
            $this->templateKey,
            $this->recipient,
            $this->recipientName,
            $this->variables,
            $this->locale,
        );
    }
}
