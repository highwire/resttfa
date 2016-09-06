<?php

namespace Tests\AppBundle\Entity;

use AppBundle\Entity\SharedSecret;
use Base32\Base32;

class SharedSecretTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerate()
    {
        $ss = new SharedSecret();
        $ss->generateSharedSecret();

        $secret = $ss->getSharedSecret();

        // The shared-secret should be 16 bytes long encoded
        $this->assertEquals(16, strlen($secret));

        // The shared-secret should properly decode
        $decoded = Base32::decode($secret);

        // The shared-secret should be 10 bytes (80 bits of security) long decoded
        $this->assertEquals(10, strlen($decoded));
    }
}