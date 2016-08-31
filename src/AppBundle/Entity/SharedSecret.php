<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Base32\Base32;
use GoogleAuthenticator\GoogleAuthenticator;

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
        $ga = new GoogleAuthenticator();
        $this->secret = $ga->createSecret();
        $this->accessToken = $ga->createSecret();
    }

    public function getSharedSecret()
    {
        return $this->secret;
    }

    public function verifyToken($token) {
        $ga = new GoogleAuthenticator();
        return $ga->verifyCode($this->secret, 2);
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function verifyAccessToken($user_acces_token) {
        return hash_equals($this->accessToken, $user_acces_token);
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
}