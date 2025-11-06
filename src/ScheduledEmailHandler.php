<?php

declare(strict_types=1);

namespace App\Handler;

require __DIR__ . '/../vendor/autoload.php';

use Bref\Context\Context;
use Bref\Event\Handler;
use Mailgun\Mailgun;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use RuntimeException;

class ScheduledEmailHandler implements Handler
{
    private Logger $logger;
    private Mailgun $mailgun;
    private string $fromEmail;
    private string $domain;
    private string $link;

    public function __construct()
    {
        $this->logger = new Logger('scheduled-email');
        $this->logger->pushHandler(new StreamHandler('php://stdout'));

        $this->mailgun = Mailgun::create($_ENV['MAILGUN_API_KEY']);
        $this->fromEmail = $_ENV['FROM_EMAIL'];
        $this->domain = $_ENV['MAILGUN_DOMAIN'];
        $this->link = $_ENV['CONTENT_LINK'];
    }

    public function handle($event, Context $context)
    {
        $this->logger->info('Processing scheduled event', ['event' => $event]);

        if (!isset($event['type']) || $event['type'] !== 'scheduled') {
            throw new RuntimeException('Invalid event type');
        }

        switch ($event['action'] ?? '') {
            case 'daily-report':
                $this->sendDailyReport($event['parameters'] ?? []);
                break;
            default:
                throw new RuntimeException('Unknown action: ' . ($event['action'] ?? 'none'));
        }

        return [
            'statusCode' => 200,
            'body' => json_encode([
                'message' => 'Scheduled task completed successfully',
                'timestamp' => time()
            ])
        ];
    }

    private function sendDailyReport(array $parameters): void
    {
        $name = $parameters['name'] ?? 'Admin';
        $email = $parameters['email'] ?? 'admin@example.com';

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
        // Example report generation - Replace with your actual metrics gathering logic
        return [
            'date' => date('Y-m-d'),
            'metrics' => [
                'total_requests' => rand(100, 1000),
                'successful_emails' => rand(90, 950),
                'failed_emails' => rand(0, 50),
                'unique_users' => rand(50, 500)
            ]
        ];
    }

    private function getDailyReportTemplate(string $name, array $reportData): string
    {
        return "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h1 style='color: #2c5282;'>Daily Report - {$reportData['date']}</h1>
                    <p>Hello {$name},</p>
                    <p>Here is your daily report summary:</p>
                    <div style='background: #f7fafc; padding: 15px; border-radius: 5px;'>
                        <ul style='list-style-type: none; padding: 0;'>
                            <li style='margin: 10px 0;'>📊 Total Requests: {$reportData['metrics']['total_requests']}</li>
                            <li style='margin: 10px 0;'>✅ Successful Emails: {$reportData['metrics']['successful_emails']}</li>
                            <li style='margin: 10px 0;'>❌ Failed Emails: {$reportData['metrics']['failed_emails']}</li>
                            <li style='margin: 10px 0;'>👥 Unique Users: {$reportData['metrics']['unique_users']}</li>
                        </ul>
                    </div>
                    <p>For more details, visit our <a href=\"{$this->link}\" style='color: #4299e1;'>dashboard</a>.</p>
                    <hr style='border: 1px solid #edf2f7; margin: 20px 0;'>
                    <p style='color: #718096; font-size: 0.875em;'>This is an automated report generated on {$reportData['date']}</p>
                </div>
            </body>
            </html>
        ";
    }
}

// Return the handler for AWS Lambda
return new ScheduledEmailHandler();
