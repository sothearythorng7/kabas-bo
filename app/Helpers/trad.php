<?php

if (! function_exists('t')) {
    /**
     * Helper pour traductions dans messages.php
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    function t(string $key, array $replace = [], string $locale = null): string
    {
        return trans("messages.$key", $replace, $locale);
    }
}