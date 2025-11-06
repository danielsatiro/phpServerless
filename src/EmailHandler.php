<?php

declare(strict_types=1);

namespace App\Handler;

require __DIR__ . '/../vendor/autoload.php';

use Bref\Context\Context;
use Bref\Event\Sqs\SqsEvent;
use Bref\Event\Sqs\SqsHandler;
use Bref\Event\Sqs\SqsRecord;
use Mailgun\Mailgun;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use RuntimeException;

class EmailHandler extends SqsHandler
{
    private Logger $logger;
    private Mailgun $mailgun;
    private string $fromEmail;
    private string $domain;
    private string $link;

    public function __construct()
    {
        $this->logger = new Logger('email');
        $this->logger->pushHandler(new StreamHandler('php://stdout'));

        // In production, use proper env vars or AWS Secrets Manager
        $this->mailgun = Mailgun::create($_ENV['MAILGUN_API_KEY'] ?? '');
        $this->fromEmail = $_ENV['FROM_EMAIL'] ?? 'daniel@satiro.me';
        $this->domain = $_ENV['MAILGUN_DOMAIN'] ?? 'satiro.me';
        $this->link = $_ENV['CONTENT_LINK'] ?? 'https://daniel.satiro.me';
    }

    public function handleSqs(SqsEvent $event, Context $context): void
    {
        foreach ($event->getRecords() as $record) {
            try {
                $this->processRecord($record);
            } catch (\Throwable $e) {
                $this->logger->error('Error processing record', [
                    'error' => $e->getMessage(),
                    'record' => $record->toArray()
                ]);
                throw $e; // Re-throw to mark the message as failed
            }
        }
    }

    private function processRecord(SqsRecord $record): void
    {
        $this->logger->debug('Processing record', ['record' => $record->toArray()]);

        $body = json_decode($record->getBody(), true);
        if (!isset($body['email'])) {
            throw new RuntimeException('Email is required in the message body');
        }

        $params = [
            'from'    => $this->fromEmail,
            'to'      => $body['email'],
            'subject' => 'Serverless com PHP!',
            'html'    => $this->getEmailContent($body['name'] ?? 'Mundo')
        ];

        $this->logger->debug('Sending email', ['params' => $params]);

        try {
            $result = $this->mailgun->messages()->send($this->domain, $params);
            $this->logger->info('Email sent successfully', [
                'messageId' => $result->getId(),
                'to' => $params['to']
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send email', [
                'error' => $e->getMessage(),
                'to' => $params['to']
            ]);
            throw $e;
        }
    }

    private function getEmailContent(string $name): string
    {
        return "
            <html>
            <body>
                <p>Olá, {$name}!</p>
                <p>Obrigado por assistir Serverless com PHP.</p>
                <p>Aqui está o <a href=\"{$this->link}\">link</a> para o conteúdo apresentado na palestra.</p>
                <hr>
                <p>Hello, {$name}!</p>
                <p>Thank you for watching Serverless with PHP.</p>
                <p>Here is the <a href=\"{$this->link}\">link</a> to the content presented in the talk.</p>
            </body>
            </html>
        ";
    }
}

// Return the handler for AWS Lambda
return new EmailHandler();
