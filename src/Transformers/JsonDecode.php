<?php

declare(strict_types=1);

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

namespace Wizaplace\Etl\Transformers;

use Wizaplace\Etl\Row;

class JsonDecode extends Transformer
{
    /**
     * Transformer columns.
     *
     * @var string[]
     */
    protected $columns = [];

    /**
     * Use associative arrays.
     *
     * @var bool
     */
    protected $assoc = false;

    /**
     * Options.
     *
     * @var int
     */
    protected $options = 0;

    /**
     * Maximum depth.
     *
     * @var int
     */
    protected $depth = 512;

    /**
     * Properties that can be set via the options method.
     *
     * @var string[]
     */
    protected $availableOptions = [
        'assoc', 'columns', 'depth', 'options',
    ];

    /**
     * Transform the given row.
     */
    public function transform(Row $row): void
    {
        $row->transform($this->columns, function ($column) {
            return \json_decode($column, $this->assoc, $this->depth, $this->options);
        });
    }
}
