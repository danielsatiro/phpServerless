<?php

declare(strict_types=1);

namespace App\Handler;

require __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;
use Bref\Context\Context;
use Bref\Event\S3\S3Event;
use Bref\Event\S3\S3Handler;
use League\Csv\Reader;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use RuntimeException;

/**
 * Example S3 Event Handler
 * This handler processes CSV files uploaded to S3
 * To use it, uncomment the s3-processor function in serverless.yml
 */
class S3Handler extends S3Handler
{
    private Logger $logger;
    private S3Client $s3Client;
    private string $bucket;

    public function __construct()
    {
        $this->logger = new Logger('s3-processor');
        $this->logger->pushHandler(new StreamHandler('php://stdout'));
        
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region'  => $_ENV['AWS_REGION'] ?? 'sa-east-1'
        ]);
    }

    public function handleS3(S3Event $event, Context $context): void
    {
        foreach ($event->getRecords() as $record) {
            try {
                $bucket = $record->getBucket();
                $key = $record->getKey();
                
                $this->logger->info('Processing file', [
                    'bucket' => $bucket,
                    'key' => $key
                ]);

                // Download file from S3
                $result = $this->s3Client->getObject([
                    'Bucket' => $bucket,
                    'Key'    => $key
                ]);

                // Process CSV content
                $csv = Reader::createFromString((string) $result['Body']);
                $csv->setHeaderOffset(0); // First row is headers

                // Example processing
                foreach ($csv as $row) {
                    $this->processRow($row);
                }

                // Optional: Move processed file to 'processed' folder
                $this->s3Client->copyObject([
                    'Bucket'     => $bucket,
                    'Key'        => 'processed/' . basename($key),
                    'CopySource' => "{$bucket}/{$key}"
                ]);

                // Optional: Delete original file
                $this->s3Client->deleteObject([
                    'Bucket' => $bucket,
                    'Key'    => $key
                ]);

                $this->logger->info('File processed successfully', [
                    'bucket' => $bucket,
                    'key' => $key
                ]);

            } catch (\Throwable $e) {
                $this->logger->error('Error processing file', [
                    'error' => $e->getMessage(),
                    'bucket' => $bucket ?? null,
                    'key' => $key ?? null
                ]);
                throw $e;
            }
        }
    }

    private function processRow(array $row): void
    {
        // Example row processing
        // Add your business logic here
        $this->logger->debug('Processing row', ['data' => $row]);

        // Example: Process user data from CSV
        if (isset($row['email'], $row['name'])) {
            // You could:
            // 1. Save to database
            // 2. Send to SQS queue
            // 3. Trigger email notification
            // 4. Generate reports
            
            // Example: Send to SQS queue
            /*
            $sqs = new \Aws\Sqs\SqsClient([
                'version' => 'latest',
                'region'  => $_ENV['AWS_REGION'] ?? 'sa-east-1'
            ]);
            
            $sqs->sendMessage([
                'QueueUrl'    => $_ENV['QUEUE_URL'],
                'MessageBody' => json_encode([
                    'name'  => $row['name'],
                    'email' => $row['email']
                ])
            ]);
            */
        }
    }
}

// Return the handler for AWS Lambda
return new S3Handler();
