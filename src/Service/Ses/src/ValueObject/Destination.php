<?php

namespace AsyncAws\Ses\ValueObject;

class Destination
{
    /**
     * An array that contains the email addresses of the "To" recipients for the email.
     */
    private $ToAddresses;

    /**
     * An array that contains the email addresses of the "CC" (carbon copy) recipients for the email.
     */
    private $CcAddresses;

    /**
     * An array that contains the email addresses of the "BCC" (blind carbon copy) recipients for the email.
     */
    private $BccAddresses;

    /**
     * @param array{
     *   ToAddresses?: null|string[],
     *   CcAddresses?: null|string[],
     *   BccAddresses?: null|string[],
     * } $input
     */
    public function __construct(array $input)
    {
        $this->ToAddresses = $input['ToAddresses'] ?? [];
        $this->CcAddresses = $input['CcAddresses'] ?? [];
        $this->BccAddresses = $input['BccAddresses'] ?? [];
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    /**
     * @return string[]
     */
    public function getBccAddresses(): array
    {
        return $this->BccAddresses;
    }

    /**
     * @return string[]
     */
    public function getCcAddresses(): array
    {
        return $this->CcAddresses;
    }

    /**
     * @return string[]
     */
    public function getToAddresses(): array
    {
        return $this->ToAddresses;
    }

    public function validate(): void
    {
        // There are no required properties
    }
}
