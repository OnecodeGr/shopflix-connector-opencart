<?php
namespace Onecode\Library;

class EventRow
{
    /**
     * @var bool
     */
    public $status = true;

    /**
     * @var string
     */
    public $code;

    /**
     * @var string
     */
    public $trigger;

    /**
     * @var string
     */
    public $action;

    /**
     * @var int
     */
    public $order;

    public function __construct(string $code, string $trigger, string $action, int $order, bool $status = true)
    {
        $this->status = $status;
        $this->code = $code;
        $this->trigger = $trigger;
        $this->action = $action;
        $this->order = $order;
    }
}