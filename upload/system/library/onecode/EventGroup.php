<?php
namespace Onecode\Library;

require_once(dirname(__FILE__) . '/EventRow.php');

class EventGroup
{
    /**
     * @var EventRow[]
     */
    private $group = [];

    public function __construct(array $group = [])
    {
        $this->group = $group;
    }

    public function add(string $key, EventRow $row): self
    {
        $this->group[$key] = $row;
        return $this;
    }

    /**
     * @return EventRow[]
     */
    public function get(): array
    {
        return $this->group;
    }
}