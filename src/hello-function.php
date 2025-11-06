<?php

require __DIR__ . '/../vendor/autoload.php';
use Mailgun\Mailgun;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

return function ($event) {
    $logger = new Logger('email');
    $logger->pushHandler(new StreamHandler('php://stdout'));

    $logger->debug('Event', $event);

    $mg = Mailgun::create($_ENV['MAILGUN_API_KEY'] ?? '');

    $link = 'https://daniel.satiro.me';

    $record = $event['Records'][0] ?? [];
    $body = isset($record['body']) ? json_decode($record['body'], true) : [];
    $params = [
        'from'    => 'daniel@satiro.me',
        'to'      => $body['email'] ?? '',
        'subject' => 'Serverless com PHP!',
        'text'    => "Olá, " . ($body['name'] ?? 'Mundo') . "!
        Obrigado por assistir Serverless com PHP.
        
        Aqui está o <a href=\"{$link}\">link</a> para o conteúdo apresentado na palestra.
        
        ---
        
        Hello, " . ($body['name'] ?? 'World') . "!
        Thank you for watching Serverless with PHP.
        
        Here is the <a href=\"{$link}\">link</a> to the content presented in the talk.",
    ];

    $logger->debug('Params', $params);
    $result = $mg->messages()->send('satiro.me', $params);

    $logger->debug($result->getMessage());
    $logger->info('Email enviado com sucesso');
};