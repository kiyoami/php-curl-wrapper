<?php

namespace Kiyoami\Curl;

class MultiStatus
{
    public $status;
    public $active;
    public function __construct(int $status, int $active)
    {
        $this->status = $status;
        $this->active = $active;
    }
}
