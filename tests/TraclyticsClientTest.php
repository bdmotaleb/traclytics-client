<?php

namespace Traclytics\Tests;

use Traclytics\TraclyticsClient;
use PHPUnit\Framework\TestCase;

class TraclyticsClientTest extends TestCase
{
    public function testConstructorWithValidParameters()
    {
        $client = new TraclyticsClient(
            'https://api.example.com',
            'test-project-key',
            'test-access-token'
        );
        
        $this->assertInstanceOf(TraclyticsClient::class, $client);
    }

    public function testConstructorThrowsExceptionForEmptyBaseUrl()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Base URL cannot be empty');
        
        new TraclyticsClient('', 'test-project-key', 'test-access-token');
    }

    public function testConstructorThrowsExceptionForEmptyProjectKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Project key cannot be empty');
        
        new TraclyticsClient('https://api.example.com', '', 'test-access-token');
    }

    public function testConstructorThrowsExceptionForEmptyAccessToken()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Access token cannot be empty');
        
        new TraclyticsClient('https://api.example.com', 'test-project-key', '');
    }

    public function testConstructorWithOptions()
    {
        $client = new TraclyticsClient(
            'https://api.example.com',
            'test-project-key',
            'test-access-token',
            [
                'maxRetries' => 5,
                'timeoutMs' => 15000
            ]
        );
        
        $this->assertInstanceOf(TraclyticsClient::class, $client);
    }
}

