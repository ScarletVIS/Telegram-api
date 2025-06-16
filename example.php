<?php

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client as HttpClient;
use TelegramClient\Exceptions\TelegramException;
use TelegramClient\TelegramClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$logger = new Logger('api-logger');
$logger->pushHandler(new StreamHandler($_ENV['LOG_PATH'] ?: __DIR__ . '/logs/api-telegram.log'));

$httpClient = new HttpClient([
    'timeout' => (float)($_ENV['TIMEOUT'] ?: 5.0),
]);

$token = $_ENV['TELEGRAM_BOT_TOKEN'] ?? null;
if (!$token) {
    die("❌ Токен не загружен! Проверь файл .env\n");
}

$client = new TelegramClient($token, $httpClient, $logger);

try {
    $chatId = 5302145640;

    $text = "Привет! Это сообщение из собственного Telegram клиента.";

    $response = $client->sendMessage($chatId, $text);

    echo "Сообщение отправлено успешно. ID сообщения: " . $response['message_id'] . PHP_EOL;

} catch (TelegramException $e) {
    echo "Ошибка Telegram API: " . $e->getMessage() . PHP_EOL;
}
