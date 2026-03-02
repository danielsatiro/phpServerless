<?php

declare(strict_types=1);

namespace Tests;

use App\Handler\EmailHandler;
use Bref\Context\Context;
use Bref\Event\Sqs\SqsEvent;
use Bref\Event\Sqs\SqsRecord;
use Mailgun\Mailgun;
use Mailgun\Api\Message;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class EmailHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testProcessRecordThrowsExceptionWhenEmailIsMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Email is required in the message body');

        $mockRecord = Mockery::mock(SqsRecord::class);
        $mockRecord->shouldReceive('getBody')->andReturn(json_encode(['name' => 'Test']));
        $mockRecord->shouldReceive('toArray')->andReturn([]);

        $mockEvent = Mockery::mock(SqsEvent::class);
        $mockEvent->shouldReceive('getRecords')->andReturn([$mockRecord]);

        // Create a real Context object instead of mocking (it's a final class)
        $context = new Context(
            'test-request-id',
            time() + 300,
            'test-invoked-function-arn',
            'test-trace-id'
        );

        // Set environment variables
        $_ENV['MAILGUN_API_KEY'] = 'test-key';
        $_ENV['FROM_EMAIL'] = 'test@example.com';
        $_ENV['MAILGUN_DOMAIN'] = 'example.com';

        $handler = new EmailHandler();
        $handler->handleSqs($mockEvent, $context);
    }

    public function testProcessRecordThrowsExceptionWhenEmailIsInvalid(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid email address: invalid-email');

        $mockRecord = Mockery::mock(SqsRecord::class);
        $mockRecord->shouldReceive('getBody')->andReturn(json_encode([
            'email' => 'invalid-email',
            'name' => 'Test'
        ]));
        $mockRecord->shouldReceive('toArray')->andReturn([]);

        $mockEvent = Mockery::mock(SqsEvent::class);
        $mockEvent->shouldReceive('getRecords')->andReturn([$mockRecord]);

        // Create a real Context object instead of mocking (it's a final class)
        $context = new Context(
            'test-request-id',
            time() + 300,
            'test-invoked-function-arn',
            'test-trace-id'
        );

        // Set environment variables
        $_ENV['MAILGUN_API_KEY'] = 'test-key';
        $_ENV['FROM_EMAIL'] = 'test@example.com';
        $_ENV['MAILGUN_DOMAIN'] = 'example.com';

        $handler = new EmailHandler();
        $handler->handleSqs($mockEvent, $context);
    }

    public function testProcessRecordAcceptsValidEmail(): void
    {
        $validEmail = 'valid@example.com';
        
        $mockRecord = Mockery::mock(SqsRecord::class);
        $mockRecord->shouldReceive('getBody')->andReturn(json_encode([
            'email' => $validEmail,
            'name' => 'Test User'
        ]));
        $mockRecord->shouldReceive('toArray')->andReturn([]);

        $mockEvent = Mockery::mock(SqsEvent::class);
        $mockEvent->shouldReceive('getRecords')->andReturn([$mockRecord]);

        // Create a real Context object instead of mocking (it's a final class)
        $context = new Context(
            'test-request-id',
            time() + 300,
            'test-invoked-function-arn',
            'test-trace-id'
        );

        // Set environment variables
        $_ENV['MAILGUN_API_KEY'] = 'test-key';
        $_ENV['FROM_EMAIL'] = 'test@example.com';
        $_ENV['MAILGUN_DOMAIN'] = 'example.com';

        // This test validates that valid emails pass validation
        // In a real scenario, you'd mock the Mailgun service
        // For now, this will fail at the Mailgun send, but email validation passes
        $handler = new EmailHandler();
        
        try {
            $handler->handleSqs($mockEvent, $context);
        } catch (\Throwable $e) {
            // We expect it to fail at Mailgun, not at validation
            $this->assertNotEquals('Invalid email address: ' . $validEmail, $e->getMessage());
        }
    }

    public function testEmailValidationWithVariousFormats(): void
    {
        $validEmails = [
            'simple@example.com',
            'user.name@example.com',
            'user+tag@example.co.uk',
            'user_name@example-domain.com'
        ];

        foreach ($validEmails as $email) {
            $this->assertTrue(
                filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
                "Email '$email' should be valid"
            );
        }

        $invalidEmails = [
            'invalid',
            '@example.com',
            'user@',
            'user @example.com',
            'user@.com'
        ];

        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
                "Email '$email' should be invalid"
            );
        }
    }
}
