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
                $body = json_decode($record->getBody(), true);

                // Check if this is a scheduled event
                if (isset($body['type']) && $body['type'] === 'scheduled') {
                    $this->handleScheduledEvent($body);
                } else {
                    $this->processRecord($record);
                }
            } catch (\Throwable $e) {
                $this->logger->error('Error processing record', [
                    'error' => $e->getMessage(),
                    'record' => $record->toArray()
                ]);
                throw $e; // Re-throw to mark the message as failed
            }
        }
    }

    private function handleScheduledEvent(array $body): void
    {
        if ($body['action'] === 'daily-report') {
            $this->sendDailyReport($body['parameters'] ?? []);
        }
    }

    private function sendDailyReport(array $parameters): void
    {
        $name = $parameters['name'] ?? 'Admin';
        $email = $parameters['email'] ?? 'admin@example.com';

        // Here you would typically:
        // 1. Generate daily statistics/report
        // 2. Format the report
        // 3. Send it via email
        $reportData = $this->generateDailyReport();

        $params = [
            'from'    => $this->fromEmail,
            'to'      => $email,
            'subject' => 'Daily Report - ' . date('Y-m-d'),
            'html'    => $this->getDailyReportTemplate($name, $reportData)
        ];

        $this->logger->info('Sending daily report', ['to' => $email]);

        try {
            $result = $this->mailgun->messages()->send($this->domain, $params);
            $this->logger->info('Daily report sent successfully', [
                'messageId' => $result->getId(),
                'to' => $params['to']
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send daily report', [
                'error' => $e->getMessage(),
                'to' => $params['to']
            ]);
            throw $e;
        }
    }

    private function generateDailyReport(): array
    {
        // Example report generation
        return [
            'date' => date('Y-m-d'),
            'metrics' => [
                'total_requests' => rand(100, 1000),
                'successful_emails' => rand(90, 950),
                'failed_emails' => rand(0, 50)
            ]
        ];
    }

    private function getDailyReportTemplate(string $name, array $reportData): string
    {
        return "
            <html>
            <body>
                <h1>Daily Report - {$reportData['date']}</h1>
                <p>Hello {$name},</p>
                <p>Here is your daily report summary:</p>
                <ul>
                    <li>Total Requests: {$reportData['metrics']['total_requests']}</li>
                    <li>Successful Emails: {$reportData['metrics']['successful_emails']}</li>
                    <li>Failed Emails: {$reportData['metrics']['failed_emails']}</li>
                </ul>
                <p>For more details, visit our <a href=\"{$this->link}\">dashboard</a>.</p>
                <hr>
                <p><small>This is an automated report.</small></p>
            </body>
            </html>
        ";
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
