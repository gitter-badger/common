<?php

namespace VGMdb\Component\OAuthServer\Storage;

use OAuth2\IOAuth2GrantExtension;

/**
 * @author Adrien Brault <adrien.brault@gmail.com>
 */
interface GrantExtensionDispatcherInterface
{
    public function setGrantExtension($uri, GrantExtensionInterface $grantExtension);
}
