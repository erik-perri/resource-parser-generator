<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Types\TypeContract;

class DocBlockData
{
    /**
     * @var ReadOnlyCollection<string, TypeContract>
     */
    public readonly ReadOnlyCollection $params;

    /**
     * @var ReadOnlyCollection<string, TypeContract>
     */
    public readonly ReadOnlyCollection $properties;

    /**
     * @var ReadOnlyCollection<string, TypeContract>
     */
    public readonly ReadOnlyCollection $methods;

    /**
     * @var ReadOnlyCollection<string, TypeContract>
     */
    public readonly ReadOnlyCollection $vars;

    /**
     * @param ?string $comment
     * @param TypeContract|null $return
     * @param Collection<string, TypeContract> $params
     * @param Collection<string, TypeContract> $properties
     * @param Collection<string, TypeContract> $methods
     * @param Collection<string, TypeContract> $vars
     */
    public function __construct(
        public readonly ?string $comment,
        public readonly ?TypeContract $return,
        Collection $params,
        Collection $properties,
        Collection $methods,
        Collection $vars,
    ) {
        $this->params = new ReadOnlyCollection($params->all());
        $this->properties = new ReadOnlyCollection($properties->all());
        $this->methods = new ReadOnlyCollection($methods->all());
        $this->vars = new ReadOnlyCollection($vars->all());
    }
}
