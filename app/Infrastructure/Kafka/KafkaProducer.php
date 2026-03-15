<?php

namespace App\Infrastructure\Kafka;

use Illuminate\Support\Facades\Log;
use RdKafka\Conf;
use RdKafka\Producer;

class KafkaProducer
{
    private ?Producer $producer = null;

    public function produce(string $topic, array $message): bool
    {
        try {
            $producer = $this->getProducer();
            $kafkaTopic = $producer->newTopic($topic);

            $payload = json_encode($message, JSON_THROW_ON_ERROR);
            $kafkaTopic->produce(RD_KAFKA_PARTITION_UA, 0, $payload);

            // Wait for message delivery
            $producer->poll(0);

            for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
                $result = $producer->flush(1000);

                if ($result === RD_KAFKA_RESP_ERR_NO_ERROR) {
                    return true;
                }
            }

            Log::warning("Kafka flush timeout for topic {$topic}");

            return true;
        } catch (\Exception $e) {
            Log::error("Kafka producer error: {$e->getMessage()}", [
                'topic' => $topic,
                'message' => $message,
            ]);

            return false;
        }
    }

    private function getProducer(): Producer
    {
        if ($this->producer === null) {
            $conf = new Conf;
            $conf->set('metadata.broker.list', config('kafka.brokers'));
            $conf->set('socket.timeout.ms', (string) config('kafka.producer.timeout_ms', 5000));
            $conf->set('message.send.max.retries', (string) config('kafka.producer.retry_count', 3));

            $this->producer = new Producer($conf);
        }

        return $this->producer;
    }
}
