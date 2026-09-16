<?php
namespace Tests\Support;

use verbb\formie\elements\Submission;
use verbb\formie\integrations\payments\Eway;
use verbb\formie\models\PaymentFieldPayload;

class ConcurrentPaymentIntegration extends Eway
{
    private string $capturePath;
    private bool $interrupt;
    public function configureCapture(string $path, bool $interrupt): void { $this->capturePath = $path; $this->interrupt = $interrupt; }
    protected function getPaymentFieldPayload(Submission $submission): PaymentFieldPayload
    {
        return new PaymentFieldPayload($this->handle, 'payment', ['ewayTokenData' => ['cardNumber' => 'test-encrypted', 'securityCode' => 'test-encrypted-cvn', 'expiryDate' => '12/30']]);
    }
    public function request(string $method, string $uri, array $options = []): mixed
    {
        file_put_contents($this->capturePath, "accepted\n", FILE_APPEND | LOCK_EX);
        usleep(300000);
        if ($this->interrupt) { exit(37); }
        return ['TransactionStatus' => true, 'TransactionID' => 'parallel-' . $this->id, 'ResponseCode' => '00'];
    }
}
