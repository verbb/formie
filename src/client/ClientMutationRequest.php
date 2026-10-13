<?php
namespace verbb\formie\client;

use Closure;

abstract class ClientMutationRequest extends BaseClientModel
{
    // Properties
    // =========================================================================

    // Server-owned authorization must not appear in serialized client payloads.
    private ?Closure $_scopeAuthorizer = null;


    // Public Methods
    // =========================================================================

    public function getScopeAuthorizer(): ?Closure
    {
        return $this->_scopeAuthorizer;
    }

    public function setScopeAuthorizer(?Closure $authorizer): void
    {
        $this->_scopeAuthorizer = $authorizer;
    }
}
