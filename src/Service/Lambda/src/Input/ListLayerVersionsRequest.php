<?php

namespace AsyncAws\Lambda\Input;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\Core\Request;
use AsyncAws\Core\Stream\StreamFactory;
use AsyncAws\Lambda\Enum\Runtime;

class ListLayerVersionsRequest
{
    /**
     * A runtime identifier. For example, `go1.x`.
     *
     * @var Runtime::*|null
     */
    private $CompatibleRuntime;

    /**
     * The name or Amazon Resource Name (ARN) of the layer.
     *
     * @required
     *
     * @var string|null
     */
    private $LayerName;

    /**
     * A pagination token returned by a previous call.
     *
     * @var string|null
     */
    private $Marker;

    /**
     * The maximum number of versions to return.
     *
     * @var int|null
     */
    private $MaxItems;

    /**
     * @param array{
     *   CompatibleRuntime?: \AsyncAws\Lambda\Enum\Runtime::*,
     *   LayerName?: string,
     *   Marker?: string,
     *   MaxItems?: int,
     * } $input
     */
    public function __construct(array $input = [])
    {
        $this->CompatibleRuntime = $input['CompatibleRuntime'] ?? null;
        $this->LayerName = $input['LayerName'] ?? null;
        $this->Marker = $input['Marker'] ?? null;
        $this->MaxItems = $input['MaxItems'] ?? null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    /**
     * @return Runtime::*|null
     */
    public function getCompatibleRuntime(): ?string
    {
        return $this->CompatibleRuntime;
    }

    public function getLayerName(): ?string
    {
        return $this->LayerName;
    }

    public function getMarker(): ?string
    {
        return $this->Marker;
    }

    public function getMaxItems(): ?int
    {
        return $this->MaxItems;
    }

    /**
     * @internal
     */
    public function request(): Request
    {
        // Prepare headers
        $headers = ['content-type' => 'application/json'];

        // Prepare query
        $query = [];
        if (null !== $this->CompatibleRuntime) {
            $query['CompatibleRuntime'] = $this->CompatibleRuntime;
        }
        if (null !== $this->Marker) {
            $query['Marker'] = $this->Marker;
        }
        if (null !== $this->MaxItems) {
            $query['MaxItems'] = (string) $this->MaxItems;
        }

        // Prepare URI
        $uri = [];
        $uri['LayerName'] = $this->LayerName ?? '';
        $uriString = "/2018-10-31/layers/{$uri['LayerName']}/versions";

        // Return the Request
        return new Request('GET', $uriString, $query, $headers, StreamFactory::create(null));
    }

    /**
     * @param Runtime::*|null $value
     */
    public function setCompatibleRuntime(?string $value): self
    {
        $this->CompatibleRuntime = $value;

        return $this;
    }

    public function setLayerName(?string $value): self
    {
        $this->LayerName = $value;

        return $this;
    }

    public function setMarker(?string $value): self
    {
        $this->Marker = $value;

        return $this;
    }

    public function setMaxItems(?int $value): self
    {
        $this->MaxItems = $value;

        return $this;
    }

    public function validate(): void
    {
        if (null !== $this->CompatibleRuntime) {
            if (!Runtime::exists($this->CompatibleRuntime)) {
                throw new InvalidArgument(sprintf('Invalid parameter "CompatibleRuntime" when validating the "%s". The value "%s" is not a valid "Runtime".', __CLASS__, $this->CompatibleRuntime));
            }
        }

        if (null === $this->LayerName) {
            throw new InvalidArgument(sprintf('Missing parameter "LayerName" when validating the "%s". The value cannot be null.', __CLASS__));
        }
    }
}
