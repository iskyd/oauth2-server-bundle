<?php

namespace OAuth2\ServerBundle\Model;

/**
 * ClientInterface
 */
interface ClientInterface
{
    public function getClientSecret();

    public function getRedirectUri();

    public function getGrantTypes();

    public function getScopes();

    public function getPublicKey();
}
