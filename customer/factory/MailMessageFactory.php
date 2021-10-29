<?php

declare(strict_types=1);

namespace customer\factory;

use Sys;
use core\interfaces\MailerFacadeInterface;
use core\interfaces\MailMessageFacadeInterface;
use core\mail\MailMessageSwift;

class MailMessageFactory
{
    /**
     * @var string Email to notify site admin.
     */
    private string $notifyEmail;

    /**
     * @var MailerFacadeInterface mailer instance for general purposes.
     */
    private MailerFacadeInterface $mailer;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->notifyEmail = c('site.notify_email');
        $this->mailer = Sys::$app->mail;
    }

    /**
     * Create new mail message.
     * 
     * @param mixed ...$args
     * 
     * @return MailMessageFacadeInterface
     */
    private function new(...$args): MailMessageFacadeInterface
    {
        return new MailMessageSwift(...$args);
    }

    /**
     * Create simple notify message.
     * 
     * @param string $subject
     * @param string $text
     * 
     * @return MailMessageFacadeInterface
     */
    public function createNotify(string $subject, string $text): MailMessageFacadeInterface
    {
        return $this
            ->new($subject, $text)
            ->to($this->notifyEmail)
            ->via($this->mailer);
    }
}