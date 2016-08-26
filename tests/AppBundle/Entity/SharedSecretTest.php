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

        print var_dump($secret);
        $decoded = Base32::decode($secret);

        // The shared-secret should be 32 bytes long
        $this->assertEquals(16, strlen($decoded));
    }
}