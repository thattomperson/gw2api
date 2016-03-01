<?php

namespace GW2Treasures\GW2Api\V2\Endpoint\Guild;

use GW2Treasures\GW2Api\GW2Api;
use GW2Treasures\GW2Api\V2\Authentication\AuthenticatedEndpoint;
use GW2Treasures\GW2Api\V2\Authentication\IAuthenticatedEndpoint;
use GW2Treasures\GW2Api\V2\Endpoint;

class MemberEndpoint extends Endpoint implements IAuthenticatedEndpoint, IRestrictedGuildEndpoint {
    use AuthenticatedEndpoint;

    /** @var string $guildId */
    protected $guildId;

    public function __construct(GW2Api $api, $apiKey, $guildId) {
        parent::__construct($api);

        $this->apiKey = $apiKey;
        $this->guildId = $guildId;
    }

    /**
     * The url of this endpoint.
     *
     * @return string
     */
    public function url() {
        return 'v2/guild/'.$this->guildId.'/members';
    }

    /**
     * @return array
     */
    public function get() {
        return $this->request()->json();
    }
}
