<?php

namespace Agenta\UkrpaymentsP2p;

use Illuminate\Support\Facades\Facade;

/**
 */
class UkrpaymentsP2pFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'ukrpayments_p2p';
    }
}
