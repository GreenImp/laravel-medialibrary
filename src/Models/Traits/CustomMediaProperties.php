<?php

namespace Spatie\MediaLibrary\Models\Traits;

trait CustomMediaProperties
{
    public function setCustomHeaders(array $customHeaders)
    {
        $this->setCustomProperty('custom_headers', $customHeaders);

        return $this;
    }

    public function getCustomHeaders()
    {
        return $this->getCustomProperty('custom_headers', []);
    }
}
