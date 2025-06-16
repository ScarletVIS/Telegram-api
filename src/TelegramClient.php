<?php

namespace TelegramClient;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use TelegramClient\Exceptions\TelegramException;

class TelegramClient
{
    private string $token;
    private HttpClient $httpClient;
    private Logger $logger;
    private string $apiUrl;

    public function __construct(string $token, ?HttpClient $httpClient = null, ?Logger $logger = null)
    {
        $this->token = $token;
        $this->apiUrl = "https://api.telegram.org/bot{$token}/";

        $this->httpClient = $httpClient ?? new HttpClient([
            'timeout' => 5.0,
        ]);

        if ($logger === null) {
            $logger = new Logger('telegram_client');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../telegram.log', Logger::DEBUG));
        }
        $this->logger = $logger;
    }

    /**
     * Универсальный метод для вызова Telegram API
     *
     * @param string $method
     * @param array $params
     * @return array
     * @throws TelegramException|GuzzleException
     */
    private function request(string $method, array $params = []): array
    {
        $url = $this->apiUrl . $method;

        try {
            $this->logger->info("Отправка запроса", ['method' => $method, 'params' => $params]);

            $response = $this->httpClient->post($url, [
                'json' => $params
            ]);

            $body = $response->getBody()->getContents();

            $this->logger->info("Получен ответ", ['response' => $body]);

            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new TelegramException("Ошибка декодирования JSON: " . json_last_error_msg());
            }

            if (isset($data['ok']) && $data['ok'] === true) {
                return $data['result'];
            }

            $errorMsg = $data['description'] ?? 'Неизвестная ошибка Telegram API';
            throw new TelegramException("Ошибка API: $errorMsg");

        } catch (RequestException $e) {
            $this->logger->error("Ошибка HTTP запроса", ['exception' => $e]);
            throw new TelegramException("Ошибка HTTP запроса: " . $e->getMessage());
        }
    }

    /**
     * Отправка текстового сообщения
     *
     * @param int $chatId
     * @param string $text
     * @param array $options
     * @return array
     * @throws TelegramException|GuzzleException
     */
    public function sendMessage(int $chatId, string $text, array $options = []): array
    {
        $params = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
        ], $options);

        return $this->request('sendMessage', $params);
    }

    /**
     * Получить обновления (updates) от Telegram API
     *
     * @param array $options
     * @return array
     * @throws TelegramException|GuzzleException
     */
    public function getUpdates(array $options = []): array
    {
        return $this->request('getUpdates', $options);
    }

    /**
     * Удалить сообщение из чата
     *
     * @param int $chatId
     * @param int $messageId
     * @return bool
     * @throws TelegramException|GuzzleException
     */
    public function deleteMessage(int $chatId, int $messageId): bool
    {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ];

        $result = $this->request('deleteMessage', $params);

        return $result === true;
    }

    /**
     * Установить webhook для бота
     *
     * @param string $url
     * @param array $options
     * @return bool
     * @throws TelegramException|GuzzleException
     */
    public function setWebhook(string $url, array $options = []): bool
    {
        $params = array_merge(['url' => $url], $options);

        $result = $this->request('setWebhook', $params);

        return $result === true;
    }
}
