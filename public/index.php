<?php
set_exception_handler(function ($e) {
    error_log($e);
});
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    error_log("$errstr in $errfile on line $errline");
});

require __DIR__ . '/../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_POST['email'])) {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $sqs = new \Aws\Sqs\SqsClient([
        'version' => 'latest',
        'region' => 'sa-east-1',
    ]);

    // Get the queue URL by name
    $queueName = 'app-dev-hello-queue';
    $result = $sqs->getQueueUrl([
        'QueueName' => $queueName,
    ]);
    $queueUrl = $result->get('QueueUrl');

    // Send the message
    $sendResult = $sqs->sendMessage([
        'QueueUrl' => $queueUrl,
        'MessageBody' => json_encode(['name' => $name, 'email' => $email]),
    ]);

    echo '<pre>Message sent to SQS. MessageId: ' . htmlspecialchars($sendResult->get('MessageId')) . '</pre>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Welcome!</title>
    <link href="https://fonts.googleapis.com/css?family=Dosis:300&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="flex h-screen">
<div class="rounded-full mx-auto self-center relative flex flex-col items-center justify-center" style="height: 500px; width: 500px; background: linear-gradient(123.19deg, #266488 3.98%, #258ECB 94.36%)">
    <h1 class="font-light w-full text-center text-blue-200 mb-6" style="font-family: Dosis; font-size: 32px;">Hello there,</h1>
    <form method="POST" class="w-3/4 bg-white bg-opacity-80 rounded-lg p-6 flex flex-col gap-4 shadow">
        <label class="block">
            <span class="text-gray-700">Name</span>
            <input type="text" name="name" required class="mt-1 block w-full rounded border-gray-300 focus:border-blue-400 focus:ring focus:ring-blue-200 focus:ring-opacity-50" />
        </label>
        <label class="block">
            <span class="text-gray-700">Email</span>
            <input type="email" name="email" required class="mt-1 block w-full rounded border-gray-300 focus:border-blue-400 focus:ring focus:ring-blue-200 focus:ring-opacity-50" />
        </label>
        <button type="submit" class="mt-4 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Send</button>
    </form>
</div>
</body>
</html>
