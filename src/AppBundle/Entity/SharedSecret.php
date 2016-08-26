<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Base32\Base32;

/**
 * @ORM\Entity
 */
class SharedSecret
{

    /**
     * Email address associated with the shared secret
     * 
     * @ORM\Column(unique=TRUE)
     * @ORM\Id
     */
    private $email;

    /**
     * The actual shared secret
     * 
     * @ORM\Column()
     */
    private $secret;

    /**
     * Access token used as a one-time-token to access the secret once by the user.
     * After the user has accessed the secret, this value is emptied, and the secret can no longe be read. 
     * 
     * @ORM\Column()
     */
    private $accessToken;

    public function generateSharedSecret()
    {
        $this->secret = $this->generateSecureRandom();
        $this->accessToken = $this->generateSecureRandom();
    }

    public function getSharedSecret()
    {
        return $this->secret;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid Email");
        }
        $this->email = $email;
    }

    public function IsDistributed() {
        return (empty($this->$accessToken) && !empty($this->secret));
    }

    public function markDistributed() {
        $this->$accessToken = '';
    }

    /**
     * Securely generate random bytes in base32 encoding
     * 
     * @param int $bytes
     *   Number of bytes to generate. Defaults to 16, which is 128 bits. 
     */
    private function generateSecureRandom($numbytes= 16) {
        $rnd = random_bytes($numbytes);
        $encoded = Base32::encode($rnd);
        return $encoded;
    }
}