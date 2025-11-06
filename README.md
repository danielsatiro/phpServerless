# PHP Serverless Application

A serverless PHP application using AWS Lambda, SQS, and the Bref framework. This application provides an API endpoint that processes form submissions and sends emails through a queue-based architecture.

## Architecture

- **API Gateway**: Handles HTTP requests
- **SQS Queue**: Manages message queuing for email processing
- **Lambda Functions**: Process requests and handle email sending
- **Mailgun**: Email delivery service

## Prerequisites

- PHP 8.2+
- Composer
- AWS CLI configured with appropriate credentials
- Node.js (for Serverless Framework)
- Docker (for local development)

## Installation

1. Clone the repository:
```bash
git clone git@github.com:danielsatiro/phpServerless.git
cd phpServerless
```

2. Install PHP dependencies:
```bash
composer install
```

3. Configure environment variables:

For local development, create a .env file:
```bash
MAILGUN_API_KEY=your-mailgun-api-key
FROM_EMAIL=your-from-email
MAILGUN_DOMAIN=your-domain
CONTENT_LINK=your-content-link
```

For production, store secrets in AWS Secrets Manager:
```bash
# Create a secret in AWS Secrets Manager
aws secretsmanager create-secret \
    --name app/mailgun \
    --description "Mailgun API credentials" \
    --secret-string "{\"api-key\":\"your-mailgun-api-key\"}"

# The secret can be accessed in serverless.yml using:
# ${ssm:/aws/reference/secretsmanager/app/mailgun/api-key}
```

These secrets are automatically loaded by the application using the IAM role configured in serverless.yml.

## Project Structure

```
├── docker/             # Docker configuration files
├── public/            
│   └── index.php      # Main API endpoint
├── src/
│   ├── EmailHandler.php     # Bref-based Lambda function
│   └── hello-function.php   # Legacy Lambda function
├── composer.json
├── serverless.yml     # Serverless Framework configuration
└── docker-compose.yml # Local development setup
```

## Available Endpoints

### POST /
Accepts form submissions with the following fields:
- `name`: User's name
- `email`: User's email address

The submission is queued in SQS for processing.

## Deployment

Deploy to AWS using Bref:
```bash
serverless deploy
```

## Local Development

1. Start the local development environment:
```bash
docker-compose up -d
docker exec -it phpserverless-php-1 bash
npm install -g osls
serverless config credentials --provider aws --key "<YOURAWS_KEY>" --secret "<YOUR_AWS_SECRET>"
```

2. Access the application at `http://localhost`

## Lambda Functions

### hello-function
Legacy Lambda function that processes SQS messages.

### bref-hello-function
Enhanced Bref-based Lambda function with:
- Proper dependency injection
- Environment variable configuration
- Structured logging
- Error handling
- HTML email templates
- SQS batch processing

## Configuration

The application is configured through `serverless.yml` and environment variables. Key configurations:

- AWS Region: sa-east-1
- SQS Queue: app-dev-hello-queue
- Runtime: PHP 8.2
- Memory: 128MB
- Timeout: 10 seconds

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Security

- Environment variables are used for sensitive data
- AWS IAM roles control access to services
- Input validation is implemented
- Error handling prevents information disclosure

## Support

For support, please open an issue in the repository.
