<?php

namespace AsyncAws\Ses\ValueObject;

use AsyncAws\Core\Exception\InvalidArgument;

class Content
{
    /**
     * The content of the message itself.
     */
    private $Data;

    /**
     * The character set for the content. Because of the constraints of the SMTP protocol, Amazon SES uses 7-bit ASCII by
     * default. If the text includes characters outside of the ASCII range, you have to specify a character set. For
     * example, you could specify `UTF-8`, `ISO-8859-1`, or `Shift_JIS`.
     */
    private $Charset;

    /**
     * @param array{
     *   Data: string,
     *   Charset?: null|string,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->Data = $input['Data'] ?? null;
        $this->Charset = $input['Charset'] ?? null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    public function getCharset(): ?string
    {
        return $this->Charset;
    }

    public function getData(): string
    {
        return $this->Data;
    }

    public function validate(): void
    {
        if (null === $this->Data) {
            throw new InvalidArgument(sprintf('Missing parameter "Data" when validating the "%s". The value cannot be null.', __CLASS__));
        }
    }
}
