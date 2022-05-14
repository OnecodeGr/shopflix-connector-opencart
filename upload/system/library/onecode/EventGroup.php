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

    public function addRaw(
        string $key,
        string $code,
        string $trigger,
        string $action,
        int    $order,
        bool   $status = true
    ): self {
        return $this->add($key, new EventRow($code, $trigger, $action, $order, $status));
    }

    public
    function add(
        string $key, EventRow $row
    ): self {
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