<?php

namespace ReceiptValidator\AppleAppStore\JWT;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser as JwtParser;

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
     * @param $signedPayload
     *
     * @return Jws
     */
    public static function toJws($signedPayload): Jws
    {
        return (new self(new JwtParser(new JoseEncoder())))->parse($signedPayload);
    }

    public function parse(string $jws): Jws
    {
        return Jws::fromJwtPlain($this->parser->parse($jws));
    }
}
