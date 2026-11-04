<?php

declare(strict_types=1);

namespace Tests\Support;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use RuntimeException;
use verbb\formie\integrations\helpdesk\Freshdesk;

final class DeliveryWorkerFreshdesk extends Freshdesk
{
    public static string $directory;
    public static string $mode = 'success';

    public static function record(string $step): void
    {
        $file = fopen(self::$directory . '/receipts.jsonl', 'ab');
        if (!$file || !flock($file, LOCK_EX)) {
            throw new RuntimeException('Unable to record the local delivery.');
        }
        fwrite($file, json_encode(['step' => $step, 'pid' => getmypid()], JSON_THROW_ON_ERROR) . "\n");
        fflush($file);
        fsync($file);
        flock($file, LOCK_UN);
        fclose($file);

        if (self::$mode === 'interrupt' && in_array($step, ['ticket', 'email'], true)) {
            // The parent kills only this worker after the receiver has a durable receipt.
            file_put_contents(self::$directory . '/ready', (string)getmypid(), LOCK_EX);
            $deadline = microtime(true) + 30;
            while (microtime(true) < $deadline) {
                usleep(20000);
            }
            throw new RuntimeException('The parent did not terminate the reserved worker.');
        }
    }

    protected function createDeliveryHttpHandler(): callable
    {
        return HandlerStack::create(static function(RequestInterface $request) {
            if ($request->getMethod() === 'GET') {
                return Create::promiseFor(new Response(200, [], '[]'));
            }
            $step = str_ends_with($request->getUri()->getPath(), '/tickets') ? 'ticket' : 'contact';
            self::record($step);
            return Create::promiseFor(self::$mode === 'failure' && $step === 'ticket'
                ? new Response(503, [], '{"message":"Synthetic outage"}')
                : new Response(201, [], '{"id":123}'));
        });
    }
}
