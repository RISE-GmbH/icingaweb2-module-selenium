<?php

// SPDX-FileCopyrightText: 2026 Research Industrial Systems Engineering (RISE) Forschungs-, Entwicklungs- und Großprojektberatung GmbH
// SPDX-FileCopyrightText: 2016 Icinga GmbH <https://icinga.com> Elastic Module
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Selenium;

use Icinga\Exception\IcingaException;

class CustomException extends IcingaException
{
    /**
     * The curl error code
     *
     * @var int
     */
    protected $errorCode;

    /**
     * Set the curl error code
     *
     * @param   int     $errorCode
     *
     * @return  $this
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = (int) $errorCode;
        return $this;
    }

    /**
     * Return the curl error code
     *
     * @return  int
     */
    public function getErrorCode()
    {
        return $this->code;
    }
}
