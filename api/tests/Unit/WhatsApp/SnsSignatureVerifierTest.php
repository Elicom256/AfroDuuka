<?php

namespace Tests\Unit\WhatsApp;

use App\Services\Ses\SnsSignatureVerifier;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SnsSignatureVerifierTest extends TestCase
{
    private SnsSignatureVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->verifier = new SnsSignatureVerifier;
    }

    #[Test]
    public function it_accepts_sns_certificate_hosts(): void
    {
        foreach ([
            'https://sns.us-east-1.amazonaws.com/SimpleNotificationService-abc.pem',
            'https://sns.eu-west-3.amazonaws.com/SimpleNotificationService-abc.pem',
            'https://sns.cn-northwest-1.amazonaws.com.cn/SimpleNotificationService-abc.pem',
        ] as $url) {
            $this->assertTrue($this->verifier->isAwsCertificateUrl($url), $url);
        }
    }

    #[Test]
    public function it_rejects_non_sns_hosts(): void
    {
        foreach ([
            'http://sns.us-east-1.amazonaws.com/cert.pem',
            'https://amazonaws.com/cert.pem',
            'https://sns.region.amazonaws.com.evil.test/cert.pem',
            'https://s3.amazonaws.com/cert.pem',
            'https://10.0.0.1/cert.pem',
            'https://sns.us-east-1.amazonaws.com.cn.evil.test/cert.pem',
            '/not/a/url',
            '',
        ] as $url) {
            $this->assertFalse($this->verifier->isAwsCertificateUrl($url), $url);
        }
    }
}
