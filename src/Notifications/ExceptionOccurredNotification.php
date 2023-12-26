<?php

declare(strict_types=1);

namespace Faustoff\Contextify\Notifications;

use Carbon\Carbon;
use Faustoff\Contextify\Exceptions\ExceptionOccurredNotificationFailedException;
use Faustoff\Contextify\Loggable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use NotificationChannels\Telegram\TelegramMessage;

class ExceptionOccurredNotification extends AbstractNotification
{
    use Loggable;

    protected string $env;
    protected Carbon $datetime;
    protected ?int $pid;
    protected string $exception;

    public function __construct(\Throwable $exception)
    {
        $this->env = App::environment();
        $this->datetime = Carbon::now();
        $this->pid = getmypid() ?: null;
        $this->exception = "{$exception}";
        // TODO: add memory usage
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Exception')
            ->view('contextify::exception', [
                'env' => $this->env,
                'datetime' => $this->datetime,
                'pid' => $this->pid,
                'exception' => $this->exception,
            ])
        ;
    }

    public function toTelegram(mixed $notifiable): TelegramMessage
    {
        return TelegramMessage::create(
            Str::limit($this->exception, 1024)
            . "\n\nENV: {$this->env}"
            . "\nDatetime: {$this->datetime}"
            . "\nPID: {$this->pid}"
        )->options([
            'parse_mode' => '',
            'disable_web_page_preview' => true,
        ]);
    }

    public function failed(\Throwable $e)
    {
        $this->logError('Notification send failed', $e);

        // To prevent infinite exception notification ExceptionOccurredNotificationFailedException should be
        // added to ignore in application exception handler.
        throw new ExceptionOccurredNotificationFailedException('Notification failed', 0, $e);
    }
}
