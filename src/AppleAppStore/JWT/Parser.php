<?php

namespace ReceiptValidator\AppleAppStore\JWT;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser as JwtParser;
use Lcobucci\JWT\Token\Plain;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * Jws Parser class
 * This class is used to parse a JWS string into a Jws object
 */
class Parser
{
    /**
     * @var JwtParser
     */
    private JwtParser $parser;

    /**
     * @param JwtParser $parser
     */
    public function __construct(JwtParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * A helper method to parse a JWS string into a Jws object
     *
     * @param string $signedPayload
     *
     * @return Jws
     */
    public static function toJws(string $signedPayload): Jws
    {
        return (new self(new JwtParser(new JoseEncoder())))->parse($signedPayload);
    }

    public function parse(string $jws): Jws
    {
        $token = $this->parser->parse($jws);
        if ($token instanceof Plain) {
            return Jws::fromJwtPlain($token);
        }

        throw new ValidationException('Invalid jwt token');
    }
}
