<?php

declare(strict_types=1);

namespace Faustoff\Loggable\Notifications;

use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Monolog\Utils;
use NotificationChannels\Telegram\TelegramMessage;

class LogNotification extends AbstractNotification
{
    public const ERROR = 'error';
    public const WARNING = 'warning';
    public const SUCCESS = 'success';
    public const INFO = 'info';
    public const DEBUG = 'debug';

    protected string $env;
    protected Carbon $datetime;

    public function __construct(
        protected string $callContext,
        protected ?int $callContextPid,
        protected string $callContextUid,
        protected string $message,
        protected string $level,
        protected mixed $context = []
    ) {
        $this->env = App::environment();
        $this->datetime = Carbon::now();
        $this->context = $context instanceof \Throwable ? "{$context}" : $context;
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(ucfirst($this->level) . ': ' . $this->message)
            ->view('loggable::log', [
                'env' => $this->env,
                'datetime' => $this->datetime,
                'callContext' => $this->callContext,
                'callContextPid' => $this->callContextPid,
                'callContextUid' => $this->callContextUid,
                'msg' => $this->message,
                'level' => $this->level,
                'context' => $this->context,
            ])
        ;
    }

    public function toTelegram(mixed $notifiable): TelegramMessage
    {
        return TelegramMessage::create(
            Str::limit($this->message, 512)
            . "\n\nENV: {$this->env}"
            . "\nLevel: {$this->level}"
            . "\nDatetime: {$this->datetime}"
            . "\nLog context: {$this->callContext}"
            . "\nPID: {$this->callContextPid}"
            . "\nUID: {$this->callContextUid}"
            . Str::limit(
                $this->context
                    ? "\nContext: " . (
                    is_string($this->context)
                        ? $this->context
                        : Utils::jsonEncode($this->context, Utils::DEFAULT_JSON_FLAGS | JSON_PRETTY_PRINT)
                    )
                    : '',
                512
            )
        )->options([
            'parse_mode' => '',
            'disable_web_page_preview' => true,
        ]);
    }
}
